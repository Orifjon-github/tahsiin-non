<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'admin_group_id' => env('TELEGRAM_ADMIN_GROUP_ID'),

    // Non narxi
    'bread_price' => 3500,

    // Yetkazish vaqtlari
    'delivery_times' => [
        '6:00-6:30',
        '6:30-7:00',
        '7:00-7:30',
        '7:30-8:00',
        '8:00-8:30',
        '8:30-9:00',
        '9:00-9:30',
        '9:30-10:00',
    ],

    // Maksimal non soni
    'max_bread_quantity' => 10,
];
