<?php

namespace Nanicas\Auth\Traits;

use Illuminate\Http\Request;
use Nanicas\Auth\Helpers\LaravelAuthHelper;
use Nanicas\Auth\Services\ThirdPartyAuthorizationService;

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

        $response = $client->retrieveByTokenAndContract($auth['access_token'], 6);
        if (!$response['status']) {
            return [];
        }

        $data = [
            'permissions' => $response['body']['response']['permissions'],
            'role' => $response['body']['response']['role'],
        ];

        LaravelAuthHelper::attachInSession(
            $request->session(),
            'acl',
            $data
        );

        return $data;
    }
}
