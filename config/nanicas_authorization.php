<?php

return [
    'AUTHENTICATION_API_URL' => env('NANICAS_AUTHENTICATION_API_URL'),
    'PAINEL_API_URL' => env('NANICAS_PAINEL_API_URL'),
    'CLIENT_SECRET' => env('NANICAS_CLIENT_SECRET'),
    'CLIENT_ID' => env('NANICAS_CLIENT_ID'),
    'SESSION_AUTH_KEY' => 'nanicas_auth',
    'SESSION_CLIENT_AUTH_KEY' => 'nanicas_client_auth',
    'DEFAULT_PERSONAL_TOKEN_MODEL' => Nanicas\Auth\Frameworks\Laravel\Models\PersonalToken::class,
];
