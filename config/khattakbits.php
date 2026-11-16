<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | Package ko user rows par kaam karna hota hai (password reset, admin:create,
    | audit snapshots) magar host ke model par hard-code karna mana hai. Yeh
    | value `KhattakBits\Core\Support\Users::model()` se resolve hoti hai.
    |
    | Khaali chhoren to `config('auth.providers.users.model')` use hoti hai, jo
    | Laravel ka apna source of truth hai — product ka thin subclass wahin set
    | hota hai. Is liye normally ise touch karne ki zaroorat nahi.
    |
    */

    'user_model' => env('KHATTAKBITS_USER_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Tenancy — D2 migration split
    |--------------------------------------------------------------------------
    |
    | `false` = ek hi DB (control plane, single-tenant starter kit, tests):
    | `php artisan migrate` central AUR tenant dono folders chalata hai, is liye
    | poora schema (audit_logs, settings, email_*, attachments, permissions)
    | ek hi database mein banta hai.
    |
    | `true` = multi-DB product: `php artisan migrate` sirf central/ chalata hai
    | aur tenant folders `tenants:migrate` ke --path mein jate hain (stancl/tenancy).
    |
    | Tafseel: docs/platform/03-EXTRACTION-PLAN.md D2.3.
    |
    */

    'tenancy' => [
        'enabled' => filter_var(env('KB_TENANCY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
