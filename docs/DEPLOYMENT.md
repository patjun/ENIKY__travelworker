# Deployment und Server

Die Laravel-App läuft unter `https://bloghelper.eniky.net`, verwaltet über Ploi. Vor der App sitzt ein nginx-Passwortschutz (HTTP Basic Auth), deshalb antworten alle Seiten ohne Zugangsdaten mit `401`.

- Pfad: `/home/bloghelper-ev5vk/bloghelper.eniky.net`
- Benutzer: `bloghelper-ev5vk`
- PHP: **8.3** (`composer.json` verlangt seit 06.07.2026 `^8.3`)

## Deploy-Script (Ploi)

```bash
cd /home/bloghelper-ev5vk/bloghelper.eniky.net
git pull origin main

{SITE_COMPOSER} install --no-interaction --prefer-dist --optimize-autoloader --no-dev

npm ci
npm run build

{SITE_PHP} artisan migrate --force
{SITE_PHP} artisan optimize:clear
{SITE_PHP} artisan optimize
{SITE_PHP} artisan queue:restart

echo "" | sudo -S service php8.3-fpm reload
echo "🚀 Application deployed!"
```

- `migrate --force` läuft bei jedem Deploy automatisch. Ein Merge nach `main` mit neuer Migration wird also mit dem nächsten Deploy sofort migriert. Features mit festgelegter Release-Reihenfolge (z. B. [Artikeldatum und Cache](ARTIKELDATUM_UND_CACHE.md#release-reihenfolge)) erst mergen bzw. deployen, wenn die Vorbedingungen erfüllt sind.
- `optimize` cacht Config, Routen, Events und Views. `env()` darf deshalb nur in `config/` verwendet werden.
- `queue:restart` lädt laufende Queue-Worker mit dem neuen Code neu; ohne Worker schadet es nicht.
- Livewire- und Filament-Assets veröffentlicht `composer install` über `filament:upgrade` im Hook `post-autoload-dump`.

## Cronjobs (Ploi)

Alle Cronjobs laufen als `bloghelper-ev5vk` und **mit `php8.3`**. Mit `php8.2` bricht jeder Aufruf sofort an Composers Plattform-Prüfung ab („Your Composer dependencies require a PHP version >= 8.3.0“).

| Zeitplan | Befehl |
|----------|--------|
| `* * * * *` | `php8.3 /home/bloghelper-ev5vk/bloghelper.eniky.net/artisan schedule:run` |
| `*/5 * * * *` | `php8.3 /home/bloghelper-ev5vk/bloghelper.eniky.net/artisan dataforseo:check-pending` |
| `0 2 * * *` | `php8.3 /home/bloghelper-ev5vk/bloghelper.eniky.net/artisan locations:update-outdated …` |

`schedule:run` führt die Jobs aus `app/Console/Kernel.php` aus, derzeit den WordPress-Sync `attractions:sync-wordpress --language=both` täglich um 02:00. Die App läuft auf `UTC`, der Sync startet also um 02:00 UTC (03:00 MEZ, 04:00 MESZ). Ausgabe: `storage/logs/wp-sync.log`.

Bis 26.10.2026 fehlte der Cronjob für `schedule:run`, der nächtliche Sync lief bis dahin nie automatisch.

Auch beim Aufruf von `artisan` von Hand auf dem Server immer `php8.3` verwenden.

## Rollen und Berechtigungen

Zugang zum Filament-Panel haben nur Benutzer mit einer Rolle (`User::canAccessPanel`, siehe [Berechtigungsmatrix](BERECHTIGUNGSMATRIX.md)). Ohne Rolle meldet das Login „These credentials do not match our records.“, auch bei korrektem Passwort.

Die Migration legt die Rollentabellen nur leer an. Nach dem ersten Deploy einer neuen Umgebung bzw. wenn sich der Seeder ändert:

```bash
php8.3 artisan db:seed --class=RolePermissionSeeder --force
```

Der Seeder ist wiederholbar: Er legt fehlende Berechtigungen und Rollen an, setzt die Rechte pro Rolle fest und weist `jungbluth@eniky.com` die Rolle `super_admin` zu. Er löscht nichts und legt keine Benutzer an. Weitere Benutzer bekommen ihre Rolle im Admin unter Benutzer.

Auf dem Server fehlte der Seeder-Lauf vom Deploy am 27.04.2026 bis 26.10.2026; in dieser Zeit war kein Login möglich.

## Nach einem Deploy prüfen

```bash
cd /home/bloghelper-ev5vk/bloghelper.eniky.net
git log -1 --oneline
php8.3 artisan migrate:status | tail -5
php8.3 artisan schedule:list
```

Und einmal unter `/admin/login` anmelden.
