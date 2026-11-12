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

];
