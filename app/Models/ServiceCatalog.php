<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catalog service (e.g. AC Service, RO Service) with types. Not to be confused with ServiceLead (bookings).
 */
class ServiceCatalog extends Model
{
    protected $table = 'services_catalog';

    protected $fillable = [
        'name',
        'description',
        'service_types',
    ];

    protected function casts(): array
    {
        return [
            'service_types' => 'array',
        ];
    }
}
