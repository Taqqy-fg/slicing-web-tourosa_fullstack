<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $guarded = [];

    protected $appends = ['avatar_url'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path) {
            return url('/storage/testimonials/' . $this->avatar_path);
        }
        return null;
    }
}
