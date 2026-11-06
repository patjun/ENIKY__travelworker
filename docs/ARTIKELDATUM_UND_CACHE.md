# Artikeldatum und Cache bei Widget-Änderungen

## Übersicht

Ändert sich der Inhalt eines Attraktions-Widgets (z. B. die Öffnungszeiten), soll

1. das WordPress-Änderungsdatum (`post_modified`, `post_modified_gmt`) **nur der Beiträge und Seiten** aktualisiert werden, die dieses Widget einbinden,
2. der WP-Rocket-Cache genau dieser Beiträge geleert werden.

Reine Neu-Syncs ohne inhaltliche Änderung verändern nichts.

Die Erkennung passiert in Laravel, die Aktualisierung der Beiträge und des Caches im WordPress-Plugin. Die Plugin-Seite ist in der Plugin-Dokumentation beschrieben (Plugin-Repo, `src/attraction-manager/README-LARAVEL.md`); sie ist dort die Referenz. Die Beschreibung der Plugin-Seite weiter unten entspricht dem umgesetzten Plugin-Code (Spezifikation: `docs/superpowers/specs/2026-10-12-artikeldatum-und-cache-design.md`).

## Datenfluss

1. **Erkennung** in `Attraction::generateWidgets()`: Jede Widget-HTML (pro Sprache de/en) wird neu erzeugt und mit dem bisherigen Wert verglichen. Vor dem Vergleich normalisiert `Attraction::normalizeHtml()` den Leerraum (Whitespace zwischen Tags, Zeilenumbrüche, mehrfache Leerzeichen). Weicht der Inhalt ab, wird das zugehörige `*_changed_at` auf `now()` gesetzt. Ist die bisherige HTML leer (erster Lauf), zählt das ebenfalls als Änderung.
2. **Delta-Sync** (`attractions:sync-wordpress`, `AttractionSyncService`): Es werden nur Datensätze und Widgets gesendet, die sich seit dem letzten erfolgreichen Sync geändert haben.
3. **Plugin**: Anhand der mitgesendeten `widget_*_changed_at` werden die betroffenen Beiträge datiert und deren Cache geleert.

### Änderungszeitpunkte

Nullable Timestamp-Spalten an `attractions`, je Widget und Sprache (englisch mit Präfix `en_`):

| Widget | Spalte (de) | API-Feld |
|---|---|---|
| Kontakt | `contact_info_changed_at` | `widget_contact` |
| Bewertung | `rating_changed_at` | `widget_rating` |
| Öffnungszeiten | `opening_hours_changed_at` | `widget_opening_hours` |
| Preise | `prices_changed_at` | `widget_prices` |
| Barrierefreiheit | `accessibility_changed_at` | `widget_accessibility` |
| JSON-LD | `structured_data_changed_at` | `json_ld` |

Die Zuordnung steht zentral in `Attraction::widgetMap($language)` (API-Feld, HTML-Spalte, Zeitstempel-Spalte, Generator-Methode).

### Delta-Regeln

- **Datensatz senden** (`AttractionSyncService::needsSync()`): wenn für die Sprache keine WordPress-ID oder kein `wp_{lang}_last_sync` existiert, oder wenn `updated_at` neuer ist als `wp_{lang}_last_sync`.
- **Widget senden** (in `mapAttractionToWordPress()`): wenn `wp_{lang}_last_sync` leer ist oder das `*_changed_at` des Widgets neuer ist als `wp_{lang}_last_sync`. Pro Widget wird dann `<feld>_changed_at` mitgesendet.
- **Neuanlage** in WordPress (noch keine `wp_{lang}_id`): alle Widgets, ohne `changed_at`.
- `wp_{lang}_last_sync` wird nur nach erfolgreichem Request gesetzt. Fehlgeschlagene Syncs werden beim nächsten Lauf erneut gesendet.

### `--force`

`php artisan attractions:sync-wordpress --force` sendet alle Datensätze und alle Widgets und lässt `changed_at` weg. Dadurch bleibt das Beitragsdatum unverändert; der Befehl eignet sich für einen kompletten Abgleich, ohne Artikeldaten zu verschieben. `--language=de|en|both` bleibt kombinierbar.

### Sofort übertragen und alle Widgets erneuern

Im Admin lassen sich Attraktionen übertragen, ohne auf den Lauf um 02:00 zu warten. Gesendet wird der gespeicherte Stand in beiden Sprachen.

| Aktion | Ort | Rollen | Ablauf |
|---|---|---|---|
| Sync now | Bearbeiten-Seite, Listenzeile | `super_admin`, `admin`, `editor` | sofort, Ergebnis direkt sichtbar |
| Sync | Mehrfachauswahl in der Liste | `super_admin`, `admin`, `editor` | Hintergrund |
| Sync all | Liste oben, mit Bestätigung | `super_admin`, `admin` | Hintergrund |
| Renew all widgets | Liste oben, mit Bestätigung | `super_admin`, `admin` | Hintergrund |

Die ersten drei Aktionen folgen den Delta-Regeln wie der nächtliche Sync: Sie senden nur Änderungen, leeren den Cache und datieren die Beiträge. Ohne Änderung wird nichts gesendet.

„Renew all widgets“ ist für Änderungen an der Widget-Struktur gedacht, zum Beispiel neues Markup oder neue Klassennamen. Die Aktion erzeugt alle Widgets neu und sendet sie vollständig:

- Das Erneuern setzt keine `*_changed_at` (`Attraction::regenerateWidgetsKeepingChangeDates()`), sonst würde der nächtliche Sync alle Beiträge datieren.
- Es setzt auch kein `updated_at`, damit „Last modified“ in der Attraktionsliste nur echte Änderungen zeigt.
- Mitgesendet wird nur `json_ld_changed_at`. Das leert nach Variante A den Cache aller Beiträge mit Widgets dieser Attraktion und datiert sie nicht. `--force` leert dagegen keinen Cache.
- Eine echte Änderung, die noch auf den Sync wartet, behält ihren Zeitstempel, ihre Beiträge werden wie üblich datiert.
- Schlägt die Übertragung einer Attraktion fehl, bleibt ihr neues HTML in Laravel, und der nächtliche Sync sendet es nicht. Die Aktion lässt sich gefahrlos wiederholen.
- Eine Änderung durch den Saisonwechsel zählt hier nicht als Änderung, weil die Aktion alle Zeitstempel erhält.

Das Ergebnis der Hintergrundläufe erscheint in der Glocke im Admin (Datenbank-Benachrichtigungen, aktualisiert sich alle 30 Sekunden). Bei einem Login-Fehler überspringt der Lauf die betroffene Sprache, schlägt der Login in beiden Sprachen fehl, endet er. `SyncAttractionsToWordPressJob` arbeitet höchstens 45 Sekunden und reiht sich dann mit den restlichen Attraktionen neu ein, weil die Queue einen Job nach `retry_after` (90 Sekunden) erneut vergibt. Dafür muss ein Queue-Worker laufen.

### Fehler beim Sync

Eine Attraktion gilt nur dann als in WordPress gelöscht, wenn das Plugin 404 mit dem Code `attraction_not_found` meldet. Ein 404 mit anderem Code (zum Beispiel `rest_no_route` bei deaktiviertem Plugin), 401, 403, 5xx, Netzwerkfehler und nicht lesbare Antworten sind Fehler. Der Befehl zeigt den Grund pro Attraktion an, zum Beispiel `HTTP 401 rest_forbidden`.

Bei einem Login-Fehler (401, oder 403 mit `rest_forbidden`) bricht der Befehl die betroffene Sprache ab und meldet, wie viele Attraktionen übersprungen wurden. Die andere Sprache läuft weiter. Ein 403 ohne `rest_forbidden` bricht nicht ab, weil eine Firewall einzelne Inhalte blockieren kann und sonst eine einzelne Attraktion jeden Lauf stoppen würde.

### Warum `updated_at` nicht durch den Sync springt

Nach einem erfolgreichen Request setzt der Sync `wp_{lang}_id` und `wp_{lang}_last_sync` und speichert mit `$attraction->timestamps = false`. So wird `updated_at` nicht angefasst. Sonst wäre jeder Sync selbst eine „Änderung“, und der Vergleich `updated_at > last_sync` würde nie zur Ruhe kommen.

## API-Vertrag

`PUT /attractions/{id}` des Plugins erhält zusätzlich optionale Felder:

`widget_contact_changed_at`, `widget_rating_changed_at`, `widget_opening_hours_changed_at`, `widget_prices_changed_at`, `widget_accessibility_changed_at`, `json_ld_changed_at`

- Format: ISO 8601, UTC (z. B. `2026-10-12T08:30:00+00:00`).
- Wird nur zusammen mit dem jeweiligen Widget gesendet und nur, wenn das `*_changed_at` in Laravel gesetzt ist.
- Bei Neuanlage und bei `--force` werden die Felder weggelassen.
- Das Plugin speichert die Zeitstempel nicht in `wp_attractions`; sie steuern nur die Aktualisierung von Beiträgen und Cache. Fehlt ein Zeitstempel, passiert an Beiträgen und Cache nichts.

## Bestandsdaten

Bei bestehenden Attraktionen sind die `*_changed_at` nach der Migration `NULL`. Ein `NULL`-Wert gilt im Delta-Sync nicht als „geändert“, es werden also zunächst keine Beiträge datiert.

Achtung: Eine Attraktion, die nach dem letzten nächtlichen Sync, aber vor dem Deploy bearbeitet wurde, hat `updated_at > last_sync`, aber kein Widget zählt als geändert. Ihr neues HTML würde ohne Gegenmaßnahme erst bei der nächsten Bearbeitung in WordPress ankommen.

## Release-Reihenfolge

1. Plugin-Seite zuerst deployen (rückwärtskompatibel: ohne `*_changed_at` ändert sich an Beiträgen und Cache nichts).
2. Laravel-App deployen. Das Deploy-Script führt `php artisan migrate --force` automatisch aus, die Migration läuft also mit dem Deploy und nicht als eigener Schritt.
3. Optional `php artisan locations:regenerate-widgets`
4. SOFORT danach `php artisan attractions:sync-wordpress --force`, vor dem nächsten geplanten Lauf um 02:00.

Da Schritt 2 durch das Deploy-Script automatisch passiert, darf der Laravel-Deploy erst nach dem Plugin-Deploy laufen und nicht so spät, dass Schritt 3 und 4 nicht mehr sicher vor 02:00 erledigt sind. Die Schritte 3 und 4 macht das Deploy-Script nicht, sie werden von Hand auf dem Server ausgeführt.

Warum: `--force` schickt das aktuelle HTML aller Attraktionen ohne Änderungsdaten und setzt `last_sync` erst nach den `changed_at`-Stempeln des Regenerierens auf `now()`. Damit wird der Ausgangszustand absorbiert:

- Keine Massen-Neudatierung: `locations:regenerate-widgets` stempelt jedes Widget, dessen gespeichertes HTML von der heutigen Generatorausgabe abweicht (Saison über `now()`, Generator-Änderungen, vorher leere HTML). Ohne den `--force`-Lauf würde der nächste nächtliche Sync alle diese Beiträge auf einmal neu datieren.
- Keine verlorenen Änderungen: Ausstehende Bearbeitungen (zwischen letztem Sync und Deploy) erreichen WordPress ebenfalls über diesen Lauf.

Das frühere „zur Erfassung des Ausgangszustands neu generieren“ gilt nicht mehr als Empfehlung.

## Leere Widget-Ausgaben

Liefert der Generator für ein Widget `null` (Daten entfernt), wird es als leerer String gespeichert und gesendet. Ein leerer Wert überschreibt nun das WordPress-Widget, statt den alten Inhalt zu behalten: entfernte Daten verschwinden aus dem Beitrag.

## JSON-LD (Variante A)

Das JSON-LD enthält u. a. Öffnungszeiten, Bewertung und Kontakt und ändert sich deshalb bei fast jeder Widget-Änderung. Würde es Beiträge datieren, bekäme jeder Beitrag mit irgendeinem Widget der Attraktion ein neues Datum. Deshalb gilt:

- Nur `widget_*_changed_at` setzen `post_modified`, und zwar nur bei Beiträgen mit dem passenden Shortcode.
- `json_ld_changed_at` leert den Cache aller Beiträge mit irgendeinem der fünf Widgets dieser Attraktion, datiert aber nicht. Sonst würde dort veraltetes JSON-LD aus dem Cache ausgeliefert.
- Eine Widget-Änderung leert zusätzlich den Cache der Beiträge mit dem geänderten Widget (diese werden ohnehin datiert).

## JSON-LD: Saisondaten der Öffnungszeiten

Google und schema.org verlangen für `validFrom` und `validThrough` ein volles Datum (`YYYY-MM-DD`). Das frühere Format ohne Jahr (`--04-01`) meldet der Validator als Fehler. Die Saisons im Formular haben aber kein Jahr und wiederholen sich jährlich.

Deshalb gibt `generateStructuredData()` für jede Saison den laufenden oder nächsten Zeitraum aus. Eine Saison bis 31.10. steht bis zu diesem Tag mit dem aktuellen Jahr im JSON-LD und ab dem 1.11. mit dem nächsten. Saisons über den Jahreswechsel enden im Folgejahr. Der 29.02. wird in Nicht-Schaltjahren zum 28.02. Ohne zwei gültige Daten entfallen beide Felder, die Öffnungszeiten bleiben stehen.

Weil die Generatoren nur beim Speichern laufen, würden die Daten sonst nach Saisonende veralten. `attractions:refresh-structured-data` läuft täglich um 01:45 vor dem Sync und erzeugt nur das JSON-LD neu, nicht die sichtbaren Widgets. Das setzt `structured_data_changed_at`, damit leert der Sync nach Variante A nur den Cache und datiert keine Beiträge. Ohne Änderung bleibt der Datensatz unberührt.

Nach dem Deploy stellt der erste Lauf bestehende Attraktionen auf das neue Format um. Der Sync um 02:00 überträgt das Ergebnis. Das Plugin muss dafür nicht geändert werden.

## Preis-Widget: aktueller Bereich im Browser

Die Widget-HTML entsteht nur beim Speichern oder per `RegenerateLocationWidgets`, nicht täglich. Ein beim Erzeugen gesetztes `open` würde deshalb veralten: Eine Attraktion bliebe nach dem letzten Speichern im Winter auch im Juli auf den Winterpreisen stehen. Automatisches Neuerzeugen zum Saisonwechsel würde dagegen `prices_changed_at` setzen und Artikeldaten verschieben, obwohl sich kein Preis geändert hat.

Deshalb:

- Laravel markiert immer den ersten Bereich mit `open`.
- Jeder Bereich mit Saison trägt `data-season-start` und `data-season-end`.
- Das Plugin-JS öffnet beim Laden den Bereich, dessen Saison heute läuft, und schließt den ersten. Trifft keine Saison zu oder ist JS aus, bleibt der erste Bereich offen. Maßgeblich ist das Datum im Browser des Lesers.

Die gecachte HTML ist für alle Leser gleich, deshalb funktioniert das mit WP Rocket.

## Plugin-Seite

- Betroffene Beiträge: Veröffentlichte Beiträge und Seiten (`post`, `page`, Status `publish`), in deren `post_content` oder `wp_postmeta.meta_value` (alle Meta-Keys) der passende Shortcode (`attraction_contact`, `attraction_rating`, `attraction_hours`, `attraction_prices`, `attraction_accessibility`) mit exakt dieser ID steht (`id="42"`, `id='42'`, `id=42`, auch mit Backslash maskierte Anführungszeichen in JSON/serialisierten Meta-Werten; `id="4"` trifft nicht `42`). Entwürfe und Revisionen werden ignoriert.
- Datum: `post_modified` und `post_modified_gmt` werden direkt per `$wpdb->update` auf das gesendete `changed_at` gesetzt, nie rückwärts (nur wenn jünger als das bisherige `post_modified_gmt`). Kein `wp_update_post()`, damit keine Revisionen und keine `save_post`-Hooks anderer Plugins ausgelöst werden.
- Cache: `clean_post_cache()` und, falls vorhanden, `rocket_clean_post()` je betroffenem Beitrag. Ohne WP Rocket kein Fehler.
- Fehler bei Suche, Datum oder Cache-Leeren lassen das Speichern der Attraktion nicht scheitern (Protokoll per `error_log`). Ungültige Zeitstempel werden ignoriert.
- Die Antwort enthält zusätzlich `updated_posts`: Post-IDs aller Beiträge, deren Cache geleert wurde (auch solche, die nicht datiert wurden, z. B. bei reinem JSON-LD-Update).
- Ein PUT braucht weiterhin mindestens ein Inhaltsfeld (`name`, `city`, `country`, `widget_*`, `json_ld`). Ein Request nur mit `*_changed_at` liefert `400`. Der Laravel-Sync sendet `changed_at` immer zusammen mit dem Widget.

Details und Tests (`cd src && composer install && vendor/bin/phpunit`): Plugin-Dokumentation `src/attraction-manager/README-LARAVEL.md` im Plugin-Repo.

## Live-Verifikation

Am 26.10.2026 lokal gegen `vonortzuort.test` (Multisite mit WP Rocket 3.23, Plugin aus dem Feature-Branch) durchgeführt: 20 von 20 Prüfungen bestanden. Das Skript `src/tests/live/verify-post-refresh.sh` im Plugin-Repo prüft:

1. Eine reine Widget-Änderung (z. B. `widget_rating_changed_at`) datiert und leert nur die Beiträge mit diesem Shortcode; Beiträge mit anderen Widgets derselben Attraktion bleiben unverändert.
2. Ein Shortcode in einem ACF-Feld (Post-Meta) wird gefunden; ein Shortcode mit anderer ID (`id="120"` statt `id="12"`) bleibt unberührt.
3. Eine reine `json_ld`-Änderung (`json_ld_changed_at`) leert den Cache aller Beiträge der Attraktion mit allen Shortcodes, datiert aber keinen.
4. Ein wiederholter Request mit demselben oder einem älteren Zeitstempel setzt `post_modified` nicht rückwärts.

Das Skript legt temporäre Test-Beiträge an und räumt sie danach wieder auf. Aufruf und Parameter stehen im Kopf des Skripts. Nicht gegen die Live-Seite laufen lassen, da es Beiträge vorübergehend neu datiert.

Hinweis für lokale Kopien der Live-Datenbank: WP Rocket legt seine Konfiguration pro Domain an (`wp-content/wp-rocket-config/<domain>.php`). Fehlt die Datei für die `.test`-Domain, cacht WP Rocket nicht. Erzeugen mit `wp eval 'rocket_generate_config_file();' --url=<domain>`.

## Entscheidungen

| Thema | Entscheidung |
|---|---|
| Welches Datum | WordPress-Änderungsdatum (`post_modified` / `post_modified_gmt`) |
| Wann | Nur bei inhaltlicher Änderung des Widgets |
| Welche Beiträge | Nur Beiträge mit dem passenden Shortcode und exakter ID |
| Wo erkannt | In Laravel (Vergleich alt/neu in `generateWidgets()`) |
| Plugin-Haltung | Vertraut dem gesendeten `changed_at`, kein eigener Vergleich |
| Saisonwechsel | Zählt als echte Änderung (Besucher sehen anderen Inhalt) |
| Shortcode-Fundorte | `post_content` und `wp_postmeta.meta_value` (generisch über alle Meta-Keys) |
| Cache | WP Rocket, `rocket_clean_post()` je betroffenem Beitrag |
| JSON-LD | Variante A: leert nur den Cache, datiert nicht |

Die Öffnungszeiten hängen von `now()` ab (Saisonauswahl). Die HTML kann sich also ohne Redaktionsänderung ändern; das wird bewusst als Änderung gewertet.

## Nicht Teil dieses Designs

- Sichtbare „Zuletzt aktualisiert“-Anzeige im Theme.
- Verknüpfte Beiträge (Relationship-Felder); Custom Fields werden nur direkt am Beitrag durchsucht.
- Asynchrone Verarbeitung (Action Scheduler).
- Neu-Laden des Caches (Preload) über das Leeren hinaus.
