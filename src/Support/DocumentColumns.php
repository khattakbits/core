<?php

namespace KhattakBits\Core\Support;

use Illuminate\Database\Schema\Blueprint;

final class DocumentColumns
{
    public static function add(Blueprint $table): void
    {
        $table->string('name', 60)->unique();
        $table->unsignedTinyInteger('docstatus')->default(0)->index();
        $table->unsignedBigInteger('amended_from')->nullable()->index();
        // `users` central DB mein hai aur document tables tenant DB mein — cross-DB FK
        // impossible hai (D1 rule, 03 §Step 0). Index rakha hai taake owner-wise queries
        // tez rahen; null-on-delete ka kaam DB constraint ke bajaye model hook karta hai.
        $table->unsignedBigInteger('created_by')->nullable()->index();
        $table->timestamp('submitted_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->softDeletes();
        $table->index('deleted_at');
    }
}
