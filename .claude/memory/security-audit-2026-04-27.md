---
name: Security Audit Phase 1 (2026-04-27)
description: Composer fully patched and non-breaking npm fixes applied; remaining 11 npm vulns require Laravel Mix → Vite migration
type: project
---

# Security Audit Phase 1 — 2026-04-27

## Composer (resolved: 3 → 0)
- `league/commonmark` 2.8.1 → 2.8.2 (CVE-2026-33347, embed allowed_domains bypass, medium)
- `phpseclib/phpseclib` 3.0.49 → 3.0.52 (CVE-2026-32935 AES-CBC padding oracle high; CVE-2026-40194 timing HMAC low)
- `symfony/polyfill-php80` 1.33.0 → 1.37.0 (pulled in transitively)

## NPM (reduced: 100 → 11)
- All 8 critical and 40 high advisories closed via non-breaking `npm audit fix`.
- Remaining 11 (5 low, 6 moderate) sit in the Laravel-Mix toolchain: `webpack-dev-server`, `webpack-notifier`, `node-notifier`, `sockjs`, `uuid`, `elliptic`, `create-ecdh`. They cannot be fixed without major-version bumps that break `laravel-mix ^6.0.6`.

## Phase 2 — open / not yet done
Migrate the build pipeline from **Laravel Mix to Vite**. This eliminates ~95 % of the remaining npm vulns and aligns with current Laravel defaults (Mix is unmaintained for security since the Vite switch in Laravel 9). Scope:
- Replace `webpack.mix.js` with `vite.config.js`
- Swap Blade `mix()` helper calls for `@vite()` directive
- Update `package.json` scripts (`dev`/`build` instead of Mix scripts)
- Adjust CI build steps if any

**Why:** Phase 1 was scoped to safe, non-breaking patches only; user asked explicitly for Phase 2 to be tracked separately.
**How to apply:** When the user revisits dependency hygiene or a moderate npm CVE escalates, propose the Vite migration as a planned, brainstormed effort — not an ad-hoc `npm audit fix --force`.

## Verification
- `composer audit` → no advisories
- `npm audit` → 11 (5 low, 6 moderate, 0 high, 0 critical)
- `php artisan test` → 366 passed (891 assertions), no regressions
