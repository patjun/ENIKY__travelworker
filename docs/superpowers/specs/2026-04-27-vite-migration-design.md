# Vite-Migration mit Filament-Custom-Theme — Design

**Datum:** 2026-04-27
**Phase:** Security-Audit Phase 2 (Folge des Composer/NPM-Patch-Releases 1.1.0)
**Status:** Spec — bereit für Implementation Plan

---

## 1. Ziel und Kontext

Mit Release 1.1.0 wurden alle Composer-Schwachstellen sowie alle critical/high-NPM-Schwachstellen geschlossen. 11 verbleibende NPM-Advisories (5 low, 6 moderate) liegen ausschließlich in der Laravel-Mix-Toolchain (`webpack-dev-server`, `webpack-notifier`, `sockjs`, `uuid`, `elliptic`, `create-ecdh`, …) und können nicht ohne einen Major-Version-Bruch behoben werden.

Eine Inspektion der Codebase hat ergeben, dass die bestehende Mix-Pipeline in der Anwendung **nicht aktiv genutzt wird**:

- `webpack.mix.js` baut nur `resources/js/app.js` (= `require('./bootstrap')` mit `lodash` + `axios` als window-Globals) und ein leeres `resources/css/app.css`
- Es gibt keine `mix()`-Aufrufe in App- oder View-Code (nur in `vendor/`)
- `welcome.blade.php` referenziert weder Mix-Output noch Vite
- `public/mix-manifest.json` existiert nicht
- `public/js/` und `public/css/` enthalten ausschließlich Filament-/dotswan-Assets, die über `php artisan filament:upgrade` (Composer post-script) verwaltet werden — unabhängig von Mix

Die Migration nutzt die Gelegenheit, **Vite produktiv** einzuführen, **Tailwind** als gemeinsame Style-Grundlage einzurichten und ein **Filament-Custom-Theme mit zentralen Branding-Tokens** zu etablieren. Damit verschwinden die 11 Schwachstellen und die App erhält einen einheitlichen, modernen Frontend-Stack.

### Branding-Tokens (verbindlich)

| Token | Wert |
|-------|------|
| Primary | `#5cd0dd` (Türkis) |
| Accent | `#39527d` (Marineblau) |
| Font | Inter (lokal über `@fontsource/inter`) |
| Border-Radius | Tailwind-Default |

---

## 2. Architektur

### 2.1 Build-Tool

Vite ersetzt Laravel Mix vollständig. Eine einzige `vite.config.js` im Projekt-Root definiert alle Inputs:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
    ],
});
```

### 2.2 Tailwind als gemeinsame Style-Grundlage

Eine **Root-`tailwind.config.js`** definiert die Branding-Tokens als Single Source of Truth:

```js
import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './resources/views/**/*.blade.php',
        '!./resources/views/filament/**',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#5cd0dd',
                accent:  '#39527d',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
```

Der Frontend-Build verarbeitet nur `resources/views/**` außerhalb von `resources/views/filament/` — Filament hat seine eigene Asset-Pipeline und soll nicht doppelt erfasst werden.

`postcss.config.js` enthält `tailwindcss` und `autoprefixer`.

### 2.3 Filament-Custom-Theme

Erzeugt mit:

```bash
php artisan make:filament-theme admin --pm=npm
```

Erzeugte Dateien:
- `resources/css/filament/admin/theme.css`
- `resources/css/filament/admin/tailwind.config.js`

Die `theme.css` startet mit:

```css
@import '@fontsource/inter/400.css';
@import '@fontsource/inter/500.css';
@import '@fontsource/inter/600.css';
@import '@fontsource/inter/700.css';

@import '../../../../vendor/filament/filament/resources/css/theme.css';

@config 'tailwind.config.js';
```

Die Theme-spezifische `tailwind.config.js` erbt die Tokens aus der Root-Config:

```js
import preset from '../../../../vendor/filament/filament/tailwind.config.preset';
import frontendConfig from '../../../../tailwind.config.js';

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: frontendConfig.theme.extend.colors.primary,
                accent:  frontendConfig.theme.extend.colors.accent,
            },
            fontFamily: frontendConfig.theme.extend.fontFamily,
        },
    },
};
```

`app/Providers/Filament/AdminPanelProvider.php` registriert das Theme und mappt die Filament-Color-API:

```php
->colors([
    'primary' => Color::hex('#5cd0dd'),
    'gray'    => Color::Slate,
])
->viteTheme('resources/css/filament/admin/theme.css')
```

`Color::hex()` generiert die nötige 50–950-Skala automatisch. `Color::Slate` für Gray harmoniert farblich mit dem Marineblau-Akzent.

### 2.4 Frontend-Assets

**`resources/css/app.css`:**

```css
@import '@fontsource/inter/400.css';
@import '@fontsource/inter/500.css';
@import '@fontsource/inter/600.css';
@import '@fontsource/inter/700.css';

@tailwind base;
@tailwind components;
@tailwind utilities;
```

**`resources/js/app.js`:**

```js
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

`resources/js/bootstrap.js` und `lodash` entfallen ersatzlos. Pusher/Echo-Codeblock war bereits auskommentiert.

### 2.5 `welcome.blade.php`

Strukturell unverändert (142 Zeilen Inhalt bleiben bestehen). Änderungen:

- `<head>`-Eintrag `@vite(['resources/css/app.css', 'resources/js/app.js'])` ersetzt evtl. Inline-Style-Blöcke und nicht vorhandene Mix-Helper-Calls
- Inline-`<style>`-Blöcke werden in `app.css` als `@layer components { … }` migriert oder durch utility-Klassen ersetzt
- Hartkodierte Farben werden auf die neuen Branding-Tokens (`bg-primary`, `text-accent`, …) gemappt, sofern eine semantische Entsprechung existiert; ansonsten bleibt der visuelle Status quo erhalten

### 2.6 `package.json`

```json
{
    "private": true,
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "vite": "^5",
        "laravel-vite-plugin": "^1",
        "tailwindcss": "^3",
        "postcss": "^8",
        "autoprefixer": "^10",
        "@fontsource/inter": "^5",
        "axios": "^1"
    }
}
```

---

## 3. Migrations-Schritte

Die Umsetzung läuft in dieser Reihenfolge. Jeder Schritt ist isoliert reversibel.

### Schritt 1 — Mix entfernen
- Löschen: `webpack.mix.js`, `resources/js/bootstrap.js`, `node_modules/`, `package-lock.json`
- `package.json` neu schreiben (Form siehe 2.6)

### Schritt 2 — Vite/Tailwind installieren
- `npm install` mit den neuen devDependencies
- `vite.config.js`, `tailwind.config.js`, `postcss.config.js` im Projekt-Root anlegen

### Schritt 3 — Frontend-Assets
- `resources/js/app.js` auf ESM-Import umschreiben (Form siehe 2.4)
- `resources/css/app.css` erstellen (Form siehe 2.4)
- `welcome.blade.php` mit `@vite(...)` ergänzen, Inline-Styles in `app.css` migrieren, Klassen auf Branding-Tokens mappen

### Schritt 4 — Filament-Theme
- `php artisan make:filament-theme admin --pm=npm`
- Generierte `theme.css` und Theme-`tailwind.config.js` an die in 2.3 beschriebene Form anpassen
- `AdminPanelProvider`: `->colors([...])` und `->viteTheme(...)` ergänzen

### Schritt 5 — Build & lokale Verifikation
- `npm run build` läuft fehlerfrei, `public/build/manifest.json` enthält Einträge für alle drei Inputs
- `npm audit` meldet **0 Schwachstellen** (Erfolgskriterium 1)
- `php artisan test` liefert **366 passed**, keine Regression (Erfolgskriterium 2)
- Manueller Browser-Check via Herd:
  - Welcome-Page (`/`) lädt, Vite-Assets aus dem Manifest werden bedient, Inter-Font ist aktiv, Türkis/Marineblau greifen
  - Filament-Admin (`/admin`) lädt, Login funktioniert, Primary-Farbe in Buttons ist Türkis, Filament-Resources sind weiterhin bedienbar (Stichprobe: AttractionResource, ListicleResource, RoleResource)

### Schritt 6 — Dokumentation
- `.claude/memory/security-audit-2026-04-27.md` aktualisieren: Phase 2 als abgeschlossen markieren, neue Build-Pipeline und Tokens dokumentieren
- `CLAUDE.md` Abschnitt "Frontend Bundling" auf `npm run dev` / `npm run build` aktualisieren

---

## 4. Erfolgskriterien (verbindlich)

1. `npm audit` → **0 Vulnerabilities** (alle Severity-Level)
2. `php artisan test` → **366 passed** (891 assertions), keine Regression
3. Welcome-Page und Filament-Admin sind im Browser funktional und visuell konsistent mit den Branding-Tokens

## 5. Stop-Bedingungen

Der Prozess wird abgebrochen und der User informiert, wenn:

- Tests fehlschlagen oder neue Regressionen auftreten
- `npm audit` nach dem Build noch Vulnerabilities zeigt
- Filament-Admin visuell oder funktional gebrochen lädt
- Der Vite-Build-Output das Filament-Theme nicht erzeugt

## 6. Risiken & Mitigation

| Risiko | Mitigation |
|--------|-----------|
| Türkis `#5cd0dd` ist hell — automatisch generierte 600/700/800-Shades evtl. zu wenig Kontrast für Hover/Text | `Color::hex()` als erster Schritt; falls Default-Skala nicht passt, kann später eine handgepflegte 50–950-Skala in den Tokens hinterlegt werden (außerhalb dieses Specs) |
| Mehrere `tailwind.config.js`-Dateien führen zu Drift | Theme-Config re-exportiert Tokens via Import aus der Root-Config — Single Source of Truth |
| Filament-internes Asset-Update via `filament:upgrade` und Custom-Theme-Build via Vite koexistieren | Filament v3 unterstützt das Modell offiziell; Custom-Theme greift _on top_ der Filament-Defaults |
| `welcome.blade.php`-Umbau bricht visuell | Strukturell unverändert lassen, nur Token-Mapping und `@vite`-Einbindung; manueller Browser-Check vor Release |

## 7. Out of Scope (explizit)

- Vollständiger visueller Re-Design des Admin-Panels (das wäre C3b-3, separates Vorhaben)
- Custom-Filament-Komponenten oder Logo/Branding-Assets
- Dark-Mode-Anpassung
- Frontend-Routing oder eine eigene SPA
- Eine handgepflegte 50–950-Farbskala für Primary (nur fallback-fähig, falls 6.1 eintritt)

---

## 8. Verweis

Diese Migration ist Phase 2 der Security-Audit-Initiative; Phase 1 wurde mit Release 1.1.0 abgeschlossen. Status- und Vulnerability-Verlauf siehe [.claude/memory/security-audit-2026-04-27.md](../../../.claude/memory/security-audit-2026-04-27.md).
