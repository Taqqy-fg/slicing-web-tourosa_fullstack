<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_infos';

    protected $fillable = [
        'group_name',
        'pic_name',
        'contact_info',
        'email',
        'address',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Relasi ke banyak Order (child table).
     * order_infos.id ← orders.order_info_id
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'order_info_id');
    }
}
