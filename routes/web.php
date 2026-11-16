<?php

use KhattakBits\Core\Routing\CoreRoutes;

/*
|--------------------------------------------------------------------------
| Core web routes
|--------------------------------------------------------------------------
|
| Routes `KhattakBits\Core\Routing\CoreRoutes` mein hain (v2.0.0 se) — sirf
| yahan se register hoti hain. Do groups: central (login, reset, logout,
| profile) aur tenant-scoped (attachments, document activity). Tenant group
| `/{tenant}` ke andar sirf tab jata hai jab `khattakbits.tenancy.enabled`
| true ho; warna aaj ke URLs bilkul wohi rehte hain (`03` D3.1).
|
| Product ke apne routes host ke `routes/web.php` mein rehte hain — core inhein
| chhoota nahi, is liye product route names ya URIs core se collide nahi karte.
|
*/

CoreRoutes::register((bool) config('khattakbits.tenancy.enabled', false));
