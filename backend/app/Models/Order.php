<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    /**
     * Relasi ke OrderInfo (parent/primary table).
     * orders.order_info_id → order_infos.id
     */
    public function orderInfo()
    {
        return $this->belongsTo(OrderInfo::class, 'order_info_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function expenses()
    {
        return $this->hasMany(OrderExpense::class);
    }

    public function terms()
    {
        return $this->hasMany(OrderTerm::class);
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }
}
