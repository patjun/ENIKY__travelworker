# Artikeldatum und Cache bei Widget-Änderungen – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ändert sich der Inhalt eines Attraktions-Widgets, aktualisiert das Plugin `post_modified` genau der Beiträge, die dieses Widget einbinden, und leert den WP-Rocket-Cache aller Beiträge mit einem Widget der Attraktion (Variante A: JSON-LD datiert nicht, leert aber den Cache).

**Architecture:** Laravel erkennt inhaltliche Änderungen beim Erzeugen der Widget-HTML (Vergleich alt/neu) und speichert pro Widget und Sprache ein `*_changed_at`. Der Sync sendet nur geänderte Datensätze und darin nur geänderte Widgets samt `*_changed_at`. Das Plugin vertraut diesen Zeitstempeln, findet die Beiträge über Shortcode-Suche in `post_content` und `wp_postmeta`, setzt `post_modified` nur für Beiträge mit dem geänderten Widget und leert den Cache für alle Beiträge mit einem Widget der Attraktion, wenn sich Widget oder JSON-LD geändert haben.

**Tech Stack:** Laravel 12 / PHP 8.3 / PHPUnit 11 (Laravel-Repo); WordPress-Plugin in PHP 7.4-kompatiblem Stil mit WP-Coding-Standards (Tabs, Yoda-Bedingungen), PHPUnit nur für WP-unabhängige Klassen (Plugin-Repo).

**Spec:** `docs/superpowers/specs/2026-10-12-artikeldatum-und-cache-design.md`

## Global Constraints

- Zwei getrennte Git-Repos, **eigene Commits/PRs pro Repo**: Laravel `/Users/patrick/Entwicklung/Herd/ENIKY/ENIKY__travelworker`, Plugin `/Users/patrick/Entwicklung/Herd/ENIKY/ENIKY__travelworker_wp_plugin`. Teil A (Task 1–4) im Laravel-Repo, Teil B (Task 5–9) im Plugin-Repo, Task 10 in beiden.
- Plugin-Code muss PHP 7.4-kompatibel bleiben (`Requires PHP: 7.4`): kein `match`, kein `?->`, keine Union Types, keine Constructor Promotion.
- Plugin-Namespace `AttractionManager`; Dateien `includes/class-*.php`; jede Datei beginnt mit `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- Laravel: PHPUnit-Klassen (kein Pest), `php artisan make:test --phpunit`, nach jeder Änderung `vendor/bin/pint --dirty --format agent`, Tests mit `php artisan test --compact <Datei>`.
- Shortcodes: `attraction_contact`, `attraction_rating`, `attraction_hours`, `attraction_accessibility`, Attribut `id`.
- API-Felder (unverändert): `widget_contact`, `widget_rating`, `widget_opening_hours`, `widget_accessibility`, `json_ld`. Neu, optional bei `PUT`: `<feld>_changed_at` (ISO 8601).
- Zeitstempel werden in UTC übertragen (`->utc()->toIso8601String()`).
- Das Plugin setzt `post_modified` nie rückwärts, nutzt nicht `wp_update_post()` und berücksichtigt nur Beiträge/Seiten mit Status `publish`.
- **Variante A (JSON-LD):** `json_ld_changed_at` datiert **keine** Beiträge, leert aber den Cache aller Beiträge mit irgendeinem Shortcode dieser Attraktion. Nur `widget_*_changed_at` ändern `post_modified`, und zwar nur bei Beiträgen mit dem passenden Shortcode.
- Fehler bei Beitragssuche oder Cache dürfen das Speichern der Attraktion nie scheitern lassen.
- Keine neuen Laravel-Abhängigkeiten. Für das Plugin wird `phpunit/phpunit` als **Dev-Abhängigkeit** in einer neuen `composer.json` unter `src/` eingeführt (Task 5, vorher kurz bestätigen lassen).
- Keine Markup-/Klassenänderungen (`am-*`) an den Widgets; die Admin-Vorschau-CSS bleibt unberührt.

## Review Focus

- Umbenennung einer Attraktion (nur `name`/`city`) wird trotz Delta-Sync übertragen, ändert aber kein Beitragsdatum (Task 3).
- Bestandsdaten ohne `*_changed_at` (NULL) lösen bei normalem Sync und bei `--force` keine Datumsänderung aus (Task 3).
- Shortcode in JSON-/serialisiertem Meta-Wert mit maskierten Anführungszeichen (`id=\"42\"`) wird gefunden, `id="4"` trifft nicht `id="42"` (Task 6).
- Schlägt der Request fehl, bleibt `wp_*_last_sync` unverändert und der nächste Lauf sendet erneut (Task 3).
- DE-Änderung markiert nie EN-Widgets und umgekehrt (Task 2).
- Beitrag hat bereits ein neueres `post_modified` als `changed_at` → Datum bleibt, Cache wird trotzdem geleert (Task 8).
- Beitrag mit nur `[attraction_contact]` bei Änderung der Öffnungszeiten: Datum bleibt, Cache wird wegen des geänderten JSON-LD geleert (Tasks 7, 8).
- Seitenzeitzone ≠ UTC: `post_modified` (lokal) und `post_modified_gmt` bleiben konsistent (Task 8).

---

## Teil A – Laravel-Repo

### Task 1: Spalten und Widget-Map im Modell

**Files:**
- Create: `database/migrations/2026_10_12_100000_add_widget_changed_at_to_attractions_table.php`
- Modify: `app/Models/Attraction.php` (`$casts`, neue Methode `widgetMap()`)
- Test: `tests/Feature/AttractionChangeTrackingTest.php` (neu)

**Interfaces:**
- Produces: Spalten `contact_info_changed_at`, `rating_changed_at`, `opening_hours_changed_at`, `accessibility_changed_at`, `structured_data_changed_at` und je mit Präfix `en_`; `Attraction::widgetMap(string $language): array` mit Einträgen `<api-feld> => ['html' => <Spalte>, 'changed_at' => <Spalte>, 'generator' => <Methodenname>]`.

- [ ] **Step 1: Test anlegen**

```bash
php artisan make:test --phpunit AttractionChangeTrackingTest --no-interaction
```

Inhalt von `tests/Feature/AttractionChangeTrackingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Attraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttractionChangeTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_at_columns_exist_for_both_languages(): void
    {
        foreach (['', 'en_'] as $prefix) {
            foreach (['contact_info', 'rating', 'opening_hours', 'accessibility', 'structured_data'] as $widget) {
                $this->assertTrue(
                    Schema::hasColumn('attractions', "{$prefix}{$widget}_changed_at"),
                    "Missing column {$prefix}{$widget}_changed_at"
                );
            }
        }
    }

    public function test_widget_map_maps_api_fields_per_language(): void
    {
        $de = Attraction::widgetMap('de');
        $en = Attraction::widgetMap('en');

        $this->assertSame(
            ['html' => 'opening_hours_html', 'changed_at' => 'opening_hours_changed_at', 'generator' => 'generateOpeningHoursHtml'],
            $de['widget_opening_hours']
        );
        $this->assertSame(
            ['html' => 'en_structured_data', 'changed_at' => 'en_structured_data_changed_at', 'generator' => 'generateStructuredData'],
            $en['json_ld']
        );
        $this->assertSame(
            ['widget_contact', 'widget_rating', 'widget_opening_hours', 'widget_accessibility', 'json_ld'],
            array_keys($de)
        );
    }
}
```

- [ ] **Step 2: Test laufen lassen, Fehlschlag prüfen**

Run: `php artisan test --compact tests/Feature/AttractionChangeTrackingTest.php`
Expected: FAIL (Spalten fehlen, `widgetMap` nicht definiert).

- [ ] **Step 3: Migration**

```bash
php artisan make:migration add_widget_changed_at_to_attractions_table --table=attractions --no-interaction
```

Datei auf den Namen `2026_10_12_100000_add_widget_changed_at_to_attractions_table.php` umbenennen (nach der letzten bestehenden Migration `2026_10_11_134320_…` einsortieren) und füllen:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $columns = [
        'contact_info_changed_at',
        'rating_changed_at',
        'opening_hours_changed_at',
        'accessibility_changed_at',
        'structured_data_changed_at',
        'en_contact_info_changed_at',
        'en_rating_changed_at',
        'en_opening_hours_changed_at',
        'en_accessibility_changed_at',
        'en_structured_data_changed_at',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attractions', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                $table->timestamp($column)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attractions', function (Blueprint $table) {
            $table->dropColumn($this->columns);
        });
    }
};
```

- [ ] **Step 4: Modell erweitern**

In `app/Models/Attraction.php` zu `$casts` hinzufügen (nach `'wp_en_last_sync' => 'datetime',`):

```php
        'contact_info_changed_at' => 'datetime',
        'rating_changed_at' => 'datetime',
        'opening_hours_changed_at' => 'datetime',
        'accessibility_changed_at' => 'datetime',
        'structured_data_changed_at' => 'datetime',
        'en_contact_info_changed_at' => 'datetime',
        'en_rating_changed_at' => 'datetime',
        'en_opening_hours_changed_at' => 'datetime',
        'en_accessibility_changed_at' => 'datetime',
        'en_structured_data_changed_at' => 'datetime',
```

und vor `generateWidgets()` die Methode:

```php
    /**
     * Map WordPress API fields to the HTML column, its change timestamp column
     * and the generator method for the given language.
     *
     * @return array<string, array{html: string, changed_at: string, generator: string}>
     */
    public static function widgetMap(string $language): array
    {
        $prefix = $language === 'en' ? 'en_' : '';

        return [
            'widget_contact' => ['html' => "{$prefix}contact_info_html", 'changed_at' => "{$prefix}contact_info_changed_at", 'generator' => 'generateContactInfoHtml'],
            'widget_rating' => ['html' => "{$prefix}rating_html", 'changed_at' => "{$prefix}rating_changed_at", 'generator' => 'generateRatingHtml'],
            'widget_opening_hours' => ['html' => "{$prefix}opening_hours_html", 'changed_at' => "{$prefix}opening_hours_changed_at", 'generator' => 'generateOpeningHoursHtml'],
            'widget_accessibility' => ['html' => "{$prefix}accessibility_html", 'changed_at' => "{$prefix}accessibility_changed_at", 'generator' => 'generateAccessibilityHtml'],
            'json_ld' => ['html' => "{$prefix}structured_data", 'changed_at' => "{$prefix}structured_data_changed_at", 'generator' => 'generateStructuredData'],
        ];
    }
```

- [ ] **Step 5: Test laufen lassen**

Run: `php artisan test --compact tests/Feature/AttractionChangeTrackingTest.php`
Expected: PASS (2 Tests).

- [ ] **Step 6: Formatieren und committen**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_10_12_100000_add_widget_changed_at_to_attractions_table.php app/Models/Attraction.php tests/Feature/AttractionChangeTrackingTest.php
git commit -m "feat: Add widget change timestamp columns and widget map to Attraction"
```

(Die bereits vorhandenen uncommitteten Änderungen an `AttractionSyncService`, `SyncAttractionsToWordPress` und `AttractionConsoleCommandsTest` gehören nicht dazu und werden nicht mit committet. Vor Task 3 klären, ob sie zuerst separat committet werden, weil Task 3 dieselben Dateien ändert.)

---

### Task 2: Änderungserkennung in `generateWidgets()`

**Files:**
- Modify: `app/Models/Attraction.php` (`generateWidgets()`, neue Methoden `normalizeHtml()`, `assignWidgetHtml()`)
- Test: `tests/Feature/AttractionChangeTrackingTest.php`

**Interfaces:**
- Consumes: `Attraction::widgetMap()` aus Task 1.
- Produces: `Attraction::normalizeHtml(string $html): string` (public static); `generateWidgets()` setzt `*_changed_at` auf `now()` genau dann, wenn die normalisierte neue HTML von der bisherigen abweicht.

- [ ] **Step 1: Failing Tests ergänzen**

In `AttractionChangeTrackingTest` oben `use Illuminate\Support\Carbon;` ergänzen und diese Tests/Helfer hinzufügen:

```php
    /**
     * @return array<int, array<string, mixed>>
     */
    private function hours(string $open, string $close): array
    {
        return [[
            'name' => null,
            'is_year_round' => true,
            'start_date' => null,
            'end_date' => null,
            'time_slots' => [
                ['days' => ['monday', 'tuesday'], 'open_time' => $open, 'close_time' => $close],
            ],
        ]];
    }

    public function test_first_generation_marks_every_widget_as_changed(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);

        $attraction->generateWidgets();

        foreach (['de', 'en'] as $language) {
            foreach (Attraction::widgetMap($language) as $definition) {
                $this->assertTrue(
                    $attraction->{$definition['changed_at']}->equalTo(now()),
                    $definition['changed_at'].' should be set on first generation'
                );
            }
        }
    }

    public function test_regenerating_unchanged_data_keeps_timestamps(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);
        $attraction->generateWidgets();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 11:00:00');
        $attraction = $attraction->fresh();
        $attraction->generateWidgets();

        $this->assertTrue($attraction->opening_hours_changed_at->equalTo(Carbon::parse('2026-10-12 10:00:00')));
        $this->assertTrue($attraction->contact_info_changed_at->equalTo(Carbon::parse('2026-10-12 10:00:00')));
        $this->assertFalse($attraction->isDirty(['opening_hours_changed_at', 'structured_data_changed_at']));
    }

    public function test_changing_opening_hours_only_marks_dependent_widgets(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);
        $attraction->generateWidgets();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction = $attraction->fresh();
        $attraction->manual_opening_hours = $this->hours('10:00', '18:00');
        $attraction->generateWidgets();

        $later = Carbon::parse('2026-10-12 12:00:00');
        $earlier = Carbon::parse('2026-10-12 10:00:00');
        $this->assertTrue($attraction->opening_hours_changed_at->equalTo($later));
        $this->assertTrue($attraction->en_opening_hours_changed_at->equalTo($later));
        $this->assertTrue($attraction->structured_data_changed_at->equalTo($later), 'JSON-LD contains the opening hours');
        $this->assertTrue($attraction->contact_info_changed_at->equalTo($earlier));
        $this->assertTrue($attraction->rating_changed_at->equalTo($earlier));
        $this->assertTrue($attraction->accessibility_changed_at->equalTo($earlier));
    }

    public function test_changing_english_name_does_not_mark_german_widgets(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);
        $attraction->generateWidgets();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction = $attraction->fresh();
        $attraction->en_name = 'A completely different English name';
        $attraction->generateWidgets();

        $earlier = Carbon::parse('2026-10-12 10:00:00');
        $this->assertTrue($attraction->en_contact_info_changed_at->equalTo(Carbon::parse('2026-10-12 12:00:00')));
        $this->assertTrue($attraction->contact_info_changed_at->equalTo($earlier));
        $this->assertTrue($attraction->structured_data_changed_at->equalTo($earlier));
    }

    public function test_whitespace_only_differences_are_not_a_change(): void
    {
        $this->assertSame(
            Attraction::normalizeHtml("<div>\n    <p>Hallo   Welt</p>\n</div>"),
            Attraction::normalizeHtml('<div><p>Hallo Welt</p></div>')
        );
        $this->assertNotSame(
            Attraction::normalizeHtml('<p>9:00</p>'),
            Attraction::normalizeHtml('<p>10:00</p>')
        );
    }
```

- [ ] **Step 2: Tests laufen lassen, Fehlschlag prüfen**

Run: `php artisan test --compact tests/Feature/AttractionChangeTrackingTest.php`
Expected: FAIL (`normalizeHtml` nicht definiert, Zeitstempel bleiben NULL).

- [ ] **Step 3: Implementierung**

In `app/Models/Attraction.php` den Block „Generate all widget HTML“ in `generateWidgets()` ersetzen durch:

```php
        foreach (['de', 'en'] as $language) {
            foreach (self::widgetMap($language) as $definition) {
                $this->assignWidgetHtml(
                    $definition['html'],
                    $definition['changed_at'],
                    (string) $this->{$definition['generator']}($language)
                );
            }
        }
```

(Das entfernt die zehn expliziten Zuweisungen `contact_info_html = …` bis `en_structured_data = …`; Zuordnung und Reihenfolge ergeben sich aus `widgetMap()`.)

Direkt nach `generateWidgets()` einfügen:

```php
    /**
     * Collapse whitespace so purely cosmetic differences are not treated as changes.
     */
    public static function normalizeHtml(string $html): string
    {
        $html = (string) preg_replace('/>\s+</', '><', $html);

        return trim((string) preg_replace('/\s+/', ' ', $html));
    }

    /**
     * Store generated widget HTML and stamp the change time when the content differs.
     */
    private function assignWidgetHtml(string $htmlColumn, string $changedAtColumn, string $html): void
    {
        if (self::normalizeHtml((string) $this->{$htmlColumn}) !== self::normalizeHtml($html)) {
            $this->{$changedAtColumn} = now();
        }

        $this->{$htmlColumn} = $html;
    }
```

- [ ] **Step 4: Tests laufen lassen**

Run: `php artisan test --compact tests/Feature/AttractionChangeTrackingTest.php tests/Feature/AttractionWidgetHtmlTest.php`
Expected: PASS. (Der zweite Lauf stellt sicher, dass die HTML-Ausgabe unverändert bleibt.)

- [ ] **Step 5: Formatieren und committen**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Attraction.php tests/Feature/AttractionChangeTrackingTest.php
git commit -m "feat: Track content changes per widget when generating widgets"
```

---

### Task 3: Delta-Sync mit `changed_at` und `--force`

**Files:**
- Modify: `app/Services/AttractionSyncService.php` (`syncAttraction()`, `syncAllAttractions()`, `mapAttractionToWordPress()`, neue Methode `needsSync()`)
- Modify: `app/Console/Commands/SyncAttractionsToWordPress.php` (Option `--force`)
- Test: `tests/Feature/AttractionSyncDeltaTest.php` (neu)

**Interfaces:**
- Consumes: `Attraction::widgetMap()` (Task 1), `*_changed_at` (Tasks 1–2).
- Produces: `AttractionSyncService::syncAttraction(Attraction $attraction, string $language, bool $force = false): bool`; `AttractionSyncService::needsSync(Attraction $attraction, string $language): bool`; `syncAllAttractions(string $language, bool $force = false): array`; Artisan-Option `--force`. Payload bei Update: `name`, `city`, `country` immer, geänderte Widgets plus `<feld>_changed_at` (ISO 8601 UTC). Bei Neuanlage und bei `--force`: alle Widgets, **ohne** `*_changed_at`.

Regel für „geändert“: Widget gilt als geändert, wenn `wp_{lang}_last_sync` leer ist oder `<changed_at> > wp_{lang}_last_sync`. `needsSync()` ist wahr, wenn keine WP-ID existiert, `last_sync` leer ist oder `updated_at > last_sync`. Damit `updated_at` nicht durch den Sync selbst springt, speichert `syncAttraction()` ohne Timestamps.

- [ ] **Step 1: Test anlegen**

```bash
php artisan make:test --phpunit AttractionSyncDeltaTest --no-interaction
```

```php
<?php

namespace Tests\Feature;

use App\Models\Attraction;
use App\Services\AttractionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AttractionSyncDeltaTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://wp.test/wp-json/attraction-manager/v1';

    protected function setUp(): void
    {
        parent::setUp();

        config(['wordpress.de' => ['api_url' => self::API, 'username' => 'admin', 'password' => 'secret']]);
    }

    private function syncedAttraction(): Attraction
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create();
        $attraction->generateWidgets();
        $attraction->wp_de_id = 77;
        $attraction->wp_de_last_sync = now();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');

        return $attraction->fresh();
    }

    public function test_create_sends_all_widgets_without_changed_at(): void
    {
        Http::fake(['wp.test/*' => Http::response(['id' => 77], 201)]);
        $attraction = Attraction::factory()->create();
        $attraction->generateWidgets();
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && isset($request['widget_opening_hours'], $request['json_ld'])
                && ! isset($request['widget_opening_hours_changed_at']);
        });
    }

    public function test_unchanged_attraction_is_not_sent(): void
    {
        Http::fake();
        $attraction = $this->syncedAttraction();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de'));

        Http::assertNothingSent();
    }

    public function test_changed_widget_is_sent_with_changed_at_and_others_are_omitted(): void
    {
        Http::fake([
            'wp.test/*/attractions/77' => Http::sequence()
                ->push(['id' => 77], 200)
                ->push(['id' => 77], 200),
        ]);
        $attraction = $this->syncedAttraction();
        $attraction->opening_hours_html = '<div>neue Zeiten</div>';
        $attraction->opening_hours_changed_at = Carbon::parse('2026-10-12 11:30:00');
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request['widget_opening_hours'] === '<div>neue Zeiten</div>'
                && $request['widget_opening_hours_changed_at'] === '2026-10-12T11:30:00+00:00'
                && ! isset($request['widget_contact'])
                && ! isset($request['widget_contact_changed_at'])
                && isset($request['name']);
        });
    }

    public function test_sync_does_not_bump_updated_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();
        $attraction->name = 'Umbenannt';
        $attraction->save();
        $updatedAt = $attraction->fresh()->updated_at;

        Carbon::setTestNow('2026-10-12 13:00:00');
        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        $fresh = $attraction->fresh();
        $this->assertTrue($fresh->updated_at->equalTo($updatedAt));
        $this->assertTrue($fresh->wp_de_last_sync->equalTo(Carbon::parse('2026-10-12 13:00:00')));
        $this->assertFalse((new AttractionSyncService)->needsSync($fresh, 'de'));
    }

    public function test_rename_is_synced_without_any_changed_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();
        $attraction->name = 'Neuer Name';
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request['name'] === 'Neuer Name'
                && ! isset($request['widget_opening_hours'])
                && ! isset($request['widget_opening_hours_changed_at']);
        });
    }

    public function test_legacy_rows_without_changed_at_never_send_changed_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();
        foreach (Attraction::widgetMap('de') as $definition) {
            $attraction->{$definition['changed_at']} = null;
        }
        $attraction->name = 'Neuer Name';
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de', force: true));

        Http::assertSent(fn (Request $request): bool => isset($request['widget_contact'])
            && ! isset($request['widget_contact_changed_at'])
            && ! isset($request['widget_opening_hours_changed_at']));
    }

    public function test_force_sends_everything_without_changed_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de', force: true));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && isset($request['widget_contact'], $request['widget_rating'], $request['widget_opening_hours'], $request['widget_accessibility'], $request['json_ld'])
            && ! isset($request['widget_contact_changed_at']));
    }

    public function test_failed_request_keeps_last_sync(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::sequence()
            ->push(['id' => 77], 200)
            ->push('error', 500),
        ]);
        $attraction = $this->syncedAttraction();
        $before = $attraction->wp_de_last_sync;
        $attraction->name = 'Neuer Name';
        $attraction->save();

        $this->assertFalse((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        $this->assertTrue($attraction->fresh()->wp_de_last_sync->equalTo($before));
        $this->assertTrue((new AttractionSyncService)->needsSync($attraction->fresh(), 'de'));
    }

    public function test_command_force_option_sends_unchanged_attractions(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $this->syncedAttraction();

        $this->artisan('attractions:sync-wordpress', ['--language' => 'de'])->assertSuccessful();
        Http::assertNothingSent();

        $this->artisan('attractions:sync-wordpress', ['--language' => 'de', '--force' => true])->assertSuccessful();
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT');
    }
}
```

Hinweis: Bei `Http::sequence()` in `test_changed_widget_…` ist der erste Eintrag für `getAttraction` (GET), der zweite für das `PUT`. Gleiches Muster in `test_failed_request_keeps_last_sync` (GET ok, PUT 500). In den Tests mit `Http::response(...)` bedient dieselbe Antwort GET und PUT. `WordPressApiService::executeWithRetry` wiederholt bei Fehlern; falls der 500er mehrfach abgefragt wird, `Http::response('error', 500)` statt `sequence()` für den PUT verwenden und GET separat über `Http::fake([... 'GET' …])` bedienen. Das beim Ausführen von Step 2/4 anhand der tatsächlichen Retry-Logik (`WordPressApiService.php`, `executeWithRetry`) anpassen.

- [ ] **Step 2: Tests laufen lassen, Fehlschlag prüfen**

Run: `php artisan test --compact tests/Feature/AttractionSyncDeltaTest.php`
Expected: FAIL (Parameter `force`, `needsSync` fehlen).

- [ ] **Step 3: Service anpassen**

`syncAttraction()` Signatur und Kopf:

```php
    public function syncAttraction(Attraction $attraction, string $language, bool $force = false): bool
    {
        try {
            $wpIdField = "wp_{$language}_id";
            $wpSyncField = "wp_{$language}_last_sync";
            $wpId = $attraction->$wpIdField;

            if (! $force && ! $this->needsSync($attraction, $language)) {
                return true;
            }

            $api = new WordPressApiService($language);
            $data = $this->mapAttractionToWordPress($attraction, $language, $wpId && ! $force);
```

Die bisherigen Zeilen `$api = …`, `$data = …` und die doppelte Deklaration von `$wpIdField/$wpSyncField/$wpId` entfallen (der Rest der Methode bleibt). Das abschließende Speichern ändern:

```php
            $attraction->$wpIdField = $result['id'];
            $attraction->$wpSyncField = now();
            $attraction->timestamps = false;
            $attraction->save();
            $attraction->timestamps = true;
```

`syncAllAttractions()`:

```php
    public function syncAllAttractions(string $language, bool $force = false): array
```
und im Schleifenaufruf `$this->syncAttraction($attraction, $language, $force)`.

Neue Methode:

```php
    /**
     * Whether the attraction changed since its last successful sync to the given language.
     */
    public function needsSync(Attraction $attraction, string $language): bool
    {
        $wpIdField = "wp_{$language}_id";
        $lastSync = $attraction->{"wp_{$language}_last_sync"};

        if (! $attraction->$wpIdField || $lastSync === null) {
            return true;
        }

        return $attraction->updated_at !== null && $attraction->updated_at->greaterThan($lastSync);
    }
```

`mapAttractionToWordPress()` Signatur `(Attraction $attraction, string $language, bool $onlyChangedWidgets = false): array`. Die beiden Sprach-Arrays werden auf Identitätsfelder reduziert und die Widgets über die Map ergänzt:

```php
        $attraction->loadMissing('city.country');
        $city = $attraction->city;
        $country = $city?->country;

        if ($language === 'en') {
            $data = [
                'name' => $attraction->en_name ?: $attraction->name,
                'city' => $city?->name_en ?: $city?->name_de,
                'country' => $country?->name_en ?: $country?->name_de,
            ];
        } else {
            $data = [
                'name' => $attraction->name,
                'city' => $city?->name_de ?: $city?->name_en,
                'country' => $country?->name_de ?: $country?->name_en,
            ];
        }

        $lastSync = $attraction->{"wp_{$language}_last_sync"};

        foreach (Attraction::widgetMap($language) as $field => $definition) {
            $changedAt = $attraction->{$definition['changed_at']};
            $isChanged = $lastSync === null || ($changedAt !== null && $changedAt->greaterThan($lastSync));

            if ($onlyChangedWidgets && ! $isChanged) {
                continue;
            }

            $data[$field] = $attraction->{$definition['html']};

            if ($onlyChangedWidgets && $changedAt !== null) {
                $data["{$field}_changed_at"] = $changedAt->copy()->utc()->toIso8601String();
            }
        }

        return array_filter($data, fn ($value): bool => $value !== null);
```

Die DocBlock-Zeile über der Methode bleibt; ergänzen: `@param bool $onlyChangedWidgets Send only widgets changed since the last sync, with their change timestamps`.

- [ ] **Step 4: Command anpassen**

In `SyncAttractionsToWordPress`:
- `$signature = 'attractions:sync-wordpress {--language=both : de, en, or both} {--force : Send all attractions and widgets, ignoring change detection}';`
- im Schleifenaufruf `$syncService->syncAttraction($location, $lang, (bool) $this->option('force'))`.

- [ ] **Step 5: Tests laufen lassen**

Run: `php artisan test --compact tests/Feature/AttractionSyncDeltaTest.php tests/Feature/AttractionConsoleCommandsTest.php`
Expected: PASS. Schlägt ein Test an der Retry-Logik fehl, Test gemäß Hinweis in Step 1 anpassen, nicht die Implementierung verbiegen.

- [ ] **Step 6: Formatieren und committen**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/AttractionSyncService.php app/Console/Commands/SyncAttractionsToWordPress.php tests/Feature/AttractionSyncDeltaTest.php
git commit -m "feat: Sync only changed attractions and widgets with change timestamps"
```

---

### Task 4: Laravel-Dokumentation

**Files:**
- Create: `docs/ARTIKELDATUM_UND_CACHE.md`

- [ ] **Step 1: Dokument schreiben** (Deutsch, Stil wie `docs/POSTS_UND_PAGES.md`)

Inhalt: Ziel; Datenfluss (Erkennung in `generateWidgets()`, `*_changed_at`, Delta-Regeln `needsSync()`/„geändert“, `--force`, warum `updated_at` nicht durch den Sync springt); API-Vertrag (`<feld>_changed_at`, ISO 8601 UTC, bei Create/`--force` weggelassen); Hinweis zu Bestandsdaten (NULL → keine Datumsänderung, einmal `locations:regenerate-widgets` ausführen, wenn gewünscht); Variante A für JSON-LD (Änderungen an Öffnungszeiten, Bewertung und Kontakt ändern auch das JSON-LD; dieses datiert keine Beiträge, leert aber den Cache aller Beiträge mit irgendeinem Widget dieser Attraktion, damit dort kein veraltetes JSON-LD ausgeliefert wird); Entscheidungstabelle aus der Spec; Verweis auf die Plugin-Doku.

- [ ] **Step 2: Committen**

```bash
git add docs/ARTIKELDATUM_UND_CACHE.md
git commit -m "docs: Document article date and cache handling"
```

---

## Teil B – Plugin-Repo (`/Users/patrick/Entwicklung/Herd/ENIKY/ENIKY__travelworker_wp_plugin`)

### Task 5: Testinfrastruktur für WP-unabhängige Klassen

**Files:**
- Create: `src/composer.json`, `src/phpunit.xml`, `src/tests/bootstrap.php`, `src/tests/SmokeTest.php`
- Modify: `src/.gitignore` (`vendor/` ergänzen)

**Interfaces:**
- Produces: `cd src && vendor/bin/phpunit` führt alle Tests unter `src/tests/` aus; `ABSPATH` ist im Bootstrap definiert, damit die Klassen-Guards `exit` nicht auslösen.

Vor diesem Task Patrick kurz fragen, ob `phpunit/phpunit` als Dev-Abhängigkeit im Plugin-Repo ok ist (Laravel nutzt PHPUnit 11; für das Plugin reicht PHPUnit 9, weil PHP 7.4 unterstützt bleiben soll).

- [ ] **Step 1: Dateien anlegen**

`src/composer.json`:

```json
{
  "name": "eniky/attraction-manager-workspace",
  "description": "Dev tooling for the Attraction Manager plugin",
  "type": "project",
  "require-dev": {
    "phpunit/phpunit": "^9.6"
  },
  "config": {
    "sort-packages": true
  }
}
```

`src/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

`src/tests/bootstrap.php`:

```php
<?php
/**
 * PHPUnit bootstrap for WordPress-independent classes.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$includes = __DIR__ . '/../attraction-manager/includes/';

foreach ( array( 'class-shortcode-matcher.php', 'class-widget-changes.php', 'interface-post-store.php', 'class-post-refresher.php' ) as $file ) {
	if ( file_exists( $includes . $file ) ) {
		require_once $includes . $file;
	}
}
```

`src/tests/SmokeTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase {

	public function test_phpunit_runs(): void {
		$this->assertTrue( defined( 'ABSPATH' ) );
	}
}
```

`src/.gitignore` um eine Zeile `vendor/` ergänzen.

- [ ] **Step 2: Installieren und laufen lassen**

Run: `cd src && composer install --no-interaction && vendor/bin/phpunit`
Expected: `OK (1 test, 1 assertion)`.

- [ ] **Step 3: Committen**

```bash
git add src/composer.json src/composer.lock src/phpunit.xml src/tests src/.gitignore
git commit -m "chore: Add PHPUnit for WordPress-independent classes"
```

---

### Task 6: `Shortcode_Matcher`

**Files:**
- Create: `src/attraction-manager/includes/class-shortcode-matcher.php`
- Test: `src/tests/ShortcodeMatcherTest.php`

**Interfaces:**
- Produces: `AttractionManager\Shortcode_Matcher::contains( string $text, array $tags, int $id ): bool` (static). Wahr, wenn `$text` einen der Shortcodes `$tags` mit exakt `id=$id` enthält.

- [ ] **Step 1: Failing Test**

```php
<?php

use AttractionManager\Shortcode_Matcher;
use PHPUnit\Framework\TestCase;

class ShortcodeMatcherTest extends TestCase {

	/**
	 * @dataProvider matching_provider
	 */
	public function test_matches( string $text ): void {
		$this->assertTrue( Shortcode_Matcher::contains( $text, array( 'attraction_hours' ), 42 ) );
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function matching_provider(): array {
		return array(
			'double quotes'       => array( '<p>[attraction_hours id="42"]</p>' ),
			'single quotes'       => array( "[attraction_hours id='42']" ),
			'unquoted'            => array( '[attraction_hours id=42]' ),
			'extra attrs after'   => array( '[attraction_hours id="42" class="x"]' ),
			'extra attrs before'  => array( '[attraction_hours class="x" id="42"]' ),
			'escaped quotes json' => array( '{"html":"[attraction_hours id=\\"42\\"]"}' ),
			'spaces around equal' => array( '[attraction_hours id = "42"]' ),
			'among others'        => array( '[attraction_contact id="7"] text [attraction_hours id="42"]' ),
		);
	}

	/**
	 * @dataProvider non_matching_provider
	 */
	public function test_does_not_match( string $text ): void {
		$this->assertFalse( Shortcode_Matcher::contains( $text, array( 'attraction_hours' ), 42 ) );
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function non_matching_provider(): array {
		return array(
			'prefix id'        => array( '[attraction_hours id="4"]' ),
			'longer id'        => array( '[attraction_hours id="420"]' ),
			'longer unquoted'  => array( '[attraction_hours id=421]' ),
			'other tag'        => array( '[attraction_contact id="42"]' ),
			'other id'         => array( '[attraction_hours id="7"]' ),
			'data-id attr'     => array( '[attraction_hours data-id="42"]' ),
			'id in next code'  => array( '[attraction_hours id="7"] [attraction_contact id="42"]' ),
			'plain text'       => array( 'attraction_hours id="42" ohne Klammern' ),
			'empty'            => array( '' ),
		);
	}

	public function test_any_of_several_tags_matches(): void {
		$tags = array( 'attraction_contact', 'attraction_hours' );

		$this->assertTrue( Shortcode_Matcher::contains( '[attraction_contact id="42"]', $tags, 42 ) );
		$this->assertFalse( Shortcode_Matcher::contains( '[attraction_rating id="42"]', $tags, 42 ) );
	}

	public function test_empty_tags_or_invalid_id_never_match(): void {
		$this->assertFalse( Shortcode_Matcher::contains( '[attraction_hours id="42"]', array(), 42 ) );
		$this->assertFalse( Shortcode_Matcher::contains( '[attraction_hours id="0"]', array( 'attraction_hours' ), 0 ) );
	}
}
```

- [ ] **Step 2: Fehlschlag prüfen**

Run: `cd src && vendor/bin/phpunit tests/ShortcodeMatcherTest.php`
Expected: FAIL / Error (`Class AttractionManager\Shortcode_Matcher not found`).

- [ ] **Step 3: Implementierung**

`src/attraction-manager/includes/class-shortcode-matcher.php`:

```php
<?php
/**
 * Shortcode matcher class.
 *
 * @package AttractionManager
 */

namespace AttractionManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds attraction shortcodes with an exact ID inside arbitrary text.
 */
class Shortcode_Matcher {

	/**
	 * Check whether the text contains one of the shortcodes with the given attraction ID.
	 *
	 * Handles double, single and missing quotes as well as backslash-escaped quotes
	 * as found in JSON or serialized meta values. The ID has to match exactly.
	 *
	 * @param string   $text Text to search (post content or meta value).
	 * @param string[] $tags Shortcode tags.
	 * @param int      $id   Attraction ID.
	 * @return bool
	 */
	public static function contains( $text, array $tags, $id ) {
		$id = (int) $id;

		if ( '' === $text || $id <= 0 || empty( $tags ) ) {
			return false;
		}

		$tag_pattern = implode(
			'|',
			array_map(
				function ( $tag ) {
					return preg_quote( $tag, '/' );
				},
				$tags
			)
		);

		$q          = '\\\\?';
		$id_pattern = '(?:' . $q . '"' . $id . $q . '"|' . $q . "'" . $id . $q . "'|" . $id . '(?!\w))';
		$pattern    = '/\[(?:' . $tag_pattern . ')(?=[\s\]])[^\]]*?(?<![\w-])id\s*=\s*' . $id_pattern . '[^\]]*\]/i';

		return 1 === preg_match( $pattern, $text );
	}
}
```

- [ ] **Step 4: Test laufen lassen**

Run: `cd src && vendor/bin/phpunit tests/ShortcodeMatcherTest.php`
Expected: PASS. Schlägt ein Fall fehl, die Regex anpassen, nicht den Testfall.

- [ ] **Step 5: Committen**

```bash
git add src/attraction-manager/includes/class-shortcode-matcher.php src/tests/ShortcodeMatcherTest.php
git commit -m "feat: Add shortcode matcher with exact attraction ID matching"
```

---

### Task 7: `Widget_Changes`

**Files:**
- Create: `src/attraction-manager/includes/class-widget-changes.php`
- Test: `src/tests/WidgetChangesTest.php`

**Interfaces:**
- Produces: `AttractionManager\Widget_Changes::parse( array $params ): array` mit Rückgabe `array{ date_tags: array<string, \DateTimeImmutable>, cache_tags: string[], invalid: string[] }`.
  - `date_tags`: Shortcode-Tag → spätester gültiger Zeitstempel (UTC). Nur sichtbare Widget-Felder (`widget_*_changed_at`) tragen dazu bei. Diese Beiträge bekommen ein neues `post_modified`.
  - `cache_tags`: Shortcode-Tags, deren Beiträge den Cache geleert bekommen. Enthält alle Tags aus `date_tags`; `json_ld_changed_at` ergänzt **alle vier** Tags, ändert aber kein Datum.
  - `invalid`: Felder mit nicht parsebarem Zeitstempel.

Variante A: Das JSON-LD datiert keine Beiträge, weil es nur im Footer steht und bei fast jeder Widget-Änderung mitwechselt. Der Cache aller Beiträge mit einem Widget der Attraktion wird trotzdem geleert, damit dort kein veraltetes JSON-LD ausgeliefert wird. Die Regel „Feld → Tags“ steht ausschließlich in den Konstanten `FIELD_TAGS` und `JSON_LD_TAGS`.

- [ ] **Step 1: Failing Test**

```php
<?php

use AttractionManager\Widget_Changes;
use PHPUnit\Framework\TestCase;

class WidgetChangesTest extends TestCase {

	public function test_widget_field_dates_and_clears_cache_for_its_shortcode_only(): void {
		$result = Widget_Changes::parse( array( 'widget_opening_hours_changed_at' => '2026-10-12T11:30:00+00:00' ) );

		$this->assertSame( array( 'attraction_hours' ), array_keys( $result['date_tags'] ) );
		$this->assertSame( '2026-10-12 11:30:00', $result['date_tags']['attraction_hours']->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( array( 'attraction_hours' ), $result['cache_tags'] );
		$this->assertSame( array(), $result['invalid'] );
	}

	public function test_json_ld_clears_cache_for_all_shortcodes_but_dates_nothing(): void {
		$result = Widget_Changes::parse( array( 'json_ld_changed_at' => '2026-10-12T11:30:00+00:00' ) );

		$this->assertSame( array(), $result['date_tags'] );
		$this->assertEqualsCanonicalizing(
			array( 'attraction_contact', 'attraction_rating', 'attraction_hours', 'attraction_accessibility' ),
			$result['cache_tags']
		);
	}

	public function test_widget_and_json_ld_together_date_only_the_widget(): void {
		$result = Widget_Changes::parse(
			array(
				'widget_opening_hours_changed_at' => '2026-10-12T11:30:00+00:00',
				'json_ld_changed_at'              => '2026-10-12T11:30:00+00:00',
			)
		);

		$this->assertSame( array( 'attraction_hours' ), array_keys( $result['date_tags'] ) );
		$this->assertCount( 4, $result['cache_tags'] );
		$this->assertCount( count( array_unique( $result['cache_tags'] ) ), $result['cache_tags'], 'no duplicate tags' );
	}

	public function test_latest_timestamp_wins_per_tag(): void {
		$result = Widget_Changes::parse(
			array(
				'widget_rating_changed_at' => '2026-10-12T09:00:00+00:00',
			)
		);
		$this->assertSame( '2026-10-12 09:00:00', $result['date_tags']['attraction_rating']->format( 'Y-m-d H:i:s' ) );

		$result = Widget_Changes::parse(
			array(
				'widget_rating_changed_at' => '2026-10-12T09:00:00+00:00',
				'widget_contact_changed_at' => '2026-10-12T11:00:00+00:00',
			)
		);
		$this->assertSame( '2026-10-12 09:00:00', $result['date_tags']['attraction_rating']->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( '2026-10-12 11:00:00', $result['date_tags']['attraction_contact']->format( 'Y-m-d H:i:s' ) );
	}

	public function test_converts_offsets_to_utc(): void {
		$result = Widget_Changes::parse( array( 'widget_contact_changed_at' => '2026-10-12T13:00:00+02:00' ) );

		$this->assertSame( '2026-10-12 11:00:00', $result['date_tags']['attraction_contact']->format( 'Y-m-d H:i:s' ) );
	}

	public function test_invalid_and_empty_values_are_ignored_and_reported(): void {
		$result = Widget_Changes::parse(
			array(
				'widget_contact_changed_at'       => 'kein datum',
				'widget_rating_changed_at'        => '',
				'widget_accessibility_changed_at' => array( 'x' ),
			)
		);

		$this->assertSame( array(), $result['date_tags'] );
		$this->assertSame( array(), $result['cache_tags'] );
		$this->assertSame( array( 'widget_contact', 'widget_accessibility' ), $result['invalid'] );
	}

	public function test_without_changed_at_nothing_is_touched(): void {
		$result = Widget_Changes::parse( array( 'widget_contact' => '<div></div>', 'name' => 'X' ) );

		$this->assertSame( array(), $result['date_tags'] );
		$this->assertSame( array(), $result['cache_tags'] );
		$this->assertSame( array(), $result['invalid'] );
	}
}
```

- [ ] **Step 2: Fehlschlag prüfen**

Run: `cd src && vendor/bin/phpunit tests/WidgetChangesTest.php`
Expected: Error (Klasse fehlt).

- [ ] **Step 3: Implementierung**

```php
<?php
/**
 * Widget changes class.
 *
 * @package AttractionManager
 */

namespace AttractionManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translates `<field>_changed_at` request parameters into affected shortcode tags.
 */
class Widget_Changes {

	/**
	 * Shortcode tag affected by a change of the given widget field.
	 * Posts embedding the shortcode get a new modified date and a fresh cache.
	 */
	const FIELD_TAGS = array(
		'widget_contact'       => 'attraction_contact',
		'widget_rating'        => 'attraction_rating',
		'widget_opening_hours' => 'attraction_hours',
		'widget_accessibility' => 'attraction_accessibility',
	);

	/**
	 * Shortcode tags whose posts print the JSON-LD of the attraction in the footer.
	 * A JSON-LD change clears their cache but does not change their modified date.
	 */
	const JSON_LD_TAGS = array( 'attraction_contact', 'attraction_rating', 'attraction_hours', 'attraction_accessibility' );

	/**
	 * Parse change timestamps from request parameters.
	 *
	 * @param array $params Request parameters.
	 * @return array{date_tags: array<string, \DateTimeImmutable>, cache_tags: string[], invalid: string[]}
	 */
	public static function parse( array $params ) {
		$date_tags  = array();
		$cache_tags = array();
		$invalid    = array();

		foreach ( self::FIELD_TAGS as $field => $tag ) {
			$changed_at = self::read_timestamp( $params, $field, $invalid );

			if ( null === $changed_at ) {
				continue;
			}

			if ( ! isset( $date_tags[ $tag ] ) || $changed_at > $date_tags[ $tag ] ) {
				$date_tags[ $tag ] = $changed_at;
			}

			$cache_tags[] = $tag;
		}

		if ( null !== self::read_timestamp( $params, 'json_ld', $invalid ) ) {
			$cache_tags = array_merge( $cache_tags, self::JSON_LD_TAGS );
		}

		return array(
			'date_tags'  => $date_tags,
			'cache_tags' => array_values( array_unique( $cache_tags ) ),
			'invalid'    => $invalid,
		);
	}

	/**
	 * Read and validate `<field>_changed_at` as UTC time.
	 *
	 * @param array    $params  Request parameters.
	 * @param string   $field   API field name.
	 * @param string[] $invalid Collects fields with invalid values.
	 * @return \DateTimeImmutable|null
	 */
	private static function read_timestamp( array $params, $field, array &$invalid ) {
		$key = $field . '_changed_at';

		if ( ! isset( $params[ $key ] ) || '' === $params[ $key ] ) {
			return null;
		}

		if ( ! is_string( $params[ $key ] ) ) {
			$invalid[] = $field;
			return null;
		}

		try {
			return ( new \DateTimeImmutable( $params[ $key ] ) )->setTimezone( new \DateTimeZone( 'UTC' ) );
		} catch ( \Exception $e ) {
			$invalid[] = $field;
			return null;
		}
	}
}
```

- [ ] **Step 4: Tests laufen lassen**

Run: `cd src && vendor/bin/phpunit tests/WidgetChangesTest.php`
Expected: PASS (7 Tests).

- [ ] **Step 5: Committen**

```bash
git add src/attraction-manager/includes/class-widget-changes.php src/tests/WidgetChangesTest.php
git commit -m "feat: Parse widget change timestamps into date and cache shortcode tags"
```

---

### Task 8: `Post_Store`, `Post_Refresher` und `Post_Repository`

**Files:**
- Create: `src/attraction-manager/includes/interface-post-store.php`
- Create: `src/attraction-manager/includes/class-post-refresher.php`
- Create: `src/attraction-manager/includes/class-post-repository.php`
- Test: `src/tests/PostRefresherTest.php`

**Interfaces:**
- Consumes: `Shortcode_Matcher::contains()` (Task 6); `date_tags` und `cache_tags` aus `Widget_Changes::parse()` (Task 7).
- Produces:
  - `interface Post_Store` mit `find_candidate_posts( array $tags, $attraction_id ): array` (Zeilen `array{post_id:int, text:string}`; ein Beitrag kann mehrfach vorkommen), `get_modified_gmt( $post_id ): string` (`Y-m-d H:i:s`, UTC), `set_modified( $post_id, \DateTimeImmutable $changed_at ): void`, `clear_cache( $post_id ): void`.
  - `Post_Refresher::__construct( Post_Store $store )`, `refresh( $attraction_id, array $date_tags, array $cache_tags ): array{affected: int[], dated: int[]}`. `affected` = alle Beiträge mit einem Shortcode aus `cache_tags` (Cache wird geleert); `dated` = Beiträge mit einem Shortcode aus `date_tags`, deren `post_modified` neu gesetzt wurde.
  - `Post_Repository implements Post_Store` (WordPress/`$wpdb`).

- [ ] **Step 1: Failing Test mit Fake-Store**

```php
<?php

use AttractionManager\Post_Refresher;
use AttractionManager\Post_Store;
use PHPUnit\Framework\TestCase;

class FakePostStore implements Post_Store {

	/** @var array<int, array{post_id:int, text:string}> */
	public $rows = array();

	/** @var array<int, string> */
	public $modified = array();

	/** @var int[] */
	public $cleared = array();

	/** @var array<int, string> */
	public $set = array();

	public function find_candidate_posts( array $tags, $attraction_id ) {
		return $this->rows;
	}

	public function get_modified_gmt( $post_id ) {
		return $this->modified[ $post_id ];
	}

	public function set_modified( $post_id, \DateTimeImmutable $changed_at ) {
		$this->set[ $post_id ] = $changed_at->format( 'Y-m-d H:i:s' );
	}

	public function clear_cache( $post_id ) {
		$this->cleared[] = $post_id;
	}
}

class PostRefresherTest extends TestCase {

	private function at( string $time ): \DateTimeImmutable {
		return new \DateTimeImmutable( $time, new \DateTimeZone( 'UTC' ) );
	}

	public function test_updates_date_and_cache_only_for_matching_posts(): void {
		$store           = new FakePostStore();
		$store->rows     = array(
			array( 'post_id' => 1, 'text' => '[attraction_hours id="42"]' ),
			array( 'post_id' => 2, 'text' => '[attraction_contact id="42"]' ),
			array( 'post_id' => 3, 'text' => '[attraction_hours id="420"]' ),
		);
		$store->modified = array( 1 => '2026-01-01 00:00:00', 2 => '2026-01-01 00:00:00', 3 => '2026-01-01 00:00:00' );

		$result = ( new Post_Refresher( $store ) )->refresh(
			42,
			array( 'attraction_hours' => $this->at( '2026-10-12 11:30:00' ) ),
			array( 'attraction_hours' )
		);

		$this->assertSame( array( 1 ), $result['affected'] );
		$this->assertSame( array( 1 ), $result['dated'] );
		$this->assertSame( array( 1 => '2026-10-12 11:30:00' ), $store->set );
		$this->assertSame( array( 1 ), $store->cleared );
	}

	public function test_json_ld_change_clears_cache_for_all_widget_posts_without_dating_them(): void {
		$store           = new FakePostStore();
		$store->rows     = array(
			array( 'post_id' => 1, 'text' => '[attraction_hours id="42"]' ),
			array( 'post_id' => 2, 'text' => '[attraction_contact id="42"]' ),
			array( 'post_id' => 3, 'text' => '[attraction_contact id="7"]' ),
		);
		$store->modified = array( 1 => '2026-01-01 00:00:00', 2 => '2026-01-01 00:00:00', 3 => '2026-01-01 00:00:00' );

		$result = ( new Post_Refresher( $store ) )->refresh(
			42,
			array( 'attraction_hours' => $this->at( '2026-10-12 11:30:00' ) ),
			array( 'attraction_hours', 'attraction_contact', 'attraction_rating', 'attraction_accessibility' )
		);

		$this->assertSame( array( 1, 2 ), $result['affected'] );
		$this->assertSame( array( 1 ), $result['dated'], 'only the post embedding the changed widget is dated' );
		$this->assertSame( array( 1 => '2026-10-12 11:30:00' ), $store->set );
		$this->assertSame( array( 1, 2 ), $store->cleared );
	}

	public function test_json_ld_only_change_dates_nothing(): void {
		$store           = new FakePostStore();
		$store->rows     = array( array( 'post_id' => 2, 'text' => '[attraction_contact id="42"]' ) );
		$store->modified = array( 2 => '2026-01-01 00:00:00' );

		$result = ( new Post_Refresher( $store ) )->refresh( 42, array(), array( 'attraction_contact' ) );

		$this->assertSame( array( 2 ), $result['affected'] );
		$this->assertSame( array(), $result['dated'] );
		$this->assertSame( array(), $store->set );
		$this->assertSame( array( 2 ), $store->cleared );
	}

	public function test_custom_field_hit_counts_and_post_is_handled_once(): void {
		$store           = new FakePostStore();
		$store->rows     = array(
			array( 'post_id' => 5, 'text' => 'Inhalt ohne Shortcode' ),
			array( 'post_id' => 5, 'text' => '{"x":"[attraction_hours id=\\"42\\"]"}' ),
			array( 'post_id' => 5, 'text' => '[attraction_hours id="42"]' ),
		);
		$store->modified = array( 5 => '2026-01-01 00:00:00' );

		$result = ( new Post_Refresher( $store ) )->refresh(
			42,
			array( 'attraction_hours' => $this->at( '2026-10-12 11:30:00' ) ),
			array( 'attraction_hours' )
		);

		$this->assertSame( array( 5 ), $result['affected'] );
		$this->assertSame( array( 5 ), $store->cleared );
		$this->assertSame( array( 5 => '2026-10-12 11:30:00' ), $store->set );
	}

	public function test_never_sets_modified_backwards_but_still_clears_cache(): void {
		$store           = new FakePostStore();
		$store->rows     = array( array( 'post_id' => 1, 'text' => '[attraction_hours id="42"]' ) );
		$store->modified = array( 1 => '2026-10-12 12:00:00' );

		$result = ( new Post_Refresher( $store ) )->refresh(
			42,
			array( 'attraction_hours' => $this->at( '2026-10-12 11:30:00' ) ),
			array( 'attraction_hours' )
		);

		$this->assertSame( array( 1 ), $result['affected'] );
		$this->assertSame( array(), $result['dated'] );
		$this->assertSame( array(), $store->set );
		$this->assertSame( array( 1 ), $store->cleared );
	}

	public function test_uses_latest_timestamp_among_matched_date_tags(): void {
		$store           = new FakePostStore();
		$store->rows     = array( array( 'post_id' => 1, 'text' => '[attraction_hours id="42"] [attraction_rating id="42"]' ) );
		$store->modified = array( 1 => '2026-01-01 00:00:00' );

		( new Post_Refresher( $store ) )->refresh(
			42,
			array(
				'attraction_hours'  => $this->at( '2026-10-12 09:00:00' ),
				'attraction_rating' => $this->at( '2026-10-12 11:00:00' ),
			),
			array( 'attraction_hours', 'attraction_rating' )
		);

		$this->assertSame( array( 1 => '2026-10-12 11:00:00' ), $store->set );
	}

	public function test_without_tags_nothing_happens(): void {
		$store       = new FakePostStore();
		$store->rows = array( array( 'post_id' => 1, 'text' => '[attraction_hours id="42"]' ) );

		$result = ( new Post_Refresher( $store ) )->refresh( 42, array(), array() );

		$this->assertSame( array( 'affected' => array(), 'dated' => array() ), $result );
		$this->assertSame( array(), $store->cleared );
	}
}
```

- [ ] **Step 2: Fehlschlag prüfen**

Run: `cd src && vendor/bin/phpunit tests/PostRefresherTest.php`
Expected: Error (Interface/Klasse fehlt).

- [ ] **Step 3: Interface und Refresher**

`includes/interface-post-store.php`:

```php
<?php
/**
 * Post store interface.
 *
 * @package AttractionManager
 */

namespace AttractionManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistence boundary used by the post refresher.
 */
interface Post_Store {

	/**
	 * Find published posts/pages whose content or custom fields mention one of the shortcode tags.
	 *
	 * @param string[] $tags          Shortcode tags.
	 * @param int      $attraction_id Attraction ID.
	 * @return array<int, array{post_id:int, text:string}>
	 */
	public function find_candidate_posts( array $tags, $attraction_id );

	/**
	 * Get the GMT modification date of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string Date as `Y-m-d H:i:s` in UTC.
	 */
	public function get_modified_gmt( $post_id );

	/**
	 * Set the modification date of a post.
	 *
	 * @param int                $post_id    Post ID.
	 * @param \DateTimeImmutable $changed_at New date (UTC).
	 */
	public function set_modified( $post_id, \DateTimeImmutable $changed_at );

	/**
	 * Clear caches (object cache and page cache) for a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache( $post_id );
}
```

`includes/class-post-refresher.php`:

```php
<?php
/**
 * Post refresher class.
 *
 * @package AttractionManager
 */

namespace AttractionManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updates modification date and cache of posts that embed changed widgets.
 */
class Post_Refresher {

	/**
	 * Post store.
	 *
	 * @var Post_Store
	 */
	private $store;

	/**
	 * Constructor.
	 *
	 * @param Post_Store $store Post store.
	 */
	public function __construct( Post_Store $store ) {
		$this->store = $store;
	}

	/**
	 * Refresh all posts that embed one of the changed widgets.
	 *
	 * Posts embedding a shortcode from `$date_tags` get a new modified date.
	 * Posts embedding a shortcode from `$cache_tags` get their cache cleared.
	 *
	 * @param int                                $attraction_id Attraction ID.
	 * @param array<string, \DateTimeImmutable> $date_tags     Shortcode tag => change time (UTC).
	 * @param string[]                           $cache_tags    Shortcode tags whose posts need a fresh cache.
	 * @return array{affected: int[], dated: int[]}
	 */
	public function refresh( $attraction_id, array $date_tags, array $cache_tags ) {
		$result = array(
			'affected' => array(),
			'dated'    => array(),
		);

		$cache_tags = array_values( array_unique( array_merge( $cache_tags, array_keys( $date_tags ) ) ) );

		if ( empty( $cache_tags ) ) {
			return $result;
		}

		$attraction_id = (int) $attraction_id;
		$latest        = array();
		$affected      = array();

		foreach ( $this->store->find_candidate_posts( $cache_tags, $attraction_id ) as $row ) {
			$post_id = (int) $row['post_id'];

			if ( Shortcode_Matcher::contains( $row['text'], $cache_tags, $attraction_id ) ) {
				$affected[ $post_id ] = true;
			}

			foreach ( $date_tags as $tag => $changed_at ) {
				if ( ! Shortcode_Matcher::contains( $row['text'], array( $tag ), $attraction_id ) ) {
					continue;
				}

				if ( ! isset( $latest[ $post_id ] ) || $changed_at > $latest[ $post_id ] ) {
					$latest[ $post_id ] = $changed_at;
				}
			}
		}

		$utc = new \DateTimeZone( 'UTC' );

		foreach ( array_keys( $affected ) as $post_id ) {
			if ( isset( $latest[ $post_id ] ) ) {
				$current = new \DateTimeImmutable( $this->store->get_modified_gmt( $post_id ), $utc );

				if ( $latest[ $post_id ] > $current ) {
					$this->store->set_modified( $post_id, $latest[ $post_id ] );
					$result['dated'][] = $post_id;
				}
			}

			$this->store->clear_cache( $post_id );
			$result['affected'][] = $post_id;
		}

		return $result;
	}
}
```

- [ ] **Step 4: Tests laufen lassen**

Run: `cd src && vendor/bin/phpunit`
Expected: PASS (alle Tests).

- [ ] **Step 5: `Post_Repository` (WordPress-abhängig, nicht unit-getestet)**

`includes/class-post-repository.php`:

```php
<?php
/**
 * Post repository class.
 *
 * @package AttractionManager
 */

namespace AttractionManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress implementation of the post store.
 */
class Post_Repository implements Post_Store {

	/**
	 * Post types that are searched.
	 */
	const POST_TYPES = array( 'post', 'page' );

	/**
	 * Find published posts/pages whose content or custom fields mention one of the shortcode tags.
	 *
	 * @param string[] $tags          Shortcode tags.
	 * @param int      $attraction_id Attraction ID (exact matching happens in Shortcode_Matcher).
	 * @return array<int, array{post_id:int, text:string}>
	 */
	public function find_candidate_posts( array $tags, $attraction_id ) {
		global $wpdb;

		$like_args = array();

		foreach ( $tags as $tag ) {
			$like_args[] = '%' . $wpdb->esc_like( '[' . $tag ) . '%';
		}

		$types_sql = implode( ',', array_fill( 0, count( self::POST_TYPES ), '%s' ) );

		$content_likes = implode( ' OR ', array_fill( 0, count( $tags ), 'p.post_content LIKE %s' ) );
		$meta_likes    = implode( ' OR ', array_fill( 0, count( $tags ), 'm.meta_value LIKE %s' ) );

		$content_sql = "SELECT p.ID AS post_id, p.post_content AS text FROM {$wpdb->posts} p
			WHERE p.post_status = 'publish' AND p.post_type IN ($types_sql) AND ($content_likes)";

		$meta_sql = "SELECT m.post_id AS post_id, m.meta_value AS text FROM {$wpdb->postmeta} m
			INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
			WHERE p.post_status = 'publish' AND p.post_type IN ($types_sql) AND ($meta_likes)";

		$args = array_merge( self::POST_TYPES, $like_args );

		$content_rows = $wpdb->get_results( $wpdb->prepare( $content_sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$meta_rows    = $wpdb->get_results( $wpdb->prepare( $meta_sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array_merge( (array) $content_rows, (array) $meta_rows );
	}

	/**
	 * Get the GMT modification date of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function get_modified_gmt( $post_id ) {
		global $wpdb;

		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT post_modified_gmt FROM {$wpdb->posts} WHERE ID = %d", $post_id )
		);

		return $value ? $value : '1970-01-01 00:00:00';
	}

	/**
	 * Set the modification date of a post without triggering save hooks or revisions.
	 *
	 * @param int                $post_id    Post ID.
	 * @param \DateTimeImmutable $changed_at New date (UTC).
	 */
	public function set_modified( $post_id, \DateTimeImmutable $changed_at ) {
		global $wpdb;

		$gmt   = $changed_at->format( 'Y-m-d H:i:s' );
		$local = get_date_from_gmt( $gmt );

		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => $local,
				'post_modified_gmt' => $gmt,
			),
			array( 'ID' => $post_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Clear the WordPress object cache and the WP Rocket page cache for a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache( $post_id ) {
		clean_post_cache( $post_id );

		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $post_id );
		}
	}
}
```

- [ ] **Step 6: Committen**

```bash
git add src/attraction-manager/includes/interface-post-store.php src/attraction-manager/includes/class-post-refresher.php src/attraction-manager/includes/class-post-repository.php src/tests/PostRefresherTest.php
git commit -m "feat: Date posts with changed widgets and clear cache for all attraction posts"
```

---

### Task 9: REST-Anbindung

**Files:**
- Modify: `src/attraction-manager/attraction-manager.php` (`load_dependencies()`)
- Modify: `src/attraction-manager/includes/class-rest-api.php` (`update_attraction()`, `get_endpoint_args_for_item_schema()`, neue Methode `refresh_posts()`)

**Interfaces:**
- Consumes: `Widget_Changes::parse()`, `Post_Refresher`, `Post_Repository`.
- Produces: `PUT /attraction-manager/v1/attractions/{id}` akzeptiert `widget_contact_changed_at`, `widget_rating_changed_at`, `widget_opening_hours_changed_at`, `widget_accessibility_changed_at`, `json_ld_changed_at`; die Antwort enthält zusätzlich `updated_posts` (int[]: alle Beiträge, deren Cache geleert wurde; nicht nur die neu datierten). Fehler in der Beitragsaktualisierung führen nie zu einem Fehlerstatus.

- [ ] **Step 1: Dateien laden**

In `load_dependencies()` nach der Zeile für `class-structured-data.php` ergänzen:

```php
		require_once EN_ATTRACTION_MANAGER_PLUGIN_DIR . 'includes/class-shortcode-matcher.php';
		require_once EN_ATTRACTION_MANAGER_PLUGIN_DIR . 'includes/class-widget-changes.php';
		require_once EN_ATTRACTION_MANAGER_PLUGIN_DIR . 'includes/interface-post-store.php';
		require_once EN_ATTRACTION_MANAGER_PLUGIN_DIR . 'includes/class-post-refresher.php';
		require_once EN_ATTRACTION_MANAGER_PLUGIN_DIR . 'includes/class-post-repository.php';
```

- [ ] **Step 2: Argumente für `PUT` registrieren**

In `get_endpoint_args_for_item_schema( $required = true )` vor `return $args;` ergänzen:

```php
		if ( ! $required ) {
			foreach ( array( 'widget_contact', 'widget_rating', 'widget_opening_hours', 'widget_accessibility', 'json_ld' ) as $field ) {
				$args[ $field . '_changed_at' ] = array(
					'description' => __( 'ISO 8601 time of the last content change; used to update dependent posts.', 'attraction-manager' ),
					'type'        => 'string',
				);
			}
		}
```

- [ ] **Step 3: `update_attraction()` erweitern**

Nach dem erfolgreichen `$this->database->update( $id, $data )` und vor dem Laden von `$attraction` einfügen und die Antwort ergänzen. Der Abschnitt ab `$attraction = $this->database->get( $id );` lautet danach:

```php
		$updated_posts = $this->refresh_posts( $id, $request->get_params() );

		$attraction = $this->database->get( $id );

		$this->log_api_request( 'PUT', "/attractions/{$id}", $data, array( 'updated_posts' => $updated_posts ) );

		$attraction->updated_posts = $updated_posts;

		return rest_ensure_response( $attraction );
```

Neue private Methode (vor `log_api_request()`):

```php
	/**
	 * Refresh modified date and cache of posts that embed changed widgets.
	 *
	 * Never lets an error break the attraction update.
	 *
	 * @param int   $attraction_id Attraction ID.
	 * @param array $params        Request parameters.
	 * @return int[] IDs of affected posts.
	 */
	private function refresh_posts( $attraction_id, array $params ) {
		try {
			$parsed = Widget_Changes::parse( $params );

			if ( ! empty( $parsed['invalid'] ) ) {
				error_log( sprintf( 'Attraction Manager: ignored invalid changed_at for %s (attraction %d).', implode( ', ', $parsed['invalid'] ), $attraction_id ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			if ( empty( $parsed['cache_tags'] ) ) {
				return array();
			}

			$result = ( new Post_Refresher( new Post_Repository() ) )->refresh( $attraction_id, $parsed['date_tags'], $parsed['cache_tags'] );

			return $result['affected'];
		} catch ( \Throwable $e ) {
			error_log( sprintf( 'Attraction Manager: refreshing posts failed for attraction %d: %s', $attraction_id, $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			return array();
		}
	}
```

- [ ] **Step 4: Unit-Tests und Syntax prüfen**

Run: `cd src && vendor/bin/phpunit && for f in attraction-manager/attraction-manager.php attraction-manager/includes/*.php; do php -l "$f"; done`
Expected: alle Tests PASS, `No syntax errors detected` für jede Datei.

- [ ] **Step 5: Manuelle Verifikation gegen eine lokale WordPress-Instanz**

Voraussetzung: Plugin aktiv, ein Beitrag mit `[attraction_hours id="<ID>"]` im Inhalt, ein zweiter nur mit `[attraction_contact id="<ID>"]`, ein dritter mit dem Shortcode in einem Custom Field.

```bash
curl -s -u "<user>:<application-password>" -X PUT \
  -H "Content-Type: application/json" \
  -d '{"widget_opening_hours":"<div>neue Zeiten</div>","widget_opening_hours_changed_at":"2026-10-12T11:30:00+00:00"}' \
  "https://<wp-host>/wp-json/attraction-manager/v1/attractions/<ID>"
```

Expected: Antwort enthält `"updated_posts":[<ID Beitrag 1>,<ID Beitrag 3>]`; in der Datenbank haben nur diese Beiträge `post_modified_gmt = 2026-10-12 11:30:00` (und `post_modified` in Seitenzeit); Beitrag 2 ist unverändert. Bei aktivem WP Rocket ist die gecachte Datei der Beiträge 1 und 3 verschwunden. Ein zweiter identischer Request ändert `post_modified` nicht (nie rückwärts, gleiche Zeit).

Zweiter Request, nur JSON-LD:

```bash
curl -s -u "<user>:<application-password>" -X PUT \
  -H "Content-Type: application/json" \
  -d '{"json_ld":"<script type=\"application/ld+json\">{}</script>","json_ld_changed_at":"2026-10-12T12:30:00+00:00"}' \
  "https://<wp-host>/wp-json/attraction-manager/v1/attractions/<ID>"
```

Expected: `updated_posts` enthält die Beiträge 1, 2 und 3 (alle mit einem Widget dieser Attraktion), aber **keiner** bekommt ein neues `post_modified` (bleibt bei 11:30:00); bei aktivem WP Rocket ist der Cache aller drei geleert.

- [ ] **Step 6: Committen**

```bash
git add src/attraction-manager/attraction-manager.php src/attraction-manager/includes/class-rest-api.php
git commit -m "feat: Update posts and clear cache when widgets change via REST API"
```

---

### Task 10: Dokumentation

**Files:**
- Modify (Plugin-Repo): `src/attraction-manager/README-LARAVEL.md` (Abschnitt „API“/„Update“ um `<feld>_changed_at`, `updated_posts` und Ablauf ergänzen), `README.md` des Plugins falls dort die API beschrieben ist
- Modify (Laravel-Repo): `docs/ARTIKELDATUM_UND_CACHE.md` (Verweis auf die Plugin-Seite, Abgleich mit dem tatsächlichen Verhalten aus der manuellen Verifikation)

- [ ] **Step 1: Plugin-Doku ergänzen** — API-Vertrag (Felder, Format, Wirkung; `widget_*_changed_at` datiert und leert den Cache, `json_ld_changed_at` leert nur den Cache), Suche in `post_content` und `wp_postmeta`, Regeln (`publish`, nie rückwärts, kein `wp_update_post`), WP-Rocket-Verhalten, Test-Befehl `cd src && vendor/bin/phpunit`.
- [ ] **Step 2: Laravel-Doku abgleichen** und Ergebnis der manuellen Verifikation (Task 9, Step 5) kurz festhalten.
- [ ] **Step 3: Je Repo committen**

```bash
git add src/attraction-manager/README-LARAVEL.md
git commit -m "docs: Document change timestamps and post refresh in the API"
```

```bash
git add docs/ARTIKELDATUM_UND_CACHE.md
git commit -m "docs: Align article date documentation with plugin behavior"
```

---

## Reihenfolge und Release

1. Teil B (Plugin) zuerst deployen: Es ignoriert fehlende `*_changed_at` und ist damit abwärtskompatibel zum alten Laravel-Sync.
2. Danach Teil A (Laravel) deployen und `php artisan migrate` ausführen.
3. Bestandsdaten: Einmal `php artisan locations:regenerate-widgets` laufen lassen, falls die Widget-HTML mit `changed_at` befüllt werden soll. Beim ersten Lauf gilt jede HTML als geändert; danach `php artisan attractions:sync-wordpress --force` (ohne Datumsänderung) bzw. normal, wenn die Beiträge einmalig ein neues Datum bekommen sollen. Das vorher mit Patrick abstimmen, weil es sonst alle Beiträge mit Widgets einmal „aktualisiert“.

## Spec-Abdeckung (Selbstcheck)

| Spec-Abschnitt | Task |
|---|---|
| Laravel: `*_changed_at`-Spalten | 1 |
| Laravel: Erkennung mit Normalisierung, erster Lauf | 2 |
| Laravel: Delta-Sync, nur geänderte Widgets, `--force`, `last_sync` nur nach Erfolg, Neuanlage ohne `changed_at` | 3 |
| Plugin: API-Felder, Zeitstempel nicht gespeichert, fehlende Zeitstempel → nichts | 7, 9 |
| Plugin: Beiträge finden (`post_content`, Meta, exakte ID, nur `publish`) | 6, 8 |
| Plugin: `post_modified` direkt, nie rückwärts, Cache, kein Fehler ohne WP Rocket | 8 |
| Nachtrag Variante A: JSON-LD datiert nicht, leert aber den Cache aller Beiträge mit einem Widget der Attraktion | 7, 8, 9 |
| Plugin: Log, `updated_posts` in der Antwort | 9 |
| Fehlerbehandlung (Save scheitert nie, ungültige Zeitstempel) | 7, 9 |
| Tests beider Repos | 1–3, 5–8 |
| Dokumentation | 4, 10 |

Abweichungen von der Spec: (1) Variante A für JSON-LD (siehe Nachtrag in der Spec). (2) Der Plan ergänzt in Task 3 die Regel `updated_at > last_sync` (und speichert im Sync ohne Timestamps), damit Umbenennungen und andere Nicht-Widget-Änderungen trotz Delta-Sync übertragen werden. Die Spec ließ das offen.
