> Atenção: Todos os comandos abaixo deverão ser executados em seu projeto principal.

## Instalar a dependência

```bash
composer require nanicas/authentication-library:dev-main
```

## Adicionar os Providers

No arquivo `config/app.php`, adicione:

```php
'providers' => [
    \Nanicas\Auth\Frameworks\Laravel\Providers\AppServiceProvider::class,
    \Nanicas\Auth\Frameworks\Laravel\Providers\AuthServiceProvider::class,
],
```

## Executar o comando de publicação dos arquivos de configuração

```
php artisan vendor:publish --tag="nanicas_auth:config"
```

Após o comando, favor verificar no diretório `/config` (raiz) se o arquivo existe:
- `nanicas_auth.php`

## Configurar as variáveis de ambiente

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

    'HARD_CONTRACT_ID' => env('NANICAS_HARD_CONTRACT_ID'),

    'SESSION_AUTH_KEY' => 'nanicas_auth',
    'SESSION_CLIENT_AUTH_KEY' => 'nanicas_client_auth',
    'AUTHORIZATION_RESPONSE_KEY' => 'authorization_response',

    'DEFAULT_PERSONAL_TOKEN_MODEL' => Nanicas\Auth\Frameworks\Laravel\Models\PersonalToken::class,
    'DEFAULT_AUTHORIZATION_CLIENT' => Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthorizationService::class,
    'DEFAULT_AUTHENTICATION_CLIENT' => Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthenticationService::class,

    'stateless' => false,
    'gate' => [
        'check_acl_permissions' => false,
    ]
];
```

## Customizar Guards e Providers em `config/auth.php`

Caso queira personalizar os meios de autenticação, altere:

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

### Configurar a entidade de usuário

Adicionar a coluna ID no "fillable" da Model que representa seu usuário autenticado:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'id', // É necessário porque a API de autenticação retorna esse atributo e deve ser preenchido
```

## Adicionar Middlewares

No arquivo `app/Http/Kernel.php`, adicione:

```php
'auth_client.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\AuthenticateClient::class,
'auth_oauth.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\AuthenticateOauth::class,
'validate_personal_token.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\ValidatePersonalToken::class,
'define_contract_by_domain.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\DefineContractByDomain::class,
```

---

## Exemplos

### Busca de permissões por usuário via API (stateless)

Primeiro passo é sua model referente ao usuário possuir a trait `Nanicas\Auth\Frameworks\Laravel\Traits\PermissionableStateless` implementada.

Segundo passo é habilitar a configuração `gate.check_acl_permissions` e `stateless` no seu arquivo `nanicas_auth.php`.

Vamos criar um middleware para separar a lógica referente a autorização, caso exista: (`namespace App\Http\Middleware`)

```php
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;

class Authorizate
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $request = request();
        $config = config(AuthHelper::CONFIG_FILE_NAME);
        $token = $request->bearerToken();

        $authorizator = app()->make(AuthorizationClient::class);
        $response = $authorizator->retrieveByTokenAndContract($token, $config['HARD_CONTRACT_ID']);

        if (!$response['status']) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => (isset($response['message'])) ? $response['message'] : 'Invalid token or contract not found.',
            ], 401);
        }

        $request->attributes->set($config['AUTHORIZATION_RESPONSE_KEY'], $response);

        return $next($request);
    }
}
```

> No exemplo acima, estamos usando um `HARD_CONTRACT_ID` fixo, ou seja, obtido do arquivo de configuração. Contudo, isso poderia ser dinâmico de acordo com sua regra de negócio.

E por último é tentar fazer o uso do fluxo como um todo, incluindo acesso às permissões, sendo:

```php
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;

Route::middleware(['auth:api', 'authorizate'])->group(function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::get('/user', function (Request $request) {
            try {
                Gate::authorize('create', User::class);
                $hasPermission = true;
            } catch (Exception $e) {
                $hasPermission = false;
            }

            $config = config(AuthHelper::CONFIG_FILE_NAME);

            return [
                'has_permission' => $hasPermission,
                'user' => $request->user(),
                'authorization_response' => $request->attributes->get($config['AUTHORIZATION_RESPONSE_KEY']),
            ];
        });
    });
});
```

**Estrutura de dados via API (stateless)**

```json
{
    "has_permission": true,
    "user": {
        "id": 4,
        "name": "José",
        "email": "jose_diretor@example.com"
    },
    "authorization_response": {
        "status": true,
        "code": 200,
        "body": {
            "response": {
                "user": {
                    "id": 4,
                    "name": "José",
                    "email": "jose_diretor@example.com"
                },
                "role": {
                    "id": 2,
                    "name": "Diretor"
                },
                "permissions": [
                    "create",
                    "edit",
                    "update",
                    "delete",
                ],
                "cache": {
                    "contract_name": "Contrato Inicial",
                    "contract_subdomain": "banana",
                    "application_name": "Banana",
                    "application_domain": "nanicas.com"
                }
            },
            "status": true
        }
    }
}
```

> Um ponto de atenção é sobre o middleware principal `auth:api`, pois, caso o token recebido seja inválido, o Laravel pode passar a exibir `"The route api/v1/user could not be found"`, sendo necessário então configurar um `fallback`.

> Caso tente usar o `Gate::authorize` sem antes passar pelo middleware `auth:api`, receberá o seguinte erro:

```php
Illuminate\Auth\Access\AuthorizationException {#323
  #message: "This action is unauthorized."
```

> Caso tente usar os métodos contidos na trait `PermissionableStateless`, como por exemplo `getACLPermissions`, sem antes adicionar uma resposta válida do `Autorizador` na requisição em questão, receberá o seguinte erro:

```php
Nanicas\Auth\Exceptions\RequiredAuthorizationResponseToPermissionateException {#296
  #message: "Authorization response is required to permissionate"
```

### Busca de permissões por usuário via sessão

Primeiro passo é sua Model referente ao usuário possuir a Trait `Nanicas\Auth\Frameworks\Laravel\Traits\PermissionableSession` implementada.

Segundo passo é habilitar a configuração `gate.check_acl_permissions` e desabilitar a configuração `stateless` no seu arquivo `nanicas_auth.php`

Terceiro e último é tentar fazer o uso do fluxo como um todo, incluindo acesso às permissões, sendo:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;

Route::middleware([
    'define_contract_by_domain.nanicas',
    'auth_oauth.nanicas',
])->get('/user', function () {
    $request = request();

    $client = app()->make(AuthorizationClient::class);
    $permissions = request()->user()->getACLPermissions($request, $client);

    dd(array_merge(
        AuthHelper::getAuthInfoFromSession($request->session()),
        ["acl" => $permissions],
    ));
});
```

**Estrutura de dados via sessão**

```php
array:7 [▼
  "contract" => array:3 [▼
    "id" => 6
    "subdomain" => "banana"
    "domain" => "nanicas.com"
  ]
  "token_type" => "Bearer"
  "expires_in" => 7200
  "access_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
  "refresh_token" => "def5020009ccaf2385d538ac0fd7b73dbad..."
  "expires_at_datetime" => DateTime @1732924585 {#314 ▼
    date: 2024-11-29 23:56:25.910698 UTC (+00:00)
  }
  "acl" => array:2 [▼
    "permissions" => array:3 [▼
      0 => "create"
      1 => "read"
      2 => "update",
      3 => "delete",
    ]
    "role" => array:2 [▼
      "id" => 2
      "name" => "Diretor"
    ]
  ]
]
```

> Caso você decida não usar o `define_contract_by_domain.nanicas` para definir seu contrato automaticamente na sessão, tenha em mente que será necessária tal informação internamente durante o uso do `Gate::authorize`, caso contrário, receberá o seguinte erro:

```php
Nanicas\Auth\Exceptions\RequiredContractToPermissionateException {#112
  #message: "Contract is required to permissionate"
```

> Entendemos que, se existe um grupo cercado pelo middleware `auth_oauth.nanicas`, significa que o login já foi feito anteriormente usando o driver `custom_session`, caso contrário, será redirecionado para o `logout`.

---

### Customizar um serviço terceiro

No arquivo de configuração `config/nanicas_auth.php`, existem as classes padrão, sendo:

```php
'DEFAULT_AUTHORIZATION_CLIENT' => Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthorizationService::class,
'DEFAULT_AUTHENTICATION_CLIENT' => Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthenticationService::class,
```

Caso queira usar uma própria, bastar herdá-la e implementar/ajustar da sua maneira, como:

```php
use Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthenticationService as ThirdPartyAuthenticationServiceNanicas;
use Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthorizationService as ThirdPartyAuthorizationServiceNanicas;

use Nanicas\Auth\Contracts\AuthenticationClient;
use Nanicas\Auth\Contracts\AuthorizationClient;

class YourCustomAuthentication 
    extends ThirdPartyAuthenticationServiceNanicas 
    implements AuthenticationClient
{
    // ...
}

class YourCustomAuthorization
    extends ThirdPartyAuthenticationServiceNanicas 
    implements AuthorizationClient
{
    // ...
}
```

As interfaces são obrigatórias, pois é com esse contrato que o Framework conseguirá usar a inversão de dependência corretamente, como configurado em `src/Frameworks/Laravel/Providers/AppServiceProvider.php`.

---

### Gerar Personal Tokens

As aplicações que forem consumir recursos privados desse projeto, deverão usar um token pessoal para comunicação entre as aplicações (machine-to-machine), enviando no cabeçalho, como no exemplo:

```php
Description:
  Generate a Personal Token

Usage:
  personal_token:generate [options] [--] <tokenable_type>

Arguments:
  tokenable_type                 

Options:
      --name[=NAME]               [default: "access_token"]
      --abilities[=ABILITIES]     (multiple values allowed)
      --expires_at[=EXPIRES_AT]  
```

#### Execução simples (token pessoal)

```bash
php artisan personal_token:generate <consumer>
```

#### Execução avançada (token pessoal)

```bash
php artisan personal_token:generate \
    "Authorization\App\Models\User" \
    --name="access_token" \
    --abilities="read,write" \
    --expires_at="2025-12-31 23:59:59"
```

Resultado (ambos):

```bash
Personal token generated successfully.
Token: e0984965f3f...f3bda2495c2a28
```

Lembre-se de configurar as variáveis de ambiente para lê-las:

```php
'AUTHENTICATION_PERSONAL_TOKEN' => env('NANICAS_AUTHENTICATION_PERSONAL_TOKEN'),
'PAINEL_PERSONAL_TOKEN' => env('NANICAS_PAINEL_PERSONAL_TOKEN'),
'AUTHORIZATION_PERSONAL_TOKEN' => env('NANICAS_AUTHORIZATION_PERSONAL_TOKEN'),
```

#### Enviando token pessoal via HTTP

```
curl --location 'http://app:8000/api/personal/filter' \
--header 'Accept: application/json' \
--header 'Authorization: e0984965f3f...f3bda2495c2a28'
```

---

### Fluxo de entrada no aplicativo

```
Quando acessar: nanicas.app.com

Se (preciso saber o contrato do usuário): (app)
- Busco contrato pelo subdomínio e domínio (autho)

Se (não existir usuario): (app)
- Gera token entre aplicações (auth)
- Cadastra usuário + contrato (se precisar) (auth)

Faz o login enviando email e senha + contrato (se precisar) (auth)
Salva na sessão os dados da autenticação (app)

Tenta buscar os detalhes de autorização do usuário (autho)

Se (não existir papel vinculado): (app)
- Busca papéis por contrato (autho)
- Envia solicitação de vínculo ao papel (autho)

Se (não for entrada automática): (app)
- aguarda aprovação do vínculo ao papel (app)
- desloga o usuário (app)
Senão: (app)
- Salva na sessão os dados contratuais (app)
- Salva na sessão os dados sobre permissões (app)
- Vai para a tela inicial (app)

Quando expirar o token (app)
- Gera um novo token enviando o refresh token (auth)
- Busca os detalhes de autorização do usuário (autho)
- Salva na sessão os dados da autenticação (app)
- Salva na sessão os dados contratuais (app)
- Salva na sessão os dados sobre permissões (app)
```

### Legendas

- **auth**, Autenticação: https://github.com/nanicas/nans-authentication-laravel
- **autho**, Autorização: https://github.com/nanicas/nans-authorization-laravel
