<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesLead extends Model
{
    protected $table = 'sales_leads';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'variant_id',
        'variant_name',
        'variant_specifications',
        'quantity',
        'unit_price',
        'total_amount',
        'customer_name',
        'phone',
        'email',
        'address',
        'street',
        'city',
        'district',
        'state',
        'pincode',
        'payment_method',
        'order_date',
        'product_details',
        'status',
        'assigned_to',
        'place_id',
    ];

    protected function casts(): array
    {
        return [
            'variant_specifications' => 'array',
            'product_details' => 'array',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'order_date' => 'datetime',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
