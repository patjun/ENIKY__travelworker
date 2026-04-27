---
name: Security Audit Phases 1+2 (2026-04-27)
description: Composer fully patched (Phase 1, Release 1.1.0); npm vulnerabilities reduced to zero by migrating from Laravel Mix to Vite + Filament custom theme (Phase 2)
type: project
---

# Security Audit Phases 1+2 — 2026-04-27

## Phase 1 — Released as 1.1.0 (composer 3 → 0; npm 100 → 11)
- `league/commonmark` 2.8.1 → 2.8.2 (CVE-2026-33347, embed allowed_domains bypass, medium)
- `phpseclib/phpseclib` 3.0.49 → 3.0.52 (CVE-2026-32935 AES-CBC padding oracle high; CVE-2026-40194 timing HMAC low)
- `symfony/polyfill-php80` 1.33.0 → 1.37.0 (transitive)
- `npm audit fix` (non-breaking) closed all 8 critical and 40 high CVEs.

## Phase 2 — Vite migration (npm 11 → 0)
- Removed Laravel Mix and the entire `webpack-dev-server` / `webpack-notifier` / `sockjs` / `uuid` / `elliptic` / `create-ecdh` toolchain.
- Introduced Vite 6 + Tailwind 3 + PostCSS 8 + Autoprefixer 10 + `@fontsource/inter` + axios as the new frontend stack. (Vite was rolled to ^6.0.0 instead of the originally-pinned ^5.0.0 to satisfy `laravel-vite-plugin@1`'s peer range while still hitting `npm audit` 0.)
- Added a Filament v3 custom theme at `resources/css/filament/admin/theme.css` with a shared `tailwind.config.js` re-export so the branding tokens live in exactly one place. The theme uses Filament's panel preset (`vendor/filament/filament/tailwind.config.preset`).

## Branding tokens (single source of truth: `tailwind.config.js`)
- Primary: `#5cd0dd` (turquoise) — also mapped via `Color::hex('#5cd0dd')` in the AdminPanelProvider.
- Accent: `#39527d` (marine blue).
- Font: Inter (loaded locally via `@fontsource/inter`, no Google Fonts request).
- Border-radius: Tailwind default.

## Build pipeline
- `npm run dev` for local development with HMR.
- `npm run build` for production bundling; outputs to `public/build/` with `manifest.json`.
- Filament's own component assets remain managed by `php artisan filament:upgrade` (Composer post-script) and are independent of the Vite build.

**Why this matters for future work:** When the user revisits dependency hygiene, the only npm-related concern is Vite/Tailwind upgrades, no longer a hidden Mix toolchain. When asked to add new front-end assets or styles, edit the Vite inputs and `tailwind.config.js`. When asked to adjust admin branding, edit the tokens in `tailwind.config.js` once and both the welcome page and Filament theme update.
