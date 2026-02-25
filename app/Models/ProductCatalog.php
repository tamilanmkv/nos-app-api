<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCatalog extends Model
{
    protected $table = 'products_catalog';

    protected $fillable = [
        'name',
        'description',
        'images',
        'variants',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'variants' => 'array',
        ];
    }
}
