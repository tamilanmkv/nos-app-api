<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceLeadController extends Controller
{
    /**
     * Store a new service lead (booking) from the NOS Android app.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bookingId' => ['required', 'string', 'max:50'],
            'serviceId' => ['required', 'string', 'max:50'],
            'serviceName' => ['required', 'string', 'max:255'],
            'serviceTypeId' => ['nullable', 'string', 'max:50'],
            'serviceTypeName' => ['nullable', 'string', 'max:255'],
            'serviceTypeDescription' => ['nullable', 'string'],
            'servicePrice' => ['nullable', 'numeric', 'min:0'],
            'serviceDuration' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'string'],
            'timeSlot' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'customerName' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'coordinates' => ['nullable', 'array'],
            'coordinates.latitude' => ['nullable', 'numeric'],
            'coordinates.longitude' => ['nullable', 'numeric'],
        ]);

        if (ServiceLead::where('booking_id', $validated['bookingId'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking ID already exists',
            ], 422);
        }

        $bookingDate = null;
        if (! empty($validated['date'])) {
            $bookingDate = \Carbon\Carbon::parse($validated['date'])->toDateString();
        }

        $lead = ServiceLead::create([
            'booking_id' => $validated['bookingId'],
            'service_id' => $validated['serviceId'],
            'service_name' => $validated['serviceName'],
            'service_type_id' => $validated['serviceTypeId'] ?? null,
            'service_type_name' => $validated['serviceTypeName'] ?? null,
            'service_type_description' => $validated['serviceTypeDescription'] ?? null,
            'service_price' => $validated['servicePrice'] ?? null,
            'service_duration' => $validated['serviceDuration'] ?? null,
            'booking_date' => $bookingDate,
            'time_slot' => $validated['timeSlot'] ?? null,
            'address' => $validated['address'] ?? null,
            'street' => $validated['street'] ?? null,
            'city' => $validated['city'] ?? null,
            'district' => $validated['district'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'customer_name' => $validated['customerName'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'coordinates' => $validated['coordinates'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully',
            'data' => [
                'id' => $lead->id,
                'bookingId' => $lead->booking_id,
            ],
        ], 201);
    }
}
