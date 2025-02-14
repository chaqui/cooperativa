<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $token = $this->userService->generateToken($user);

        return response()->json(compact('token'));
    }

    public function logout(): JsonResponse
    {
        try {
            $this->userService->logout();
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout', 'message' => $e->getMessage()], 500);
        }
    }

    public function validateToken(): JsonResponse
    {
        $valid = $this->userService->validateToken();
        if ($valid) {
            return response()->json(['message' => 'Token is valid']);
        }
        return response()->json(['error' => 'Token is invalid'], 401);
    }
}
