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
     * Ensure the acting user is a super admin.
     */
    protected function ensureSuperAdmin(Request $request)
    {
        if (! $request->user() || ! $request->user()->is_superadmin) {
            abort(403, 'Hanya super admin yang dapat mengelola akun admin.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $admins = User::orderByDesc('is_superadmin')
            ->orderBy('name')
            ->get();

        return response()->json(['admins' => $admins]);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'is_superadmin' => 'nullable|boolean',
        ]);

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_superadmin' => $request->boolean('is_superadmin', false),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['admin' => $admin], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        $admin = User::findOrFail($id);

        // Prevent a super admin from demoting themselves.
        if ($admin->id === $request->user()->id && ! $request->boolean('is_superadmin', $admin->is_superadmin)) {
            throw ValidationException::withMessages([
                'is_superadmin' => ['Anda tidak dapat menonaktifkan status super admin untuk akun Anda sendiri.'],
            ]);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $admin->id,
            'password' => 'nullable|string|min:6',
            'is_superadmin' => 'nullable|boolean',
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
        $admin->updated_by = $request->user()->id;
        $admin->save();

        return response()->json(['admin' => $admin]);
    }

    public function destroy(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        $admin = User::findOrFail($id);

        // Prevent deleting your own account.
        if ($admin->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'email' => ['Anda tidak dapat menghapus akun Anda sendiri.'],
            ]);
        }

        $admin->deleted_by = $request->user()->id;
        $admin->save();
        $admin->delete();

        return response()->json(['message' => 'Akun admin berhasil dihapus.']);
    }
}
