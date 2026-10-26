# Artikeldatum und Cache bei Widget-Änderungen – Design

Stand: 2026-10-12 · Status: freigegeben, Nachtrag „Variante A“ eingearbeitet

Betrifft beide Repos: Laravel-App (`ENIKY__travelworker`) und WordPress-Plugin (`ENIKY__travelworker_wp_plugin`, `src/attraction-manager`).

## Ziel

Ändert sich der Inhalt eines Attraktions-Widgets (z. B. Öffnungszeiten), soll

1. das WordPress-Änderungsdatum (`post_modified`, `post_modified_gmt`) **nur der Beiträge und Seiten** aktualisiert werden, in denen dieses Widget tatsächlich eingebunden ist,
2. der WP-Rocket-Cache genau dieser Beiträge geleert werden.

Reine Neu-Syncs ohne inhaltliche Änderung dürfen nichts verändern.

## Ausgangslage

- Attraktionen liegen in Laravel (`Attraction`) und werden per `AttractionSyncService` über die Plugin-REST-API in die Tabelle `wp_attractions` übertragen. Pro Sprache (de/en) gibt es einen eigenen WP-Datensatz (`wp_de_id`, `wp_en_id`).
- Redakteure binden Widgets per Shortcode ein: `attraction_contact`, `attraction_rating`, `attraction_hours`, `attraction_accessibility` (Attribut `id`). Shortcodes stehen in `post_content` und in Custom Fields (`wp_postmeta`) der Beiträge.
- JSON-LD wird im Footer jeder Seite ausgegeben, die mindestens ein Widget der Attraktion rendert.
- Heute wird nach jedem Sync `wp_*_last_sync` gesetzt und `save()` aufgerufen; `updated_at` springt dadurch bei jedem Sync und taugt nicht als Änderungsdatum.
- `Attraction::generateWidgets()` erzeugt alle HTML-Spalten neu. Die Öffnungszeiten hängen von `now()` ab (Saisonauswahl), die HTML kann sich also ohne Redaktionsänderung ändern.
- Das Plugin setzt heute weder `post_modified` noch leert es einen Cache.

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

## Laravel

### Änderungszeitpunkte

Neue nullable Timestamp-Spalten an `attractions`, je Widget und Sprache:

- `contact_info_changed_at`, `rating_changed_at`, `opening_hours_changed_at`, `accessibility_changed_at`, `structured_data_changed_at`
- dieselben mit Präfix `en_`

`generateWidgets()` vergleicht jede neu erzeugte HTML mit dem bisherigen Wert (nach Normalisierung von Leerraum zwischen Tags und Zeilenenden) und setzt das zugehörige `*_changed_at` auf `now()`, nur wenn sie abweicht. Beim ersten Lauf (altes `*_html` leer) wird ebenfalls `now()` gesetzt.

### Delta-Sync

- Ein Datensatz wird nur gesendet, wenn für die Sprache ein `*_changed_at` neuer ist als `wp_{lang}_last_sync` oder `wp_{lang}_last_sync` leer ist.
- Pro Datensatz werden nur geänderte Widgets gesendet, jeweils mit `widget_*_changed_at` (ISO 8601, UTC).
- Der Artisan-Befehl `attractions:sync-wordpress` erhält `--force`: alle Datensätze und alle Widgets werden gesendet, `changed_at` wird dann weggelassen (kein Datums-Update).
- `last_sync` wird weiterhin nur nach erfolgreichem Request gesetzt; Fehlschläge werden beim nächsten Lauf erneut gesendet.
- Neuanlage in WordPress (`createAttraction`) sendet alle Widgets ohne `changed_at`.

### Anpassungen

- `AttractionSyncService::mapAttractionToWordPress()` filtert nach geänderten Widgets und ergänzt `*_changed_at`.
- `SyncAttractionsToWordPress` wählt die Datensätze für den Delta-Sync aus.
- Admin-Vorschau (`AttractionResource`) ist nicht betroffen, weil Markup und Klassennamen unverändert bleiben.

## Plugin

### API-Vertrag

`PUT /attractions/{id}` akzeptiert zusätzlich optionale Felder:
`widget_contact_changed_at`, `widget_rating_changed_at`, `widget_opening_hours_changed_at`, `widget_accessibility_changed_at`, `json_ld_changed_at`.

Die Zeitstempel werden **nicht** in `wp_attractions` gespeichert; sie steuern nur die Aktualisierung der Beiträge. Fehlt ein Zeitstempel, passiert nichts an Beiträgen oder Cache.

### Betroffene Beiträge finden

Pro übermitteltem `*_changed_at`:

| Feld | Shortcode |
|---|---|
| `widget_contact` | `attraction_contact` |
| `widget_rating` | `attraction_rating` |
| `widget_opening_hours` | `attraction_hours` |
| `widget_accessibility` | `attraction_accessibility` |
| `json_ld` | alle vier, **nur Cache, kein Datum** (siehe unten) |

**Variante A für JSON-LD (Nachtrag):** Das JSON-LD enthält u. a. Öffnungszeiten und Bewertung und ändert sich daher bei fast jeder Widget-Änderung. Würde es Beiträge datieren, bekäme jeder Beitrag mit irgendeinem Widget der Attraktion ein neues Datum. Deshalb gilt:

- Nur `widget_*_changed_at` setzen `post_modified`, und zwar nur bei Beiträgen mit dem passenden Shortcode.
- `json_ld_changed_at` leert den Cache aller Beiträge mit irgendeinem der vier Shortcodes dieser Attraktion, ändert aber kein Datum. Sonst würde dort veraltetes JSON-LD aus dem Cache ausgeliefert.
- Eine Widget-Änderung leert zusätzlich den Cache der Beiträge mit dem geänderten Widget (diese werden ohnehin datiert).

- Suche in `wp_posts.post_content` und `wp_postmeta.meta_value`.
- Erkannt werden die Schreibweisen `id="42"`, `id='42'` und `id=42`. Die ID muss exakt passen (`id="4"` trifft nicht `id="42"`), z. B. per Regex nach der SQL-Vorauswahl mit `LIKE`.
- Berücksichtigt werden Beiträge und Seiten mit Status `publish` (keine Entwürfe, keine Revisionen).
- Treffer aus `wp_postmeta` werden über `post_id` auf den Beitrag abgebildet; Revisionen und Nicht-Veröffentlichte werden gefiltert.

### Aktion pro Beitrag

1. Nur bei Beiträgen mit einem geänderten **Widget**: `post_modified` und `post_modified_gmt` direkt per `$wpdb->update` auf das mitgeschickte `changed_at` setzen, nur wenn dieses neuer ist als der aktuelle Wert (nie rückwärts). Kein `wp_update_post()`, damit keine Revisionen und keine `save_post`-Hooks anderer Plugins ausgelöst werden.
2. Für alle betroffenen Beiträge (Widget- und JSON-LD-Änderungen): `clean_post_cache( $post_id )`.
3. Wenn `rocket_clean_post` existiert: `rocket_clean_post( $post_id )` (leert Beitrag, zugehörige Archive und Startseite). Ohne WP Rocket kein Fehler.

### Log

Ein Eintrag im vorhandenen API-Log (`log_api_request`) pro Update: Attraktions-ID, geänderte Widgets, betroffene Post-IDs.

### Antwort

Die Antwort enthält zusätzlich `updated_posts` (Liste der Post-IDs), damit Laravel sie loggen kann (optional auswertbar).

## Fehlerbehandlung

- Fehler bei der Beitragssuche oder beim Cache-Leeren dürfen das Speichern der Attraktion nicht scheitern lassen; sie werden geloggt, die API antwortet weiter mit Erfolg.
- Ungültige Zeitstempel werden ignoriert und geloggt.

## Tests

**Laravel (PHPUnit, `tests/Feature`)**
- Unveränderte Daten: `*_changed_at` bleibt unverändert, Datensatz wird im Delta-Sync nicht gesendet.
- Änderung der Öffnungszeiten: `opening_hours_changed_at` (de/en) und `structured_data_changed_at` werden gesetzt (das JSON-LD enthält die Zeiten), Kontakt-, Bewertungs- und Barrierefreiheits-Widget bleiben unverändert und werden nicht gesendet.
- Normalisierung: reine Leerraumunterschiede gelten nicht als Änderung.
- `--force` sendet alles ohne `changed_at`.
- Fehlgeschlagener Sync lässt `last_sync` unverändert.

**Plugin**
- Betroffene Beiträge je Widget-Typ; `json_ld` leert den Cache aller vier Shortcodes, datiert aber keinen Beitrag.
- Beitrag mit nur `[attraction_contact]` bei Änderung der Öffnungszeiten: Datum bleibt, Cache wird geleert.
- Fundorte `post_content` und Custom Field.
- ID-Präfix-Falle (`4` vs. `42`), alle Anführungszeichen-Varianten.
- Entwürfe und Revisionen werden ignoriert.
- `post_modified` wird nicht rückwärts gesetzt.
- `rocket_clean_post` wird aufgerufen, wenn vorhanden, und fehlt es, gibt es keinen Fehler.
- Fehlende `*_changed_at` ändern nichts.

Ist im Plugin keine WP-Test-Suite eingerichtet, wird die Erkennungs- und Suchlogik in eine eigene, WordPress-unabhängig testbare Klasse gelegt; dafür gilt die Einrichtung der Tests als Teil des Plans.

## Nicht Teil dieses Designs

- Sichtbare „Zuletzt aktualisiert“-Anzeige im Theme.
- Verknüpfte Beiträge (Relationship-Felder); Custom Fields werden nur direkt am Beitrag durchsucht.
- Asynchrone Verarbeitung (Action Scheduler); bei Bedarf später, die Erkennung bleibt dabei gleich.
- Neu-Laden des Caches (Preload) über das Leeren hinaus.

## Dokumentation

Nach der Umsetzung wird der Datenfluss (Erkennung in Laravel, Delta-Sync, Aktualisierung im Plugin, Cache) in `docs/` festgehalten, mit Begründung der Entscheidungen aus der Tabelle oben.
