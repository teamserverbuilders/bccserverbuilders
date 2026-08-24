<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /** Canonical permission list used by seeder + runtime ensure. */
    public static function permissionCatalog(): array
    {
        return [
            'tax-declarations.view', 'tax-declarations.create', 'tax-declarations.edit',
            'tax-declarations.delete', 'tax-declarations.approve', 'tax-declarations.archive',
            'field-appraisals.view', 'field-appraisals.create', 'field-appraisals.edit', 'field-appraisals.delete',
            'properties.view', 'properties.create', 'properties.edit', 'properties.delete',
            'ocr.view', 'ocr.upload', 'ocr.scan', 'ocr.correct',
            'gis.view', 'gis.edit',
            'documents.view', 'documents.upload', 'documents.delete',
            'workflow.view', 'workflow.manage',
            'archive.view', 'archive.restore', 'archive.delete',
            'reports.view', 'reports.export',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'settings.view', 'settings.edit',
            'audit.view',
            'backup.create', 'backup.restore',
        ];
    }

    public function index(Request $request)
    {
        $query = User::with(['department', 'position', 'roles', 'permissions'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            })
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'role' => 'nullable|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department_id' => $request->department_id,
            'position_id' => $request->position_id,
            'employee_id' => $request->employee_id,
            'contact_number' => $request->contact_number,
            'status' => 'active',
            'force_password_change' => true,
        ]);

        if ($request->role) {
            $user->assignRole($request->role);
            // Copy role permissions as direct so User Management can customize later
            $role = Role::findByName($request->role, 'web');
            $user->syncPermissions($role->permissions);
        }

        return response()->json($user->load(['department', 'position', 'roles', 'permissions']), 201);
    }

    public function show(User $user)
    {
        $user->load(['department', 'position', 'roles.permissions', 'permissions', 'loginLogs' => fn ($q) => $q->latest()->limit(10)]);
        $data = $user->toArray();
        $data['permission_names'] = $user->getAllPermissions()->pluck('name')->values()->all();
        $data['direct_permission_names'] = $user->getPermissionNames()->values()->all();

        return response()->json($data);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
        ]);

        $user->update($request->only(['name', 'email', 'department_id', 'position_id', 'employee_id', 'contact_number', 'status']));

        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json($user->fresh()->load(['department', 'position', 'roles', 'permissions']));
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:8']);
        $user->update([
            'password' => Hash::make($request->password),
            'force_password_change' => true,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
        return response()->json(['message' => 'Password reset successfully.']);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        return response()->json($user->fresh());
    }

    /**
     * Admin assigns role + exact function permissions for a user.
     * Selected permissions become the user's direct access list (source of truth with role).
     */
    public function syncPermissions(Request $request, User $user)
    {
        $this->ensurePermissionsExist();

        $request->validate([
            'role' => 'nullable|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($request->has('role')) {
            if ($request->role) {
                $user->syncRoles([$request->role]);
            } else {
                $user->syncRoles([]);
            }
        }

        $user->syncPermissions($request->permissions ?? []);

        $user->load(['department', 'position', 'roles.permissions', 'permissions']);

        return response()->json([
            'message' => 'Permissions updated.',
            'user' => array_merge($user->toArray(), [
                'permission_names' => $user->getAllPermissions()->pluck('name')->values()->all(),
                'direct_permission_names' => $user->getPermissionNames()->values()->all(),
            ]),
        ]);
    }

    public function roles()
    {
        $userCounts = $this->roleUserCounts();

        return response()->json(
            Role::with('permissions')
                ->orderBy('name')
                ->get()
                ->map(function (Role $role) use ($userCounts) {
                    $data = $role->toArray();
                    $data['is_protected'] = $this->isProtectedRole($role);
                    $data['permissions_count'] = $role->permissions->count();
                    $data['users_count'] = (int) ($userCounts[$role->id] ?? 0);
                    return $data;
                })
                ->values()
        );
    }

    public function permissions()
    {
        $this->ensurePermissionsExist();

        return response()->json(Permission::orderBy('name')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0];
        }));
    }

    public function storeRole(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:roles,name']);
        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json($this->formatRole($role->fresh()->load('permissions')), 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        if ($this->isProtectedRole($role) && $request->filled('name') && $request->name !== $role->name) {
            return response()->json(['message' => 'Protected admin roles cannot be renamed.'], 403);
        }

        if ($request->filled('name') && !$this->isProtectedRole($role)) {
            $request->validate(['name' => 'string|max:255|unique:roles,name,' . $role->id]);
            $role->update(['name' => $request->name]);
        }

        if ($request->permissions !== null) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json(
            $this->formatRole($role->fresh()->load('permissions'))
        );
    }

    public function destroyRole(Role $role)
    {
        if ($this->isProtectedRole($role)) {
            return response()->json(['message' => 'Protected admin roles cannot be deleted.'], 403);
        }

        $userCount = $this->countUsersForRole($role->id);
        if ($userCount > 0) {
            return response()->json([
                'message' => "Cannot delete this role. It is assigned to {$userCount} user(s). Reassign them first.",
            ], 422);
        }

        $role->syncPermissions([]);
        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }

    private function isProtectedRole(Role $role): bool
    {
        $name = strtolower(trim($role->name));
        return in_array($name, [
            'super administrator',
            'super admin',
            'administrator',
            'admin',
        ], true);
    }

    /** @return array<int,int> role_id => user count */
    private function roleUserCounts(): array
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roleKey = config('permission.column_names.role_pivot_key')
            ?? config('permission.column_names.role_morph_key')
            ?? 'role_id';
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');

        return DB::table($table)
            ->where('model_type', User::class)
            ->select($roleKey, DB::raw('COUNT(DISTINCT ' . $morphKey . ') as aggregate'))
            ->groupBy($roleKey)
            ->pluck('aggregate', $roleKey)
            ->map(fn ($c) => (int) $c)
            ->all();
    }

    private function countUsersForRole(int $roleId): int
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roleKey = config('permission.column_names.role_pivot_key')
            ?? config('permission.column_names.role_morph_key')
            ?? 'role_id';

        return (int) DB::table($table)
            ->where($roleKey, $roleId)
            ->where('model_type', User::class)
            ->count();
    }

    private function formatRole(Role $role): array
    {
        $data = $role->toArray();
        $data['is_protected'] = $this->isProtectedRole($role);
        $data['permissions_count'] = $role->relationLoaded('permissions')
            ? $role->permissions->count()
            : $role->permissions()->count();
        $data['users_count'] = $this->countUsersForRole((int) $role->id);
        return $data;
    }

    public function departments()
    {
        return response()->json(Department::with('positions')->where('is_active', true)->get());
    }

    public function positions()
    {
        return response()->json(Position::with('department')->where('is_active', true)->get());
    }

    private function ensurePermissionsExist(): void
    {
        foreach (self::permissionCatalog() as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }
}
