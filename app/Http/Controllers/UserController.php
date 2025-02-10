<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUser;
use App\Traits\SqlMesage;
use App\Services\UserService;
use App\Http\Resources\User as UserResource;

class UserController extends Controller
{

    use SqlMesage;
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->userService->getUsers();
        return UserResource::collection($users);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUser $request)
    {
        try {
            $user = $this->userService->createUser($request->all());
            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->sqlMessageError($e)], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = $this->userService->getUserById($id);
        return new UserResource($user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUser $request, string $id)
    {
        $user = $this->userService->updateUser($request->all(), $id);
        return response()->json($user, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->userService->deleteUser($id);
        return response()->json(['message' => 'User deleted successfully'], 204);
    }

    public function inactivate(string $id)
    {
        $this->userService->inactivateUser($id);
        return response()->json(['message' => 'User inactivated successfully'], 200);
    }
}
