<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUser extends Model
{
    protected $table = 'app_users';

    protected $fillable = [
        'phone',
        'name',
        'email',
        'city',
        'dob',
        'place_id',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
