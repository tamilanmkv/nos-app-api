<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'address',
        'place_id',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
