<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Dapatkan semua akun admin.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->is_superadmin && ! $user->hasPermission('admins.view')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses menu Admin.');
        }

        $admins = User::with('roles', 'directPermissions')->orderByDesc('is_superadmin')->orderBy('name')->get();

        return response()->json(['admins' => $admins]);
    }

    /**
     * Buat akun admin baru.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->is_superadmin && ! $user->hasPermission('admins.create')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat akun admin.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|exists:roles,id',
            'direct_permission_ids' => 'nullable|array',
            'direct_permission_ids.*' => 'exists:permissions,id',
        ]);

        $isSuperadmin = false;
        if (! empty($data['role_id'])) {
            $role = \App\Models\Role::find($data['role_id']);
            $isSuperadmin = $role && $role->name === 'super_admin';
        }

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_superadmin' => $isSuperadmin,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if (! empty($data['role_id'])) {
            $admin->roles()->sync([$data['role_id']]);
        }
        if (! empty($data['direct_permission_ids'])) {
            $admin->directPermissions()->sync($data['direct_permission_ids']);
        }

        return response()->json(['admin' => $admin->load('roles', 'directPermissions')], 201);
    }

    /**
     * Update akun admin berdasarkan ID.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (! $user->is_superadmin && ! $user->hasPermission('admins.update')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah akun admin.');
        }

        $admin = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $admin->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'nullable|exists:roles,id',
            'direct_permission_ids' => 'nullable|array',
            'direct_permission_ids.*' => 'exists:permissions,id',
        ]);

        // Prevent a super admin from demoting themselves
        if ($admin->id === $user->id && array_key_exists('role_id', $data)) {
            $targetRole = $data['role_id'] ? \App\Models\Role::find($data['role_id']) : null;
            if (! $targetRole || $targetRole->name !== 'super_admin') {
                throw ValidationException::withMessages([
                    'role_id' => ['Anda tidak dapat mengubah role Anda sendiri dari Super Admin.'],
                ]);
            }
        }

        // Derive is_superadmin from the role
        $isSuperadmin = $admin->is_superadmin;
        if (array_key_exists('role_id', $data)) {
            if (! empty($data['role_id'])) {
                $role = \App\Models\Role::find($data['role_id']);
                $isSuperadmin = $role && $role->name === 'super_admin';
            } else {
                $isSuperadmin = false;
            }
        }

        if (isset($data['name'])) {
            $admin->name = $data['name'];
        }
        if (isset($data['email'])) {
            $admin->email = $data['email'];
        }
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }
        $admin->is_superadmin = $isSuperadmin;
        $admin->updated_by = $user->id;
        $admin->save();

        if (array_key_exists('role_id', $data)) {
            $admin->roles()->sync($data['role_id'] ? [$data['role_id']] : []);
        }
        if (array_key_exists('direct_permission_ids', $data)) {
            $admin->directPermissions()->sync($data['direct_permission_ids'] ?? []);
        }

        return response()->json(['admin' => $admin->load('roles', 'directPermissions')]);
    }

    /**
     * Hapus akun admin berdasarkan ID.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (! $user->is_superadmin && ! $user->hasPermission('admins.delete')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus akun admin.');
        }

        $admin = User::findOrFail($id);

        // Prevent deleting your own account.
        if ($admin->id === $user->id) {
            throw ValidationException::withMessages([
                'email' => ['Anda tidak dapat menghapus akun Anda sendiri.'],
            ]);
        }

        $admin->roles()->detach();
        $admin->directPermissions()->detach();
        $admin->deleted_by = $user->id;
        $admin->save();
        $admin->delete();

        return response()->json(['message' => 'Akun admin berhasil dihapus.']);
    }
}
