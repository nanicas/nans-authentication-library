<?php

namespace Nanicas\Auth\Traits;

use Illuminate\Http\Request;
use Nanicas\Auth\Helpers\LaravelAuthHelper;
use Nanicas\Auth\Services\ThirdPartyAuthorizationService;
use Nanicas\Auth\Exceptions\RequiredContractToPermissionateException;

trait Permissionable
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Nanicas\Auth\Services\ThirdPartyAuthorizationService $client
     * @return mixed
     */
    public function getACLPermissions(Request $request, ThirdPartyAuthorizationService $client)
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);
        $auth = $request->session()->get($config['SESSION_AUTH_KEY']);

        if (array_key_exists('acl', $auth)) {
            return $auth['acl'];
        }

        return $this->forceGetACLPermissions($request, $client);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Nanicas\Auth\Services\ThirdPartyAuthorizationService $client
     * @return mixed
     */
    public function forceGetACLPermissions(Request $request, ThirdPartyAuthorizationService $client)
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);
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

        LaravelAuthHelper::attachInSession(
            $request->session(),
            'acl',
            $data
        );

        return $data;
    }
}
