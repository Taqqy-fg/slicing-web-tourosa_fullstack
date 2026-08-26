<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderInfo;
use Illuminate\Http\Request;

class OrderInfoController extends Controller
{
    /**
     * Ambil semua informasi pesanan.
     */
    public function index()
    {
        $infos = OrderInfo::orderBy('id', 'desc')->get();

        return response()->json([
            'data' => $infos->map(fn ($i) => $this->format($i))
        ]);
    }

    /**
     * Buat informasi pesanan baru.
     */
    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'group_name'   => 'required|string|max:255',
            'pic_name'     => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:100',
            'email'        => 'nullable|string|email|max:255',
            'address'      => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $info = OrderInfo::create([
            'group_name'   => $data['group_name'],
            'pic_name'     => $data['pic_name'] ?? null,
            'contact_info' => $data['contact_info'] ?? null,
            'email'        => $data['email'] ?? null,
            'address'      => $data['address'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'created_by'   => $userId,
            'updated_by'   => $userId,
        ]);

        return response()->json([
            'message' => 'Informasi pesanan berhasil dibuat.',
            'data'    => $this->format($info),
        ], 201);
    }

    /**
     * Ambil satu informasi pesanan berdasarkan ID.
     */
    public function show($id)
    {
        $info = OrderInfo::findOrFail($id);
        return response()->json(['data' => $this->format($info)]);
    }

    /**
     * Update informasi pesanan berdasarkan ID.
     */
    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;
        $info = OrderInfo::findOrFail($id);

        $data = $request->validate([
            'group_name'   => 'required|string|max:255',
            'pic_name'     => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:100',
            'email'        => 'nullable|string|email|max:255',
            'address'      => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $info->update([
            'group_name'   => $data['group_name'],
            'pic_name'     => $data['pic_name'] ?? null,
            'contact_info' => $data['contact_info'] ?? null,
            'email'        => $data['email'] ?? null,
            'address'      => $data['address'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'updated_by'   => $userId,
        ]);

        return response()->json([
            'message' => 'Informasi pesanan berhasil diperbarui.',
            'data'    => $this->format($info->fresh()),
        ]);
    }

    /**
     * Hapus (soft delete) informasi pesanan berdasarkan ID.
     */
    public function destroy(Request $request, $id)
    {
        $userId = $request->user()->id;
        $info = OrderInfo::findOrFail($id);

        $info->update(['deleted_by' => $userId]);
        $info->delete();

        return response()->json(['message' => 'Informasi pesanan berhasil dihapus.']);
    }

    /**
     * Format data untuk response JSON.
     */
    private function format(OrderInfo $info): array
    {
        return [
            'id'           => $info->id,
            'group_name'   => $info->group_name,
            'pic_name'     => $info->pic_name,
            'contact_info' => $info->contact_info,
            'email'        => $info->email,
            'address'      => $info->address,
            'notes'        => $info->notes,
            'created_at'   => $info->created_at?->toDateString(),
        ];
    }
}
