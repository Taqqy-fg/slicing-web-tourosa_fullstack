<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Dapatkan semua permission.
     */
    public function index()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        $grouped = $permissions->groupBy('group')->map(function ($items, $group) {
            return [
                'group' => $group,
                'items' => $items,
            ];
        })->values();

        return response()->json(['permissions' => $grouped]);
    }

    /**
     * Dapatkan semua permission flat (untuk form assign).
     */
    public function all()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return response()->json(['permissions' => $permissions]);
    }
}
