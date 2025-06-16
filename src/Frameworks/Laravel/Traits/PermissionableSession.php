<?php

namespace Nanicas\Auth\Frameworks\Laravel\Traits;

use Illuminate\Http\Request;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Exceptions\RequiredContractToPermissionateException;

trait PermissionableSession
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Nanicas\Auth\Contracts\AuthorizationClient $client
     * @return mixed
     */
    public function getACLPermissions(Request $request, AuthorizationClient $client)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);
        $auth = $request->session()->get($config['SESSION_AUTH_KEY']);

        if (array_key_exists('acl', $auth)) {
            return $auth['acl'];
        }

        return $this->forceGetACLPermissions($request, $client);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Nanicas\Auth\Contracts\AuthorizationClient $client
     * @return mixed
     */
    public function forceGetACLPermissions(Request $request, AuthorizationClient $client)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);
        $auth = $request->session()->get($config['SESSION_AUTH_KEY']);

        if (!array_key_exists('contract',  $auth)) {
            throw new RequiredContractToPermissionateException();
        }

        $response = $client->retrieveByTokenAndContract($auth['access_token'], $auth['contract']['id']);
        if (!$response['status']) {
            $data = [
                'permissions' => [],
                'role' => null,
            ];
        } else {
            $data = [
                'permissions' => $response['body']['response']['permissions'],
                'role' => $response['body']['response']['role'],
            ];
        }

        AuthHelper::attachInSession(
            $request->session(),
            'acl',
            $data
        );

        return $data;
    }
}
