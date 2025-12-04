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
'authorizate_dynamic.nanicas' => \Nanicas\Auth\Frameworks\Laravel\Http\Middleware\AuthorizateWithDynamicContract::class,
```

---

## Exemplos

### Autorização com Contrato Dinâmico (Multi-tenant) - Recomendado

Esta é a forma recomendada para aplicações multi-tenant, onde o contrato é definido dinamicamente via header HTTP.

#### 1. Configurar o AuthServiceProvider

No seu `App\Providers\AuthServiceProvider.php`, implemente a trait `PolicyPermissionMapeable` e defina o mapeamento de permissões para policies:

```php
namespace App\Providers;

use App\Models\Charge;
use App\Policies\ChargePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Nanicas\Auth\Frameworks\Laravel\Traits\PolicyPermissionMapeable;

class AuthServiceProvider extends ServiceProvider
{
    use PolicyPermissionMapeable;

    protected $policies = [
        Charge::class => ChargePolicy::class,
        // ... outras policies
    ];

    /**
     * Mapeamento de permissões para policies.
     * A chave é o prefixo da permissão (ex: "charge" para "charge.create").
     */
    public static $mapPermissions = [
        'charge' => ChargePolicy::class,
        // 'gym' => GymPolicy::class,
    ];
}
```

#### 2. Habilitar verificação de permissões ACL

No arquivo `config/nanicas_auth.php`:

```php
'gate' => [
    'check_acl_permissions' => true,
],
```

#### 3. Usar o middleware nas rotas

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'authorizate_dynamic.nanicas'])->group(function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::get('/charges', [ChargeController::class, 'index']);
        // ...
    });
});
```

#### 4. Enviar o header X-Contrato-ID nas requisições

```bash
curl --location 'http://app:8000/api/v1/charges' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer eyJ0eXAiOiJKV1Q...' \
--header 'X-Contrato-ID: 6'
```

#### Como funciona

O middleware `authorizate_dynamic.nanicas`:

1. Extrai o `contract_id` do header `X-Contrato-ID`
2. Busca as permissões do usuário no Autorizador para aquele contrato
3. Registra dinamicamente as permissões no `Gate` do Laravel
4. As permissões seguem o padrão `recurso.acao` (ex: `charge.create`, `charge.update`)

O mapeamento em `$mapPermissions` associa o prefixo da permissão (`charge`) à Policy correspondente (`ChargePolicy`). Assim, quando uma permissão `charge.create` é recebida, o Gate registra automaticamente `ChargePolicy@create`.

#### Usar nas Policies

```php
namespace App\Policies;

use App\Models\User;
use App\Models\Charge;
use Illuminate\Support\Facades\Gate;

class ChargePolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('charge.create');
    }

    public function update(User $user, Charge $charge): bool
    {
        return Gate::allows('charge.update');
    }
}
```

#### Acessando dados da autorização no Controller/Service

Após passar pelo middleware, você pode acessar os dados da autorização usando o helper `AuthHelper::getAuthorizationResponse(request())`:

**Estrutura de dados retornada pelo `getAuthorizationResponse()`:**

```json
{
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
                "charge.create",
                "charge.update",
                "charge.read",
                "charge.delete"
            ],
            "cache": {
                "contract_name": "Contrato Inicial",
                "contract_subdomain": "banana",
                "application_name": "Banana",
                "application_domain": "nanicas.com"
            },
            "attributes": {
                "nome": "João",
                "asaas_customer_id": "cus_000007274549"
            }
        },
        "status": true
    }
}
```

---

### Autorização com Contrato Fixo (HARD_CONTRACT_ID)

> ⚠️ **Pendente:** O middleware para autorização com contrato fixo (`HARD_CONTRACT_ID`) ainda não foi implementado na library. Caso necessite dessa funcionalidade, será necessário abrir um PR futuro ou implementar manualmente em sua aplicação.

A configuração `HARD_CONTRACT_ID` no arquivo `nanicas_auth.php` está reservada para cenários onde o contrato é único/fixo para toda a aplicação, diferente do modo dinâmico que usa o header `X-Contrato-ID`.

---

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
      0 => "charge.create"
      1 => "charge.read"
      2 => "charge.update",
      3 => "charge.delete",
    ]
    "role" => array:2 [▼
      "id" => 2
      "name" => "Diretor"
    ]
  ]
]
```

> Caso você decida não usar o `define_contract_by_domain.nanicas` para definir seu contrato automaticamente na sessão, tenha em mente que será necessária tal informação, caso contrário, receberá o seguinte erro:

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
