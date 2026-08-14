<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(CatalogItem::class);
    }
}
