<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUser extends Model
{
    protected $table = 'app_users';

    protected $fillable = [
        'phone',
        'name',
        'email',
        'city',
        'dob',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }
}
