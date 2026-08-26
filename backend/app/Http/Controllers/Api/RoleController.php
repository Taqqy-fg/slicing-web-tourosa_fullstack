<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Dapatkan semua role.
     */
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return response()->json(['roles' => $roles]);
    }

    /**
     * Buat role baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
        ]);

        if (!empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return response()->json(['role' => $role->load('permissions')], 201);
    }

    /**
     * Dapatkan detail role berdasarkan ID.
     */
    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json(['role' => $role]);
    }

    /**
     * Update role berdasarkan ID.
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
            'label' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(collect($data)->only(['name', 'label', 'description'])->toArray());

        if (array_key_exists('permissions', $data)) {
            $role->permissions()->sync($data['permissions'] ?? []);
        }

        return response()->json(['role' => $role->load('permissions')]);
    }

    /**
     * Hapus role berdasarkan ID.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return response()->json(['message' => 'Role system tidak dapat dihapus.'], 403);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Role masih digunakan oleh admin. Hapus atau ubah role admin terlebih dahulu.'], 400);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json(['message' => 'Role berhasil dihapus.']);
    }
}
