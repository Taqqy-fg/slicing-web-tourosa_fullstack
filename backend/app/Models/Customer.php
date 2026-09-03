<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pic_name',
        'contact_info',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
