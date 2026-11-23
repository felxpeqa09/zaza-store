<?php

return [
    'environment' => env('MERCADOPAGO_ENV', 'test'),
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
];
