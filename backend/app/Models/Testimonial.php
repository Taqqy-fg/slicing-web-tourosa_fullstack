<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Testimonial extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $appends = ['avatar_url'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path) {
            return url('/storage/testimonials/' . $this->avatar_path);
        }
        return null;
    }
}
