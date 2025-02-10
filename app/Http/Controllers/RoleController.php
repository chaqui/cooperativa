<?php

namespace App\Http\Controllers;


use App\Traits\SqlMesage;
use Illuminate\Http\Request;

use App\Services\RoleService;
use App\Http\Resources\Rol as RolResource;
use App\Http\Requests\StoreRol;

class RoleController extends Controller
{
    private $roleService;

    use SqlMesage;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = $this->roleService->getRoles();
        return RolResource::collection($roles);
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
    public function store(StoreRol $request)
    {
        try {
            $role = $this->roleService->createRole($request->all());
            return response()->json($role, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->sqlMessageError($e)], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = $this->roleService->getRoleById($id);
        return new RolResource($role);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $role = $this->roleService->updateRole($request->all(), $id);
            return response()->json($role, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->sqlMessageError($e)], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->roleService->deleteRole($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->sqlMessageError($e)], 400);
        }
    }
}
