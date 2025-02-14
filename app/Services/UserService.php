<?php

namespace App\Services;

use App\Models\User;
use App\Traits\Loggable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserService
{

    use Loggable;

    public function createUser($data)
    {
        $this->log($data);
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
        throw new ModelNotFoundException('User not found');
    }

    public function generateToken(User $user)
    {
        if(!$user->active) {
            throw new \Exception('User is not active');
        }
        $this->log('Generating token for user: ' . $user->email);
        return JWTAuth::fromUser($user);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function validateToken()
    {
        return JWTAuth::parseToken()->checkOrFail();
    }

    public function inactivateUser($id)
    {
        $user = $this->getUserById($id);
        $user->active = false;
        $user->save();
    }
}
