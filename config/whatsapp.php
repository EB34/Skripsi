<?php

return [
    'aktif' => env('WA_GATEWAY_AKTIF', true),
    'url' => env('WA_GATEWAY_URL', 'http://localhost:3000'),
    'token' => env('WA_GATEWAY_TOKEN', ''),
    'timeout' => 10,
];