# Währungen im Preis-Widget

## Speicherung

- Die Attraktion speichert ihre Währung als ISO-4217-Code in `attractions.price_currency` (z. B. `EUR`, `CZK`). Es gibt keinen Fremdschlüssel auf eine ID.
- Die Tabelle `currencies` (Model `Currency`) enthält nur die Formatierung: `code`, `symbol` (optional) und `decimals`.
- `Attraction::formatPrice()` liest Symbol und Nachkommastellen aus `currencies`. Ohne Symbol wird der ISO-Code angezeigt („250 CZK“ / „CZK 250“). Fehlt ein Code in der Tabelle, gelten 2 Nachkommastellen ohne Symbol.

## Neue Währung anlegen

Im Tab „Prices“ der Attraktion über das „+“ am Feld „Currency“: ISO-Code (3 Buchstaben, wird großgeschrieben, muss neu sein), optionales Symbol (max. 5 Zeichen) und Nachkommastellen (0–3). Die neue Währung ist danach sofort ausgewählt und steht für alle Attraktionen zur Verfügung.

Bestehende Währungen lassen sich in der Oberfläche nicht bearbeiten oder löschen.

## Bestehende Zuordnungen (Migration vom 06.11.2026)

Vorher war die Liste fest im Code (EUR €, CZK 0 Stellen, CHF, GBP £, PLN, HUF 0 Stellen). Die Migration `create_currencies_table` legt diese sechs mit identischer Formatierung an und übernimmt zusätzlich jeden Code, der schon in `attractions.price_currency` steht. Die Werte an den Attraktionen ändert sie nicht, die Widgets sehen danach genauso aus wie vorher.
