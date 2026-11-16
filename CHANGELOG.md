# Changelog

Har version ke saath breaking changes, naye contracts aur migration notes. Format
[Keep a Changelog](https://keepachangelog.com/) jaisa; versioning semver.

## [2.0.0] — 2026-11-16

**MAJOR** — do public contracts badle: core routes ka split aur `menu.php` ka `path` key.
Upgrade steps: [`UPGRADE.md`](UPGRADE.md).

### Added

- `KhattakBits\Core\Routing\CoreRoutes` — core routes ab ek jagah, do groups mein (central
  aur tenant-scoped). `routes/web.php` sirf `CoreRoutes::register()` call karta hai.
- `khattakbits.tenancy.enabled` (env `KB_TENANCY_ENABLED`, default `false`) — D2 ke split
  ka wohi flag jo ab routes aur migrations dono chalata hai.
- `MigrationPaths` (`src/Support/MigrationPaths.php`) — `folders()` / `forMigrate()`, flag ke
  hisaab se `migrate` ke folders tay karta hai.
- `ModuleRegistry::migrationPaths('central'|'tenant')` — module migrations
  `{module}/Database/Migrations/{context}` convention par (`07` §7.2).
- `MenuBuilder` payload mein har item par `path` (static) aur `href` (runtime).
- `UPGRADE.md`, `CHANGELOG.md`.

### Changed

- Migrations `database/migrations/{central,tenant}/` mein split (core: 2 central / 10 tenant).
- `php artisan migrate` flag `false` par **central + tenant** dono folders chalata hai, taake
  control plane (single DB) ka schema poora bane. Flag `true` par sirf `central/`; tenant
  folders `tenancy.migration_parameters['--path']` se `tenants:migrate` ko jate hain.
- `CoreServiceProvider` ka config docblock theek — `mergeConfigFrom` `config:cache` ke saath
  **kaam karta hai** (cache banne wale boot mein defaults dump ho jati hain).

### Breaking

- `menu.php` mein `href` ki jagah `path` likhein; `href` ab runtime key hai (MenuBuilder
  bharta hai). Purani `menu.php` chalti rehti hai (`path` = `href` fallback), magar
  `routes.php` ko `path` par shift karna lazmi hai — warna slug route registration mein
  bake ho jata hai (`03` D3.2).
- Tenant-scoped core routes (`attachments`, `documents/{type}/{id}/views|activity`) flag
  `true` par `/{tenant}/...` par chali jati hain. `{tenant}` **pehla** param hai. Flag
  `false` par URLs bilkul wohi (koi `{tenant}` nahi).
- `DocumentColumns::add()` aur `email_templates.updated_by` se `users` ka DB-level FK hata —
  cross-DB FK multi-DB tenancy mein impossible hai. Columns `unsignedBigInteger()->nullable()`
  + index; null-on-delete ka kaam app-level hook karta hai.

### Notes

- Route **names** nahi badle: `login`, `password.request`, `password.email`, `password.reset`,
  `password.update`.
- `tenantUrl()` / `tenantPath()` is version mein **nahi** hain — path-based tenancy (P5) ke
  saath aayenge. Flag `false` par unki zaroorat bhi nahi.
- `InitializeTenancyByPath` middleware core register nahi karta; product `routes/web.php` mein
  `{tenant}` group par lagta hai (P5).
