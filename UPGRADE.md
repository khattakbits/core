# Upgrade: khattakbits/core v1.x → v2.0.0

v2.0.0 **MAJOR** hai — do public contracts badalte hain: core routes ka split aur `menu.php`
ka `path` key. Dono ki wajah path-based multi-tenancy hai (`07-MULTI-TENANCY-DESIGN.md`),
magar **flag off (default) par aap ke URLs aur behaviour bilkul wohi rehte hain**.

> **Sab se pehle:** `khattakbits.tenancy.enabled` (env `KB_TENANCY_ENABLED`) `false` hai to
> upgrade 15 minute ka kaam hai — sirf **C4** aur **C5** (neeche). Baqi steps tab hain jab
> aap tenancy on karein.

## 0. Breaking changes — ek nazar mein

| # | Kya toota | Kise farq parta hai | Fix |
|---|---|---|---|
| 1 | `menu.php` mein `href` ki jagah `path` (static) + `href` (runtime) | har product ka `menu.php` | **C4** |
| 2 | `routes.php` route URI `href` se nahi, `path` se leta hai | har product ka module `routes.php` | **C5** |
| 3 | Tenant-scoped core routes (`attachments`, `documents/...`) flag on par `/{tenant}/...` par chali jati hain | sirf tenancy on karne par | **C3** |
| 4 | Migrations `database/migrations/{central,tenant}/` mein | har product (D2 se) | section 3 |

Route **names** nahi badle: `login`, `password.request`, `password.email`, `password.reset`,
`password.update` wohi hain. Baqi core routes ke naam v1 mein bhi nahi thay.

## 1. Route split (`03` D3.1, `07` §3.4)

Core routes ab do groups mein hain — `KhattakBits\Core\Routing\CoreRoutes`:

| Group | Routes | `KB_TENANCY_ENABLED=false` (default) | `=true` |
|---|---|---|---|
| Central | `login`, `forgot-password`, `reset-password`, `logout`, `profile`, `profile/password` | `/login`, `/profile`, … | **wohi** (prefix nahi) |
| Tenant-scoped | `attachments` (4), `documents/{type}/{id}/views`, `.../activity` | `/attachments`, `/documents/...` | `/{tenant}/attachments`, `/{tenant}/documents/...` |

`{tenant}` **pehla** param hona lazmi hai: `InitializeTenancyByPath` `parameterNames()[0]`
padhta hai (`07` §1.2). `/attachments/{tenant}/{attachment}` chupchaap non-tenant route ban
jata — attachment central disk par dhoonda jata aur 404 aata.

**Aap ko kya karna hai:**

- `route('attachments.show', ...)` jaisi calls: core routes ke naam nahi hain, is liye aaj
  koi `route()` call nahi hai. Agar aap ne khud naam diye hain to `{tenant}` param pass karein
  — `route('x', ['tenant' => $slug, ...])`. `URL::defaults(['tenant' => $slug])` sirf tab kaam
  karta hai jab request mein tenancy initialized ho (`07` §1.3).
- Hardcoded `/attachments` links: flag on karne se pehle `tenantUrl('/attachments')` par le
  jayein (C2/C3). **Yeh helper v2.0.0 mein maujood nahi** — P5 (tenancy phase) mein aayega.
  Jab tak tenancy on na karein, kuch nahi karna.

## 2. `menu.php`: `path` (static) + `href` (runtime) — `03` D3.2, `07` §5.3

**Kyun:** `AccountingDemo/routes.php` menu ke `href` ko route URI ki tarah use karta tha
(`Route::inertia($item['href'], ...)`). Agar `href` mein slug aa jaye to route registration
mein slug **bake** ho jata — tenant A ki routes banti hain, tenant B ki 404.

Naya contract:

```php
// v2.0
[
    'label' => 'Sales Invoice',
    'path'  => '/accounting-demo/doctypes/sales-invoice',  // static, slug-free — route registration
    // 'href' => chhorein; MenuBuilder khud bharta hai (aaj path ke barabar, tenancy on par slug ke saath)
    'can'   => null,
]
```

`MenuBuilder` payload ab har item mein **`path`** aur **`href`** dono deta hai (module entry
par bhi). `href` tab tak `path` hi hai; slug prefixing P5 mein `MenuBuilder::hrefFor()` ke
andar aayegi — us waqt aap ki `menu.php` files dobara touch nahi hongi.

**Backward compatible:** purani `menu.php` (sirf `href`) crash nahi karti — `path` `href` se
fallback hota hai. Magar `routes.php` ko phir bhi `path` par shift karna hoga (C5), warna
trap wapas aa jata hai.

## 3. Migrations (`{central,tenant}`) — D2 se

Agar aap ne D2 ke baad upgrade nahi kiya:

- `php artisan migrate` ab **central + tenant** dono chalata hai jab flag `false` ho (CP,
  single-tenant starter, tests) — poora schema ek DB mein.
- Flag `true` par sirf `central/`; tenant folders `php artisan tenants:migrate` se.
- **Filename na badlein.** Migration order timestamp prefix se aata hai; rename karne se
  pehle se chali hui migrations dobara chalengi ("table already exists").
- Apni module migrations `app/Modules/{Module}/Database/Migrations/{central,tenant}/` mein
  rakhein (flat `Migrations/*.php` support nahi).

## 4. Codemod steps

`C1`–`C7` `07` §5.5 ka plan hai. **Kaunsa step kab:** C4/C5 v2.0.0 ke saath foran; C1/C2/C3/C6
tab jab aap tenancy on karein (P5) — kyunke `tenantUrl()` / `tenantPath()` us waqt aate hain.

| Step | Kya | Files | Kab | Verify |
|---|---|---|---|---|
| **C1** | `khattakbits-ui` mein `useTenantUrl.js` + export | 2 (naya + `index.js`) | P5 | `npm run build` |
| **C2** | `resources/js` ke tenant path literals → `tenantPath()` | 7 Vue files | P5 | click-through + `grep` zero |
| **C3** | PHP ke tenant redirects → `to_route()` / `tenant_url()` | 3 controllers | P5 | `route:list` diff |
| **C4** | `menu.php`: har item mein `href` → `path` (slug-free). Leading `/{slug}` ho to strip karein | har module ka `menu.php` | **ab** | sidebar ke saare links click |
| **C5** | `routes.php`: `$item['href']` → `$item['path']` | module `routes.php` | **ab** | `route:list` mein wohi URIs |
| **C6** | `MenuBuilder` prefixing + `useActiveModule` fix | 2 files | P5 | Home + Settings dono highlight |
| **C7** | `useServerList.js` / `UserMenu.vue` — **koi change nahi** (sab central) | 0 | — | — |

**C4 ka mechanical shape:**

```bash
# har module ke menu.php mein
grep -n "'href' =>" app/Modules/*/menu.php
# 'href' => '/settings/users'   ->   'path' => '/settings/users'
```

**C5 ka mechanical shape:**

```php
// pehle
Route::inertia($item['href'], 'Examples/List');
// ab
Route::inertia($item['path'], 'Examples/List');
```

## 5. Verify

```bash
# 1. Flag off par route table bilkul wohi hona chahiye (koi {tenant} nahi)
php artisan route:list --json | jq -r '.[] | select(.uri | contains("{tenant}")) | .uri'
# → khali output

# 2. Central routes mojood hain
php artisan route:list --path=profile
php artisan route:list --path=attachments

# 3. Menu payload: path aur href dono, aur barabar
php artisan tinker --execute 'dump(app(\KhattakBits\Core\Support\MenuBuilder::class)->for(App\Models\User::first())[0]);'

# 4. Koi menu.php mein purana href na bacha ho
grep -rn "'href' =>" app/Modules/*/menu.php   # → koi hit nahi

# 5. Tests
php artisan test
```

## 6. Rollback

v2.0.0 se v1.x par wapas — 10 minute ka kaam hona chahiye, warna koi upgrade karega hi nahi:

```bash
composer require khattakbits/core:^1.4 --with-dependencies
```

Phir:

1. `menu.php` files revert (git) — v2 ne `href` ko `path` kiya tha.
2. Module `routes.php` revert (`$item['path']` → `$item['href']`).
3. `composer dump-autoload` + `php artisan optimize:clear`.

**Kya nahi todega:** v2 ki migrations (`{central,tenant}` folders) DB ko touch nahi karti —
rollback par `php artisan migrate` phir bhi chalega, kyunke filenames wohi hain aur migration
table already unhein "run" kehti hai.

**Kya todega:** agar aap ne v2 par `KB_TENANCY_ENABLED=true` kar diya tha aur tenant DBs
ban gaye thay, to v1 par wapas jaane ka matlab hai path-based tenancy ka poora kaam rad —
data wapas central DB mein nahi aayega. Us soorat mein rollback ka faisla data migration ka
kaam hai, sirf composer pin nahi.
