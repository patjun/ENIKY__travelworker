# Preis-Widget Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Jeder Standort bekommt ein eigenes, aufklappbares Preis-Widget mit Shortcode `[attraction_prices id="…"]`, gepflegt in Filament und per Claude-Button übersetzbar.

**Architecture:** Laravel speichert die Preise als JSON am `Attraction`-Modell und erzeugt die Widget-HTML pro Sprache über den vorhandenen `widgetMap()`-Ablauf. Das Plugin speichert und gibt sie wie die anderen Widgets aus. Den aktuellen Bereich öffnet `widgets.js` im Browser, damit Saisonwechsel weder alte HTML hinterlassen noch Artikeldaten verschieben.

**Tech Stack:** Laravel 12, Filament 3, Livewire 3, PHPUnit 11, WordPress-Plugin (PHP, SCSS, Vanilla JS), Claude über `ClaudeService`.

**Spec:** `docs/superpowers/specs/2026-10-31-preis-widget-design.md`

## Global Constraints

- Zwei Repos, getrennte Branches und PRs: Laravel `feature/price-widget` von `develop`, Plugin `feature/price-widget` von `origin/main`.
- Plugin nur in einem Worktree bearbeiten: `git -C ../ENIKY__travelworker_wp_plugin worktree add ../ENIKY__travelworker_wp_plugin-prices -b feature/price-widget origin/main`. Das Arbeitsverzeichnis des Plugin-Repos enthält unkommittierte 1.3.0-Änderungen und `src/_backup/`, beides bleibt unangetastet.
- Plugin-Version `1.4.0`.
- Ausrollen: erst Plugin, dann Laravel. Das Plugin-Update spielt Patrick manuell ein, Claude deployt nicht.
- Keine neuen Abhängigkeiten. JS-Tests laufen mit `node --test`.
- Laravel-Spalten: ohne Präfix Deutsch, `en_` Englisch. Klassen mit Präfix `am-price-`, Bereiche mit `am-season` und `am-season-toggle`.
- Texte mit `e()` ausgeben.
- Label `Preise` / `Prices`, Titel `Was kostet es?` / `What does it cost?`, kostenlos `gratis` / `free`.
- Währungen EUR, CZK, CHF, GBP, PLN, HUF, Vorgabe EUR. Symbol bei EUR und GBP, sonst ISO-Code. Zwei Nachkommastellen, außer CZK und HUF (ganzzahlig).
- Laravel: nach Änderungen `vendor/bin/pint --dirty --format agent`, Tests mit `php artisan test --compact <Datei>`.

## Review Focus

1. Eine Saison wird im Formular wieder entfernt, nachdem sie gespeichert war: Der Bereich darf danach keine Saison mehr haben. Test in Task 4.
2. Claude liefert unvollständiges oder falsch aufgebautes JSON, oder die Antwort wird abgeschnitten: Das Formular bleibt unverändert, es erscheint eine Fehlermeldung. Test in Task 5.
3. Zeile ohne Betrag und ohne „kostenlos“ oder Bereich ohne Zeilen, etwa aus halb gefüllten Repeatern: Das Formular lehnt fehlende Beträge ab, das Widget lässt solche Einträge aus und gibt ohne verwertbare Zeile nichts aus. Tests in Task 3 und 4.
4. Betrag kommt als String (`"3.70"`, `"0"`) aus dem Formular: Die Darstellung ist dieselbe wie bei Zahlen, `0` ohne „kostenlos“ wird als Betrag gezeigt, nicht als „gratis“. Test in Task 3.
5. Saison über den Jahreswechsel (`11-01` bis `02-28`) im Januar und im Dezember: Der Bereich ist offen. Test in Task 2.

---

### Task 1: Plugin speichert und gibt `widget_prices` aus

**Repo:** Plugin-Worktree

**Files:**
- Modify: `src/attraction-manager/includes/class-database.php` (Spalte in `CREATE TABLE`, Defaults, Insert-Formate, `sanitize_data()`)
- Modify: `src/attraction-manager/includes/class-rest-api.php` (Parameter, Feldliste, Schema, `_changed_at`-Liste)
- Modify: `src/attraction-manager/includes/class-shortcodes.php`
- Modify: `src/attraction-manager/includes/class-widget-changes.php`
- Modify: `src/attraction-manager/admin/views/list.php`, `src/attraction-manager/admin/views/list-page.php`
- Modify: `src/attraction-manager/attraction-manager.php`, `src/attraction-manager/README.md`, `src/attraction-manager/README-LARAVEL.md`, `src/attraction-manager/CLAUDE.md`
- Test: `src/tests/WidgetChangesTest.php`

**Interfaces:**
- Produces: REST-Felder `widget_prices` und `widget_prices_changed_at`, Shortcode `attraction_prices`.

- [ ] **Step 1: Failing test ergänzen.** In `WidgetChangesTest`:

```php
public function test_prices_widget_dates_only_its_own_shortcode(): void {
	$result = Widget_Changes::parse( array( 'widget_prices_changed_at' => '2026-10-12T11:30:00+00:00' ) );

	$this->assertSame( array( 'attraction_prices' ), array_keys( $result['date_tags'] ) );
	$this->assertSame( array( 'attraction_prices' ), $result['cache_tags'] );
}
```

Außerdem im vorhandenen Test `test_json_ld_clears_cache_for_all_shortcodes_but_dates_nothing` `attraction_prices` zur erwarteten Liste hinzufügen.

- [ ] **Step 2: Test laufen lassen.** `cd src && composer install && vendor/bin/phpunit --filter WidgetChangesTest`. Erwartet: FAIL.

- [ ] **Step 3: Implementieren.** In `FIELD_TAGS` `'widget_prices' => 'attraction_prices'` und in `JSON_LD_TAGS` `'attraction_prices'` ergänzen. An jeder Stelle, an der `widget_accessibility` in den oben genannten Dateien steht, `widget_prices` analog ergänzen, mit `grep -rn "widget_accessibility\|attraction_accessibility" src/attraction-manager` als Prüfliste. Spalte `widget_prices LONGTEXT`. In `create()` das Format-Array und die Reihenfolge von `sanitize_data()` synchron halten. Shortcode `prices_shortcode()` ruft `render_widget( $atts['id'], 'widget_prices' )` wie `hours_shortcode()`. In `list.php` Button „Prices“, in `list-page.php` den Shortcode aufführen.

- [ ] **Step 4: Version setzen.** Header `Version:` und `EN_ATTRACTION_MANAGER_VERSION` auf `1.4.0`, Changelog in `README.md`. `README-LARAVEL.md` und `CLAUDE.md` um Feld und Shortcode ergänzen.

- [ ] **Step 5: Tests laufen lassen.** `vendor/bin/phpunit`. Erwartet: alle PASS. `php -l` für alle geänderten PHP-Dateien ohne Fehler.

- [ ] **Step 6: Commit.** `git add -A src/attraction-manager src/tests && git commit -m "feat: Add prices widget field and shortcode"`

### Task 2: Plugin-Stile und aktueller Bereich im Browser

**Repo:** Plugin-Worktree

**Files:**
- Modify: `src/attraction-manager/assets/css/widgets.scss`, `src/attraction-manager/assets/css/widgets.css`
- Modify: `src/attraction-manager/assets/js/widgets.js`
- Modify: `src/package.json` (Script `"test:js": "node --test tests/"`)
- Test: `src/tests/widgets-season.test.js`

**Interfaces:**
- Consumes: Markup aus Task 3: `.am-prices` enthält `details.am-season`, optional mit `data-season-start` und `data-season-end` im Format `MM-DD`.
- Produces: Klassen `am-price-rows`, `am-price-row`, `am-price-label`, `am-price-amount`, `am-price-intro`, `am-price-footer`, `am-price-meta`.

- [ ] **Step 1: Failing test schreiben.** `widgets.js` exportiert unter Node `isInSeason` und `todayKey`.

```js
const test = require( 'node:test' );
const assert = require( 'node:assert' );
const { isInSeason, todayKey } = require( '../attraction-manager/assets/js/widgets.js' );

test( 'season inside one year', () => {
	assert.equal( isInSeason( '07-15', '04-01', '10-31' ), true );
	assert.equal( isInSeason( '03-31', '04-01', '10-31' ), false );
} );

test( 'season boundaries are included', () => {
	assert.equal( isInSeason( '04-01', '04-01', '10-31' ), true );
	assert.equal( isInSeason( '10-31', '04-01', '10-31' ), true );
} );

test( 'season across new year', () => {
	assert.equal( isInSeason( '01-15', '11-01', '02-28' ), true );
	assert.equal( isInSeason( '12-15', '11-01', '02-28' ), true );
	assert.equal( isInSeason( '07-15', '11-01', '02-28' ), false );
} );

test( 'today key is zero padded', () => {
	assert.equal( todayKey( new Date( 2026, 0, 5 ) ), '01-05' );
} );
```

- [ ] **Step 2: Test laufen lassen.** `cd src && npm run test:js`. Erwartet: FAIL, `isInSeason` fehlt.

- [ ] **Step 3: JS implementieren.** In der IIFE von `widgets.js`:
  - `todayKey( date )` und `isInSeason( today, start, end )` als String-Vergleich: bei `start <= end` im Bereich, sonst `today >= start || today <= end`.
  - `openCurrentSeason( widget )` sucht den ersten `.am-season[data-season-start][data-season-end]`, dessen Saison heute läuft. Trifft einer zu, verliert jeder `.am-season` des Widgets `open`, und der Treffer bekommt `open`. Sonst bleibt alles unverändert.
  - `init()` ruft das für alle `.am-prices` auf. DOM-Zugriffe laufen nur, wenn `document` existiert.
  - Am Ende: `if ( typeof module !== 'undefined' && module.exports ) { module.exports = { isInSeason: isInSeason, todayKey: todayKey }; }`.

- [ ] **Step 4: Test laufen lassen.** `npm run test:js`. Erwartet: PASS.

- [ ] **Step 5: Stile ergänzen.** Die Regeln `.am-season + .am-season` und `.am-season-toggle` aus `.am-hours` in einen gemeinsamen Block `.am-hours, .am-prices` ziehen. `.am-prices` bekommt `padding-bottom: 18px`. `.am-price-row` als Flex-Zeile wie `.am-day` mit `justify-content: space-between`, `.am-price-amount` mit `white-space: nowrap`, `font-weight: 600` und `font-variant-numeric: tabular-nums`. `.am-price-intro` und `.am-price-footer` wie `.am-rating .am-text`. `.am-price-meta` als Flex-Zeile mit Abstand und Farbe `--am-muted-2`. Dann `npm run build:css`.

- [ ] **Step 6: Commit.** `git add -A src/attraction-manager/assets src/tests src/package.json && git commit -m "feat: Style prices widget and open the current season"`

- [ ] **Step 7: Plugin-PR erstellen.** Push und PR gegen `main`, nicht mergen.

### Task 3: Preisdaten und Widget-HTML in Laravel

**Repo:** Laravel

**Files:**
- Create: Migration per `php artisan make:migration add_prices_to_attractions_table --no-interaction`
- Modify: `app/Models/Attraction.php`, `app/Models/AttractionBlock.php`
- Modify: `database/factories/AttractionFactory.php` (State `withPrices()`)
- Create: `tests/Feature/AttractionPricesWidgetTest.php`

**Interfaces:**
- Produces:
  - Spalten: `prices` (json), `price_currency` (string 3, Default `EUR`), `price_intro`, `en_price_intro`, `price_footer`, `en_price_footer` (text), `price_valid_from` (date), `price_ticket_url` (string), `prices_html`, `en_prices_html` (text), `prices_changed_at`, `en_prices_changed_at` (timestamp). Alle außer `price_currency` nullable.
  - Struktur `prices`: `list<array{name_de: ?string, name_en: ?string, start_date: ?string, end_date: ?string, rows: list<array{id: string, label_de: ?string, label_en: ?string, amount: float|string|null, is_free: bool}>}>`.
  - `Attraction::generatePricesHtml($language = 'de'): ?string`
  - `widgetMap()`-Eintrag `widget_prices`.
  - Beim Speichern erhält jede Zeile ohne `id` eine UUID.

- [ ] **Step 1: Branch und Spec.** `git switch -c feature/price-widget develop`. Spec und Plan committen: `git add docs/superpowers && git commit -m "docs: Add price widget design and plan"`.

- [ ] **Step 2: Failing tests schreiben.** Test-Klasse nach dem Muster von `AttractionWidgetHtmlTest`. Tests mit Literalen:

```php
public function test_german_widget_shows_sections_and_formatted_prices(): void
// Devín-Beispiel: assertStringContainsString für 'class="am-widget am-prices"', 'Was kostet es?', 'Wintersaison', 'Erwachsene', '6,00 €'
public function test_english_widget_uses_english_format(): void
// '€6.00', 'What does it cost?', 'Winter season'
public function test_czk_has_no_decimals_and_iso_code(): void
// amount 250, de: '250 CZK', en: 'CZK 250'
public function test_chf_keeps_two_decimals(): void
// amount 12, de: '12,00 CHF', en: 'CHF 12.00'
public function test_free_row_shows_gratis_instead_of_amount(): void
// de 'gratis', en 'free', kein '0,00'
public function test_amount_as_string_and_zero_are_formatted_as_amounts(): void
// amount '3.70' => '3,70 €', amount '0' mit is_free false => '0,00 €'
public function test_missing_translation_falls_back_to_other_language(): void
// label_de 'Erwachsene', label_en null, en-Widget enthält 'Erwachsene'; umgekehrt ebenso
public function test_widget_is_empty_without_usable_rows(): void
// prices null, [], Bereich ohne rows, Zeile ohne Betrag und ohne is_free; jeweils null, auch mit price_intro
public function test_row_without_amount_is_skipped_but_section_stays(): void
public function test_first_section_is_open_and_only_complete_seasons_have_data_attributes(): void
// assertSame(1, substr_count($html, '<details class="am-season" open')), data-season-start="11-01", data-season-end="02-28"; Bereich nur mit start_date hat keines der Attribute
public function test_intro_footer_valid_from_and_ticket_link_are_rendered(): void
// 'am-price-intro', 'am-price-footer', 'Stand: 01.09.2026', en 'As of Sep 1, 2026', href und 'Online-Tickets' / 'Online tickets'
public function test_meta_line_is_absent_without_valid_from_and_ticket_url(): void
public function test_texts_are_escaped(): void
// name_de '<script>x</script> & Co' => '&lt;script&gt;', kein '<script>'
public function test_multiline_intro_keeps_line_breaks(): void
public function test_price_change_sets_changed_at_and_unchanged_prices_do_not(): void
// generateWidgets(), save, setTestNow, generateWidgets(): unverändert; Betrag ändern, generateWidgets(): prices_changed_at neu
public function test_sync_sends_prices_widget_with_changed_at(): void
// nach Muster von AttractionSyncDeltaTest: PUT enthält widget_prices und widget_prices_changed_at
public function test_clearing_prices_sends_an_empty_widget(): void
// PUT enthält Schlüssel widget_prices mit ''
public function test_saving_adds_ids_to_new_rows_and_keeps_existing_ones(): void
public function test_attraction_block_renders_prices_after_opening_hours(): void
```

- [ ] **Step 3: Tests laufen lassen.** `php artisan test --compact tests/Feature/AttractionPricesWidgetTest.php`. Erwartet: FAIL.

- [ ] **Step 4: Migration und Modell.** Spalten wie oben, `prices => array`, `price_valid_from => date`, `*_changed_at => datetime`, alle Felder in `fillable`. `widgetMap()` um `widget_prices` nach `widget_opening_hours` ergänzen, mit `generator` `generatePricesHtml`. In `booted()` ein `saving`-Callback, das fehlende `rows[].id` mit `(string) Str::uuid()` ergänzt und `prices` nur bei tatsächlich ergänzter ID neu setzt.

- [ ] **Step 5: `generatePricesHtml()` implementieren.** Nutzt `renderWidgetHead('prices', …)`. Private Helfer `formatPrice(mixed $amount, string $language): string` und `priceText(array $item, string $key, string $language): ?string` für den Sprach-Rückfall. Aufbau:

```html
<section class="am-widget am-prices">
  <div class="am-label">Preise</div>
  <h3 class="am-title">Was kostet es?</h3>
  <p class="am-price-intro">…</p>
  <details class="am-season" open data-season-start="11-01" data-season-end="02-28">
    <summary class="am-season-toggle"><span>Wintersaison</span></summary>
    <div class="am-price-rows">
      <div class="am-price-row"><span class="am-price-label">Erwachsene</span><span class="am-price-amount">6,00 €</span></div>
    </div>
  </details>
  <p class="am-price-footer">…</p>
  <div class="am-price-meta"><span>Stand: 01.09.2026</span><a class="am-link" href="…" target="_blank" rel="noopener">Online-Tickets</a></div>
</section>
```

Formatierung: de `3,70 €`, `250 CZK`, `12,00 CHF`; en `€3.70`, `CZK 250`, `CHF 12.00`. Zeile ohne verwertbaren Preis auslassen, Bereich ohne Zeilen auslassen, das erste ausgegebene `details` trägt `open`. Bereich ohne Titel in beiden Sprachen: Titel `Preise` / `Prices`. Einleitung und Fußtext mit `nl2br(e(…))`.

- [ ] **Step 6: `AttractionBlock` ergänzen.** Preis-Widget nach den Öffnungszeiten, nach demselben Muster.

- [ ] **Step 7: Tests laufen lassen.** Dieselbe Datei plus `tests/Feature/AttractionSyncDeltaTest.php`, `AttractionWidgetHtmlTest.php`, `ListicleTest.php`. Erwartet: PASS.

- [ ] **Step 8: Commit.** `feat: Generate prices widget from attraction data`

### Task 4: Pflege im Filament-Formular

**Repo:** Laravel

**Files:**
- Modify: `app/Filament/Resources/AttractionResource.php`
- Test: `tests/Feature/AttractionResourceTest.php`

**Interfaces:**
- Consumes: Spalten und Struktur aus Task 3.
- Produces: `Forms\Components\Section::make('Prices')->key('prices-section')` als Ziel für die Aktionen aus Task 5. Repeater `prices` mit verschachteltem Repeater `rows`.

- [ ] **Step 1: Failing tests schreiben.** Mit dem Muster `Livewire::test(EditAttraction::class, …)->fillForm([...])->call('save')`:

```php
public function test_prices_can_be_saved_and_widget_is_generated(): void
// fillForm prices mit einem Bereich und einer Zeile; assertHasNoFormErrors; fresh()->prices_html enthält '3,70 €', rows[0].id ist gesetzt
public function test_row_needs_amount_unless_it_is_free(): void
// amount leer, is_free false => assertHasFormErrors; is_free true, amount leer => keine Fehler
public function test_negative_amount_is_rejected(): void
public function test_ticket_url_must_be_a_url(): void
public function test_prices_section_keeps_row_ids_when_editing(): void
public function test_removing_season_months_clears_the_season(): void
// Datensatz mit start_date '11-01' und end_date '02-28'; ->set('data.prices.<key>.start_month', null); save; start_date ist null und Widget ohne data-season-start
```

- [ ] **Step 2: Tests laufen lassen.** Erwartet: FAIL.

- [ ] **Step 3: Sektion bauen.** Nach „Opening Hours“: collapsible, `columnSpanFull`.
  - Felder `price_currency` (Select, Pflicht, Default `EUR`), `price_valid_from` (DatePicker), `price_ticket_url` (`->url()`), `price_intro`, `en_price_intro`, `price_footer`, `en_price_footer` (Textarea).
  - Repeater `prices`: `name_de`, `name_en`, Saisonfelder, Repeater `rows` mit Hidden `id`, `label_de`, `label_en`, `amount` (`->numeric()->minValue(0)`, `->required(fn (Get $get): bool => ! $get('is_free'))`), Toggle `is_free` (`->live()`).
  - `reorderable`, `collapsible`, `itemLabel` aus dem Titel.

- [ ] **Step 4: Saisonfelder.** Optionale Selects `start_month`, `start_day`, `end_month`, `end_day` wie bei den Öffnungszeiten, mit denselben Optionen, `->dehydrated(false)` und `reactive`, aber ohne `required` und ohne Überschneidungsprüfung. Monats- und Tagesoptionen in statische Methoden ziehen und in beiden Sektionen nutzen. Neuer Helfer:

```php
protected static function updateOptionalSeasonDate(Get $get, Set $set, string $prefix): void
// setzt "{$prefix}_date" auf "MM-DD", wenn Monat und Tag gesetzt sind, sonst auf null
```

Hidden `start_date` und `end_date` hydrieren die Selects wie bei den Öffnungszeiten.

- [ ] **Step 5: Vorschau.** In beiden Sprach-Tabs einen Placeholder für `prices_html` bzw. `en_prices_html` nach dem Muster der Öffnungszeiten (Zeile 93 und 147). Die CSS-Kopie in Zeile 47 durch den Inhalt der aus Task 2 gebauten `widgets.css` ersetzen.

- [ ] **Step 6: Tests laufen lassen.** `php artisan test --compact tests/Feature/AttractionResourceTest.php`. Erwartet: PASS.

- [ ] **Step 7: Commit.** `feat: Maintain prices in the attraction form`

### Task 5: Übersetzungsbutton

**Repo:** Laravel

**Files:**
- Modify: `app/Services/ClaudeService.php`
- Create: `app/Filament/Actions/TranslatePricesAction.php`
- Modify: `app/Filament/Resources/AttractionResource.php`
- Create: `tests/Feature/AttractionPricesTranslationTest.php`

**Interfaces:**
- Consumes: `prices-section` und Formularfelder aus Task 4.
- Produces:
  - `ClaudeService::translatePrices(array $texts, string $sourceLanguage, string $targetLanguage): array`, gleiche Form wie die Eingabe, sonst `\RuntimeException`.
  - `TranslatePricesAction::make(string $targetLanguage): Action`, Aktionsnamen `translatePricesToEn` und `translatePricesToDe`.
  - Form von `$texts`: `array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}`.

- [ ] **Step 1: Failing tests schreiben.** `Http::fake` für `api.anthropic.com/*` mit `['content' => [['text' => json_encode($translated)]]]`, `config(['ai.anthropic.api_key' => 'test'])`. Aufruf über `callFormComponentAction('prices-section', 'translatePricesToEn')`.

```php
public function test_german_texts_are_translated_into_the_english_fields(): void
public function test_english_texts_are_translated_into_the_german_fields(): void
public function test_translation_uses_unsaved_form_state(): void
// fillForm ändert label_de, Request an Claude enthält den neuen Text
public function test_translation_does_not_save_the_record(): void
public function test_amounts_dates_and_ids_stay_unchanged(): void
public function test_source_language_empty_text_is_not_sent_as_translation_target(): void
// Quelle leer => Zielfeld bleibt, wie es war
public function test_invalid_response_leaves_form_unchanged_and_shows_error(): void
// Antworttext 'kein json'
public function test_response_with_other_structure_leaves_form_unchanged(): void
// sections mit weniger Einträgen
public function test_connection_error_leaves_form_unchanged_and_shows_error(): void
// Http::fake wirft ConnectionException: executeWithRetry wiederholt nur RequestException, der Test wartet also nicht
```

- [ ] **Step 2: Tests laufen lassen.** Erwartet: FAIL.

- [ ] **Step 3: `ClaudeService` erweitern.** Den HTTP-Teil aus `generateIntro()` in `private function sendMessage(string $prompt, int $maxTokens): string` ziehen, `generateIntro()` nutzt ihn unverändert mit `$settings->max_tokens`. `translatePrices()` nutzt `max($settings->max_tokens, 4000)`, weil die Vorgabe 1000 lange Preislisten abschneiden würde. Der Prompt verlangt ausschließlich JSON in derselben Form, Eigennamen bleiben erhalten. Antwort decodieren, Form rekursiv gegen die Eingabe prüfen.

- [ ] **Step 4: Aktion bauen.** Nach dem Muster von `GenerateIntroWithAiAction`, Beschriftungen `Nach Englisch übersetzen` und `Nach Deutsch übersetzen`. Statische Methoden:

```php
public static function extractTexts(array $state, string $language): array
public static function applyTranslation(array $state, array $translated, string $language): array
```

Beide erhalten die Repeater-Schlüssel, deshalb Abgleich über die Position (`array_values`), nicht über `id`: Neue Zeilen haben im Formular noch keine ID. Die Aktion liest `prices`, Einleitung und Fußtext per `Get`, schreibt per `Set` nur die Zielfelder und speichert nichts. `requiresConfirmation` nur, wenn die Zielsprache schon Text enthält. Sind in der Quellsprache keine Texte vorhanden, Warnung statt Aufruf. Fehler als `Notification::make()->danger()`.

- [ ] **Step 5: In `AttractionResource` einhängen.** `->headerActions([TranslatePricesAction::make('en'), TranslatePricesAction::make('de')])` an `prices-section`.

- [ ] **Step 6: Tests laufen lassen.** Neue Datei plus `tests/Feature/ListicleResourceTest.php` und `AiSettingsPageTest.php`. Erwartet: PASS.

- [ ] **Step 7: Commit.** `feat: Translate price texts with Claude`

### Task 6: Doku und Abschlussprüfung

**Repo:** Laravel, danach beide

**Files:**
- Modify: `docs/ARTIKELDATUM_UND_CACHE.md`

- [ ] **Step 1: Doku ergänzen.** Tabelle der Widgets (Zeile 28), Feldliste (Zeile 59), Shortcode-Liste (Zeile 102), dazu ein Abschnitt, warum der aktuelle Bereich im Browser geöffnet wird.

- [ ] **Step 2: Laravel prüfen.** `vendor/bin/pint --dirty --format agent` und `php artisan test --compact`. Erwartet: alle PASS.

- [ ] **Step 3: Commit und PR.** `docs: Document prices widget`, Push, PR gegen `develop`, nicht mergen. Im PR steht: erst das Plugin ausrollen.

- [ ] **Step 4: Lokale Prüfung über beide Repos.** Der Symlink der lokalen Seite zeigt auf das Plugin-Arbeitsverzeichnis. Für die Prüfung auf den Worktree umhängen und danach zurücksetzen, den ursprünglichen Ziel-Pfad vorher notieren. Der Pfad der Seite steht in Herd.
  - `wp eval` auf `vonortzuort.test`: `wp_kses` mit `wp_kses_allowed_html( 'post' )` lässt die erzeugte Widget-HTML samt `open` und `data-season-*` unverändert.
  - Laden einer Admin-Seite legt über `check_version()` die Spalte `widget_prices` an, `wp db query "SHOW COLUMNS …"` bestätigt sie. Die Spalte bleibt nach dem Zurückhängen bestehen und stört die 1.3.0 nicht.
  - Einen Standort mit Devín-Daten per `attractions:sync-wordpress --language=de` synchronisieren, Shortcode in einen Testbeitrag setzen.
  - Im Browser mit `/browse` prüfen: Aussehen, Sommer-Bereich öffnet sich bei passendem Datum, Aufklappen funktioniert. Testbeitrag danach entfernen.
  - Sync läuft gegen die lokale Seite, nie gegen Live: `config('wordpress.de.api_url')` vorher prüfen.
