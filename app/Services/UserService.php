<?php

namespace App\Services;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService
{



    public function createUser($data)
    {
        User::create($data);
    }

    public function updateUser($data, $id)
    {
        $user = $this->getUserById($id);
        $user->update($data);
    }

    public function deleteUser($id)
    {
        $user = $this->getUserById($id);
        $user->delete();
    }

    public function getUsers()
    {
        return User::all();
    }

    public function getUserById($id)
    {
        $user = User::find($id);
        if ($user) {
            return $user;
        }
        throw new \Exception('User not found');
    }

    public function generateToken(User $user)
    {
        return JWTAuth::fromUser($user);
    }
}
