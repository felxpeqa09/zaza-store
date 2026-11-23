<?php

return [
    'map_embed_url' => env('ZAZA_MAP_EMBED_URL'),
    'currency' => 'COP',
    'catalog_disk' => env('CATALOG_DISK', 'public'),
    'health_token' => env('HEALTH_TOKEN'),
    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
    'recaptcha' => [
        'enabled' => (bool) env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET_KEY'),
        'hostname' => env('RECAPTCHA_HOSTNAME') ?: parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST),
    ],
    'contact' => [
        'whatsapp' => '+57 314 389 7867',
        'whatsapp_url' => 'https://wa.me/573143897867',
        'email' => 'storezaza848@gmail.com',
        'instagram' => '@zazastore_ibg',
        'instagram_url' => 'https://www.instagram.com/zazastore_ibg/',
        'address' => 'Cra 7 # 16-25, centro, Ibagué',
    ],
];
