# Preis-Widget für Standorte

Asana: [Erweitern der Standorte um Eintrittspreise](https://app.asana.com/1/515785379340232/project/1206977577894215/task/1220053123973821)

## Absicht

Leser sollen im Artikel auf einen Blick sehen, was ein Standort kostet. Das Widget zeigt keine vollständige Preisliste, sondern eine kurze, von Hand gewählte Auswahl. Die Standseilbahn Como hat rund 20 Tarife, im Widget stehen vielleicht vier bis fünf.

Die Preise bei Como und Burg Devín haben denselben Aufbau: benannte Bereiche, darin links die Beschreibung und rechts der Preis. Die Bereiche bedeuten aber Unterschiedliches (Ticketarten oder Saisons) und sind deshalb frei benannt.

Dieses Vorhaben ist das erste von zweien. Das zweite prüft die Preis-URLs (`website_pricing`, `en_website_pricing`) automatisch und ändert Preise erst nach Freigabe. Es bekommt ein eigenes Konzept und setzt auf den Daten dieses Widgets auf. Das Datenmodell muss deshalb einzelne Preise eindeutig ansprechbar halten, baut aber keine Teile der Prüfung vorweg.

## Ziele

- Eigenes Widget mit eigenem Shortcode `[attraction_prices id="…"]`, im Stil der vorhandenen Widgets.
- Pflege im Filament-Formular des Standorts, in beliebiger Sprache beginnend.
- Übersetzung per Button in die jeweils andere Sprache.
- Eine Preisänderung wirkt wie bei den Öffnungszeiten: Artikeldatum und Cache werden über den vorhandenen Ablauf aktualisiert.

## Nicht-Ziele

- Automatische Preisprüfung, KI-Auswertung der Preis-URLs und Freigabe-Ablauf (zweites Vorhaben).
- Preise in den strukturierten Daten (JSON-LD). `priceRange` bleibt, wie es ist.
- Vollständige Tariflisten, Hinweise pro Bereich, Rabattlogik, Umrechnung von Währungen.

## Aussehen (Entscheidungen von Patrick)

Entwurf B: aufklappbar wie die Öffnungszeiten.

- Bereiche sind aufklappbar, darin Zeilen mit Beschreibung links und Preis rechts.
- Über den Bereichen steht ein optionaler Einleitungstext, darunter ein optionaler Fußtext. Einen Hinweis pro Bereich gibt es nicht.
- Saisondaten sind optional. Sind sie gepflegt, ist der aktuelle Bereich offen, sonst der erste.
- Es ist immer genau ein Bereich beim Laden offen. Die übrigen lassen sich aufklappen.

Reihenfolge im Widget: Label, Titel, Einleitungstext, Bereiche, Fußtext, dann eine Zeile mit Stand-Datum und Ticket-Link. Ohne Stand-Datum und Ticket-Link entfällt diese Zeile.

## Daten (Vorschläge von Claude, noch nicht ausdrücklich bestätigt)

- **Bereich:** Titel je Sprache, optional Saison-Start und -Ende.
- **Zeile:** Beschreibung je Sprache, Betrag als Zahl oder Schalter „kostenlos“.
- **Standort:** Währung, Einleitungstext je Sprache, Fußtext je Sprache, optionales Stand-Datum, optionaler Ticket-Link.

Der Preis existiert nur einmal, die Darstellung folgt der Sprache (`3,70 €` / `€3.70`). „Kostenlos“ wird als „gratis“ / „free“ angezeigt. Der Titel des Widgets ist fest: Label „Preise“ / „Prices“, Titel „Was kostet es?“ / „What does it cost?“.

Ohne Bereiche mit mindestens einer Zeile gibt das Widget nichts aus, auch wenn Einleitungs- oder Fußtext gepflegt sind.

### Calls von Claude (noch nicht einzeln besprochen)

- **Speicherung:** Eine JSON-Spalte `prices` für die Bereiche, analog zu `manual_opening_hours`, jeweils mit `name_de`, `name_en` und `rows` aus `label_de`, `label_en`, `amount`, `is_free`. Dazu `price_currency`, `price_intro`, `en_price_intro`, `price_footer`, `en_price_footer`, `price_valid_from`, `price_ticket_url`.
- **Stabile IDs:** Jede Zeile bekommt beim Anlegen eine UUID in `rows[].id`, die beim Bearbeiten erhalten bleibt. Das zweite Vorhaben spricht Preise darüber an. Sonst hat die ID keine Funktion.
- **Ein Ticket-Link für beide Sprachen.**
- **Währungen:** Auswahl aus EUR, CZK, CHF, GBP, PLN, HUF, Vorgabe EUR. Eine Währung pro Standort. EUR und GBP zeigen das Symbol, die übrigen den ISO-Code.
- **Nachkommastellen:** EUR, CHF, GBP und PLN immer mit zwei Stellen, CZK und HUF ganzzahlig.
- **Fehlende Übersetzung:** Fehlt ein Text in einer Sprache, zeigt das Widget die andere Sprache, wie bei `en_name ?: name`.
- **Saisondaten:** gleiches Format und gleiche Eingabe wie bei den Öffnungszeiten (`m-d`), Saisons dürfen über den Jahreswechsel gehen. Beide Daten sind nötig, damit ein Bereich als Saison zählt.

## Aktueller Bereich: im Browser, nicht beim Erzeugen

Die Widget-HTML entsteht nur beim Speichern oder per `RegenerateLocationWidgets`, nicht täglich. Ein beim Erzeugen gesetztes `open` würde deshalb veralten: Devín bliebe nach dem letzten Speichern im Winter auch im Juli auf den Winterpreisen stehen. Automatisches Neuerzeugen zum Saisonwechsel würde dagegen `prices_changed_at` setzen und Artikeldaten verschieben, obwohl sich kein Preis geändert hat.

Deshalb:

- Laravel erzeugt immer den ersten Bereich mit `open`.
- Jeder Bereich mit Saison trägt `data-season-start` und `data-season-end`.
- Das Plugin-JS, das auch „Heute“ bei den Öffnungszeiten setzt, öffnet beim Laden den Bereich, dessen Saison heute läuft, und schließt den ersten. Trifft keine Saison zu oder ist JS aus, bleibt der erste Bereich offen. Maßgeblich ist das Datum im Browser des Lesers.

Diese Lösung ist gegen WP Rocket robust, weil die gecachte HTML für alle gleich bleibt.

## Umsetzung Laravel

- Migration für die Spalten oben sowie `prices_html`, `en_prices_html`, `prices_changed_at`, `en_prices_changed_at`.
- `Attraction`: `fillable`, `casts`, `generatePricesHtml($language)`, Eintrag `widget_prices` in `widgetMap()`. `renderWidgetHead()` wiederverwenden. Damit laufen Änderungserkennung und Delta-Sync ohne weitere Anpassung.
- `AttractionBlock`: Preis-Widget nach den Öffnungszeiten ausgeben.
- `AttractionResource`: neue Sektion „Prices“ nach „Opening Hours“ mit Repeatern, Widget-Vorschau in beiden Sprach-Tabs, CSS-Kopie der Vorschau auf Stand des Plugin-CSS bringen.
- Markup: Klasse `am-prices`, Bereiche mit den vorhandenen `am-season` und `am-season-toggle`, neue Klassen mit Präfix `am-price-`. Texte mit `e()` ausgeben.

### Übersetzungsbutton

- Zwei Aktionen in der Sektion: „Nach Englisch übersetzen“ und „Nach Deutsch übersetzen“.
- Übersetzt werden Bereichstitel, Zeilenbeschreibungen, Einleitungs- und Fußtext. Beträge, Währung, Daten und Links bleiben unberührt.
- Quelle ist der aktuelle Formularstand, auch ungespeichert. Das Ergebnis überschreibt die Felder der Zielsprache im Formular und wird nicht gespeichert. Sind dort schon Texte vorhanden, fragt die Aktion vorher nach.
- Neue Methode in `ClaudeService` mit strukturierter Antwort, Modell und Einstellungen aus `AiSetting`. Fehler erscheinen als Filament-Benachrichtigung, das Formular bleibt unverändert. Vorbild ist `GenerateIntroWithAiAction`.

## Umsetzung Plugin

- Spalte `widget_prices` (LONGTEXT) in `class-database.php` inklusive Defaults, Formaten und `sanitize_data()` mit `wp_kses`.
- REST: Feld und Schema in `class-rest-api.php` inklusive `widget_prices_changed_at`.
- `class-shortcodes.php`: Shortcode `attraction_prices`.
- `class-widget-changes.php`: Zuordnung `widget_prices` zu `attraction_prices`, Eintrag in `JSON_LD_TAGS`.
- `widgets.scss`: die Regeln für `.am-season` und `.am-season-toggle` gelten für `.am-hours` und `.am-prices`, dazu Stile für Zeilen, Einleitung, Fußtext und Meta-Zeile. `widgets.css` neu bauen.
- JS für den aktuellen Bereich, siehe oben.
- Admin-Liste, Shortcode-Übersicht, `README.md`, `README-LARAVEL.md` und `CLAUDE.md` ergänzen.
- Version auf 1.4.0 setzen und von `main` abzweigen, unabhängig von der Änderung „Mindestversion WordPress 7.0“. Die 1.3.0 existiert nur als unkommittierte Änderung im Arbeitsverzeichnis des Plugin-Repos und bleibt dort unangetastet; umgesetzt wird deshalb in einem eigenen Worktree. Die Spalte wird nur angelegt, wenn die Version höher ist als die in WordPress gespeicherte (`check_version()`), und die lokale Seite meldet bereits 1.3.0. Deshalb darf das Widget nicht als 1.3.0 erscheinen. Wird die Mindestversion später veröffentlicht, bekommt sie eine höhere Nummer als 1.4.0.

## Reihenfolge beim Ausrollen

Zuerst das Plugin, dann Laravel. Andernfalls sendet Laravel `widget_prices` an ein Plugin ohne dieses Feld, setzt trotzdem `wp_*_last_sync`, und die Änderung wird nicht erneut gesendet.

Das Plugin-Update spielt Patrick manuell ein.

## Prüfung

Laravel, Feature-Tests:

- Darstellung für de und en, EUR und CZK, kostenlos, Rückfall auf die andere Sprache, leeres Widget, Sonderzeichen werden maskiert.
- Saisonattribute erscheinen nur bei vollständigen Daten, der erste Bereich trägt `open`.
- Preisänderung setzt `prices_changed_at`, unveränderte Preise nicht. Sync sendet `widget_prices` mit Zeitstempel. Leeren aller Preise sendet einen leeren Wert.
- Übersetzungsaktion mit `Http::fake`, einschließlich Fehlerfall, Beträge bleiben gleich.

Plugin: Tests für Speichern, `sanitize_data()` (Markup und `data-season-*` bleiben erhalten), Shortcode und Zuordnung in `class-widget-changes.php`. Die Saisonlogik im JS wird einschließlich Jahreswechsel geprüft.

## Doku

`docs/ARTIKELDATUM_UND_CACHE.md` um Widget und Shortcode ergänzen.

## Dem Umsetzenden überlassen

- Genaue CSS-Werte, solange das Widget zu den vorhandenen passt.
- Aufteilung der Filament-Felder und Wortlaut der Beschriftungen im Admin.
- Prompt und Antwortformat der Übersetzung.
