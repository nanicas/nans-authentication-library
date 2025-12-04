<?php

namespace Nanicas\Auth\Frameworks\Laravel\Traits;

use Illuminate\Http\Request;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
use Nanicas\Auth\Exceptions\RequiredAuthorizationResponseToPermissionateException;

trait PermissionableStateless
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws RequiredAuthorizationResponseToPermissionateException
     */
    public function getACLPermissions(Request $request)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);

        if (!$request->attributes->has($config['AUTHORIZATION_RESPONSE_KEY'])) {
            throw new RequiredAuthorizationResponseToPermissionateException();
        }

        $response = $request->attributes->get($config['AUTHORIZATION_RESPONSE_KEY']);
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

        return $data;
    }

    /**
     * @param Request $request
     * @param string $permission
     * @return bool
     */
    public function hasPermission(Request $request, string $permission)
    {
        $permissions = $this->getACLPermissions($request);
        if (!array_key_exists('permissions', $permissions)) {
            return false;
        }

        return in_array($permission, $permissions['permissions']);
    }
}
