<?php

namespace KhattakBits\Core\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use KhattakBits\Core\Models\User;

/**
 * User model resolver.
 *
 * Package ko host ke `App\Models\User` par hard-code karna mana hai, lekin user
 * rows par kaam karna zaroori hai (password reset, admin command, audit
 * snapshots). Is liye model ka naam config se aata hai:
 *
 *   config('khattakbits.user_model')            // product override
 *   config('auth.providers.users.model')        // Laravel ka apna source of truth
 *   KhattakBits\Core\Models\User::class         // aakhri fallback
 *
 * Product normally `config/auth.php` mein apna thin subclass set karta hai, aur
 * wohi value yahan resolve hoti hai — is se host ki extra relations bhi kaam
 * karti hain.
 */
final class Users
{
    /**
     * @return class-string<Model>
     */
    public static function model(): string
    {
        $candidates = [
            config('khattakbits.user_model'),
            config('auth.providers.users.model'),
            User::class,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && class_exists($candidate)) {
                return $candidate;
            }
        }

        return User::class;
    }

    /**
     * Naya query builder resolved model par.
     */
    public static function query(): Builder
    {
        $model = self::model();

        return $model::query();
    }
}
