<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderTermPayment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function term()
    {
        return $this->belongsTo(OrderTerm::class, 'order_term_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
