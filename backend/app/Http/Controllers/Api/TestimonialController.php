<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::orderBy('sort_order')->orderBy('id')->get();
        return response()->json(['testimonials' => $testimonials]);
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'quote'   => 'required|string',
            'name'    => 'required|string|max:255',
            'role'    => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'avatar'  => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'nullable|boolean',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('testimonials', 'public');
            $avatarPath = str_replace('testimonials/', '', $avatarPath);
        }

        $testimonial = Testimonial::create([
            'quote'       => $data['quote'],
            'name'        => $data['name'],
            'role'        => $data['role'],
            'company'     => $data['company'],
            'avatar_path' => $avatarPath,
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => $data['is_active'] ?? true,
            'created_by'  => $userId,
            'updated_by'  => $userId,
        ]);

        return response()->json(['testimonial' => $testimonial], 201);
    }

    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;
        $testimonial = Testimonial::findOrFail($id);

        $data = $request->validate([
            'quote'   => 'sometimes|string',
            'name'    => 'sometimes|string|max:255',
            'role'    => 'sometimes|string|max:255',
            'company' => 'sometimes|string|max:255',
            'avatar'  => 'nullable|image|max:2048',
            'sort_order' => 'sometimes|integer',
            'is_active'  => 'sometimes|boolean',
        ]);

        if ($request->hasFile('avatar')) {
            if ($testimonial->avatar_path) {
                $oldPath = storage_path('app/public/testimonials/' . $testimonial->avatar_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $avatarPath = $request->file('avatar')->store('testimonials', 'public');
            $data['avatar_path'] = str_replace('testimonials/', '', $avatarPath);
        }

        unset($data['avatar']);
        $data['updated_by'] = $userId;
        $testimonial->update($data);
        return response()->json(['testimonial' => $testimonial]);
    }

    public function destroy(Request $request, $id)
    {
        $userId = $request->user()->id;
        $testimonial = Testimonial::findOrFail($id);

        if ($testimonial->avatar_path) {
            $path = storage_path('app/public/testimonials/' . $testimonial->avatar_path);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $testimonial->update(['deleted_by' => $userId]);
        $testimonial->delete();
        return response()->json(['message' => 'Testimonial deleted']);
    }
}
