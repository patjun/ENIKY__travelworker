# Artikeldatum und Cache bei Widget-Änderungen

## Übersicht

Ändert sich der Inhalt eines Attraktions-Widgets (z. B. die Öffnungszeiten), soll

1. das WordPress-Änderungsdatum (`post_modified`, `post_modified_gmt`) **nur der Beiträge und Seiten** aktualisiert werden, die dieses Widget einbinden,
2. der WP-Rocket-Cache genau dieser Beiträge geleert werden.

Reine Neu-Syncs ohne inhaltliche Änderung verändern nichts.

Die Erkennung passiert in Laravel, die Aktualisierung der Beiträge und des Caches im WordPress-Plugin. Die Plugin-Seite ist in der Plugin-Dokumentation beschrieben (Plugin-Repo, `src/attraction-manager/README-LARAVEL.md`); sie ist dort die Referenz. Die Beschreibung der Plugin-Seite weiter unten entspricht der Spezifikation (`docs/superpowers/specs/2026-10-12-artikeldatum-und-cache-design.md`).

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

### Warum `updated_at` nicht durch den Sync springt

Nach einem erfolgreichen Request setzt der Sync `wp_{lang}_id` und `wp_{lang}_last_sync` und speichert mit `$attraction->timestamps = false`. So wird `updated_at` nicht angefasst. Sonst wäre jeder Sync selbst eine „Änderung“, und der Vergleich `updated_at > last_sync` würde nie zur Ruhe kommen.

## API-Vertrag

`PUT /attractions/{id}` des Plugins erhält zusätzlich optionale Felder:

`widget_contact_changed_at`, `widget_rating_changed_at`, `widget_opening_hours_changed_at`, `widget_accessibility_changed_at`, `json_ld_changed_at`

- Format: ISO 8601, UTC (z. B. `2026-10-12T08:30:00+00:00`).
- Wird nur zusammen mit dem jeweiligen Widget gesendet und nur, wenn das `*_changed_at` in Laravel gesetzt ist.
- Bei Neuanlage und bei `--force` werden die Felder weggelassen.
- Das Plugin speichert die Zeitstempel nicht in `wp_attractions`; sie steuern nur die Aktualisierung von Beiträgen und Cache. Fehlt ein Zeitstempel, passiert an Beiträgen und Cache nichts.

## Bestandsdaten

Bei bestehenden Attraktionen sind die `*_changed_at` zunächst `NULL`. Das bedeutet: keine Datumsänderung, solange sich nichts ändert (ein `NULL`-Wert gilt im Delta-Sync nicht als „geändert“). Soll der Ausgangszustand einmal sauber erfasst werden, `php artisan locations:regenerate-widgets` ausführen. Beim ersten Lauf gilt eine leere bisherige HTML als Änderung; bereits befüllte HTML wird nur bei echter Abweichung neu datiert.

## JSON-LD (Variante A)

Das JSON-LD enthält u. a. Öffnungszeiten, Bewertung und Kontakt und ändert sich deshalb bei fast jeder Widget-Änderung. Würde es Beiträge datieren, bekäme jeder Beitrag mit irgendeinem Widget der Attraktion ein neues Datum. Deshalb gilt:

- Nur `widget_*_changed_at` setzen `post_modified`, und zwar nur bei Beiträgen mit dem passenden Shortcode.
- `json_ld_changed_at` leert den Cache aller Beiträge mit irgendeinem der vier Widgets dieser Attraktion, datiert aber nicht. Sonst würde dort veraltetes JSON-LD aus dem Cache ausgeliefert.
- Eine Widget-Änderung leert zusätzlich den Cache der Beiträge mit dem geänderten Widget (diese werden ohnehin datiert).

## Plugin-Seite (laut Spezifikation)

- Betroffene Beiträge: Veröffentlichte Beiträge und Seiten, in deren `post_content` oder `wp_postmeta.meta_value` der passende Shortcode (`attraction_contact`, `attraction_rating`, `attraction_hours`, `attraction_accessibility`) mit exakt dieser ID steht (`id="42"`, `id='42'`, `id=42`; `id="4"` trifft nicht `42`). Entwürfe und Revisionen werden ignoriert.
- Datum: `post_modified` und `post_modified_gmt` werden direkt per `$wpdb->update` auf das gesendete `changed_at` gesetzt, nie rückwärts. Kein `wp_update_post()`, damit keine Revisionen und keine `save_post`-Hooks anderer Plugins ausgelöst werden.
- Cache: `clean_post_cache()` und, falls vorhanden, `rocket_clean_post()` je betroffenem Beitrag. Ohne WP Rocket kein Fehler.
- Fehler bei Suche oder Cache-Leeren lassen das Speichern der Attraktion nicht scheitern. Die Antwort enthält zusätzlich `updated_posts` (Post-IDs).

Details und Stand der Umsetzung: Plugin-Dokumentation `src/attraction-manager/README-LARAVEL.md` im Plugin-Repo.

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
