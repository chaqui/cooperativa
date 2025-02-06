<?php

namespace App\Traits;

use Tymon\JWTAuth\Facades\JWTAuth;


trait Authorizable
{
    use Loggable;
    public function authorizeRol($roles)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $user && in_array($user->role_id, $roles);
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }
}
