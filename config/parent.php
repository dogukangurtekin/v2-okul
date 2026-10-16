<?php

return [
    // log: yalniz parent_messages'a yazar | whatsapp: WhatsApp Cloud API sablon mesaji
    'channel' => env('PARENT_CHANNEL', 'log'),
    'link_days' => 30,
    'whatsapp' => [
        'version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'template' => env('WHATSAPP_TEMPLATE_NAME'),
        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'tr'),
    ],
];
