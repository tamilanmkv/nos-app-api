<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLead extends Model
{
    protected $table = 'service_leads';

    protected $fillable = [
        'booking_id',
        'service_id',
        'service_name',
        'service_type_id',
        'service_type_name',
        'service_type_description',
        'service_price',
        'service_duration',
        'booking_date',
        'time_slot',
        'address',
        'street',
        'city',
        'district',
        'state',
        'pincode',
        'customer_name',
        'phone',
        'email',
        'coordinates',
        'status',
        'assigned_to',
        'place_id',
    ];

    protected function casts(): array
    {
        return [
            'coordinates' => 'array',
            'service_price' => 'decimal:2',
            'booking_date' => 'date',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
