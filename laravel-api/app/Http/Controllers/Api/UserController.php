<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles:id,name');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array($request->query('sort_by'), ['name', 'email', 'created_at']) ? $request->query('sort_by') : 'created_at';
        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return response()->json(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function store(UserRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => bcrypt($request->validated('password')),
        ]);
        $user->syncRoles([$this->resolveRole($request->validated('role'))]);

        return response()->json($user->load('roles:id,name'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles:id,name'));
    }

    public function update(UserRequest $request, User $user)
    {
        $user->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            ...($request->filled('password') ? ['password' => bcrypt($request->validated('password'))] : []),
        ]);
        $user->syncRoles([$this->resolveRole($request->validated('role'))]);

        return response()->json($user->load('roles:id,name'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    /**
     * Look up the role by name explicitly, rather than passing the bare name to syncRoles(): Sanctum's
     * auth middleware switches the app's default auth guard to "sanctum" for the rest of the request,
     * and syncRoles()'s name-based lookup would otherwise search for a role under that guard instead of
     * the "web" guard roles are actually seeded/created with.
     */
    private function resolveRole(string $name): Role
    {
        return Role::where('name', $name)->where('guard_name', 'web')->firstOrFail();
    }
}
