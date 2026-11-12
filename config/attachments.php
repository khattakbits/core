<?php

/*
|--------------------------------------------------------------------------
| Attachment limits (package defaults)
|--------------------------------------------------------------------------
|
| Yeh sirf defaults hain. Product inhein `vendor:publish --tag=khattakbits-config`
| se apne `config/attachments.php` mein le kar override karta hai — aur wahan
| `types` mein apne doctypes declare karta hai.
|
| `types` package mein khaali hai jaan boojh kar: kaunsa doctype attachments
| accept karta hai yeh product ka faisla hai, core ka nahi. Jis type ka key
| nahi, wo 404; jis action ka key nahi, wo 403.
|
*/

return [
    'max_kb' => 10240,                    // 10 MB per file
    'max_files_per_record' => 50,
    'max_mb_per_user_per_day' => 200,
    'extensions' => [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'xls',
        'xlsx',
        'csv',
        'mp3',
        'm4a',
        'wav',
        'ogg',
        'oga',
        'opus',
        'aac',
        'weba',
        'webm',
    ],

    'types' => [],
];
