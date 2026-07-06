<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query()->with('permissions:id,name')->withCount('users');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = in_array($request->query('sort_by'), ['name', 'created_at']) ? $request->query('sort_by') : 'name';
        $sortDir = $request->query('sort_dir') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir);

        return response()->json(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function store(RoleRequest $request)
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions', []));

        return response()->json($role->load('permissions:id,name'), 201);
    }

    public function show(Role $role)
    {
        return response()->json($role->load('permissions:id,name'));
    }

    public function update(RoleRequest $request, Role $role)
    {
        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return response()->json($role->load('permissions:id,name'));
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['Administrator'])) {
            return response()->json(['message' => 'This role cannot be deleted.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted']);
    }

    public function permissionOptions()
    {
        return response()->json(Permission::query()->select('id', 'name')->orderBy('name')->get());
    }

    public function roleOptions()
    {
        return response()->json(Role::query()->select('id', 'name')->orderBy('name')->get());
    }
}
