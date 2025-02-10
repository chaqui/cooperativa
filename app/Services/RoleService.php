<?php

namespace App\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\Role;

class RoleService
{
    public function createRole($data)
    {
        Role::create($data);
    }

    public function updateRole($data, $id)
    {
        $role = $this->getRoleById($id);
        $role->update($data);
    }

    public function deleteRole($id)
    {
        $role = $this->getRoleById($id);
        $role->delete();
    }

    public function getRoles()
    {
        return Role::all();
    }

    public function getRoleById($id)
    {
        $role = Role::find($id);
        if ($role) {
            return $role;
        }
        throw new ModelNotFoundException('Role not found');
    }


}
