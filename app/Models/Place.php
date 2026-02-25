<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $table = 'places';

    protected $fillable = [
        'name',
        'address',
        'is_active',
        'service_ids',
        'product_ids',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'service_ids' => 'array',
            'product_ids' => 'array',
        ];
    }
}
