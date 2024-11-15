> Atenção: Todos os comandos abaixo deverão ser executados em seu projeto principal.

## Instalar dependência
```bash
composer require nanicas/auth:dev-main
```

## Configurar o client e secret no .env
```php
return [
    'AUTHENTICATION_OAUTH_CLIENT_ID' => env('NANICAS_AUTHENTICATION_OAUTH_CLIENT_ID'),
    'AUTHENTICATION_OAUTH_CLIENT_SECRET' => env('NANICAS_AUTHENTICATION_OAUTH_CLIENT_SECRET'),
    'AUTHENTICATION_CLIENT_ID' => env('NANICAS_AUTHENTICATION_CLIENT_ID'),
    'AUTHENTICATION_CLIENT_SECRET' => env('NANICAS_AUTHENTICATION_CLIENT_SECRET'),
    'AUTHENTICATION_API_URL' => env('NANICAS_AUTHENTICATION_API_URL'),
    'AUTHENTICATION_PERSONAL_TOKEN' => env('NANICAS_AUTHENTICATION_PERSONAL_TOKEN'),

    'PAINEL_API_URL' => env('NANICAS_PAINEL_API_URL'),
    'PAINEL_PERSONAL_TOKEN' => env('NANICAS_PAINEL_PERSONAL_TOKEN'),

    'AUTHORIZATION_API_URL' => env('NANICAS_AUTHORIZATION_API_URL'),
    'AUTHORIZATION_PERSONAL_TOKEN' => env('NANICAS_AUTHORIZATION_PERSONAL_TOKEN'),

    'SESSION_AUTH_KEY' => 'nanicas_auth',
    'SESSION_CLIENT_AUTH_KEY' => 'nanicas_client_auth',

    'DEFAULT_PERSONAL_TOKEN_MODEL' => Nanicas\Auth\Frameworks\Laravel\Models\PersonalToken::class,
    'DEFAULT_AUTHORIZATION_CLIENT' => Nanicas\Auth\Services\ThirdPartyAuthorizationService::class,
];
```

## Adicionar os providers em config/app.php
```php
'providers' => [
    \Nanicas\Auth\Frameworks\Laravel\Providers\AppServiceProvider::class,
    \Nanicas\Auth\Frameworks\Laravel\Providers\BootstrapServiceProvider::class,
    \Nanicas\Auth\Frameworks\Laravel\Providers\AuthServiceProvider::class,
],
```

## Alterar os guards e providers em `config/auth.php`
```php
'guards' => [
    'web' => [
        'driver' => 'custom_session',
        'provider' => 'custom',
    ],
    'api' => [
        'driver' => 'token',
        'provider' => 'custom_token',
    ],
],
```

```php
'providers' => [
    'custom' => [
        'driver' => 'custom_session',
        'model' => App\Models\User::class,
    ],
    'custom_token' => [
        'driver' => 'custom_token',
        'model' => App\Models\User::class,
    ],
],
```

## Adicionar um apelido (alias) para o middleware em `app/Http/Kernel.php`
```php
'auth_oauth.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\Authenticate::class,
'auth_client.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\AuthenticateClient::class,
'auth_personal.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\ValidatePersonalToken::class,
'acl.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\Permissions::class,
```

## Executar o comando de publicação dos arquivos de configuração
`php artisan vendor:publish --tag="nanicas_auth:config"`

Após o comando, favor verificar no diretório `config` (raiz) se os arquivos foram transferidos:
- `nanicas_auth.php`

## Adicionar a coluna ID no "fillable" na model que representa seu usuário autenticado
```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'id', // It is necessary because Auth API returns this attribute
```

## Gerar Personal Tokens
```
php artisan personal_token:generate painel
```
