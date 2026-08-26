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

        // Non-superadmin can only view admins (read-only) if they have admins.view
        if (! $user->is_superadmin && ! $user->hasPermission('admins.view')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses menu Admin.');
        }

        $admins = User::with('roles')->orderByDesc('is_superadmin')->orderBy('name')->get();

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
            'is_superadmin' => 'nullable|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_superadmin' => $request->boolean('is_superadmin', false),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if (! empty($data['role_ids'])) {
            $admin->roles()->sync($data['role_ids']);
        }

        return response()->json(['admin' => $admin->load('roles')], 201);
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

        // Prevent a super admin from demoting themselves.
        if ($admin->id === $user->id && ! $request->boolean('is_superadmin', $admin->is_superadmin)) {
            throw ValidationException::withMessages([
                'is_superadmin' => ['Anda tidak dapat menonaktifkan status super admin untuk akun Anda sendiri.'],
            ]);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $admin->id,
            'password' => 'nullable|string|min:6',
            'is_superadmin' => 'nullable|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if (isset($data['name'])) {
            $admin->name = $data['name'];
        }
        if (isset($data['email'])) {
            $admin->email = $data['email'];
        }
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }
        if ($request->has('is_superadmin')) {
            $admin->is_superadmin = $request->boolean('is_superadmin');
        }
        $admin->updated_by = $user->id;
        $admin->save();

        if (array_key_exists('role_ids', $data)) {
            $admin->roles()->sync($data['role_ids'] ?? []);
        }

        return response()->json(['admin' => $admin->load('roles')]);
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
        $admin->deleted_by = $user->id;
        $admin->save();
        $admin->delete();

        return response()->json(['message' => 'Akun admin berhasil dihapus.']);
    }
}
