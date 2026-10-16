<?php

// php artisan push:vapid ile anahtar uretip .env'ye ekleyin. Paket: composer require minishlink/web-push
return [
    'subject' => env('WEBPUSH_VAPID_SUBJECT', 'mailto:admin@example.com'),
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),
];
