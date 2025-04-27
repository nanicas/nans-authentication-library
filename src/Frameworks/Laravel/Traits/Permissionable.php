<?php

namespace Nanicas\Auth\Frameworks\Laravel\Traits;

use Illuminate\Http\Request;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
// use Nanicas\Auth\Exceptions\FalseHTTPResponseException;
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
        $config = config(AuthHelper::CONFIG_FILE_NAME);
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

            // if (isset($response['message'][0])) {
            //     throw new FalseHTTPResponseException($response['message'][0]);
            // }
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
