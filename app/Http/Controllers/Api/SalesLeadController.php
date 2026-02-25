<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesLeadController extends Controller
{
    /**
     * Store a new sales lead (product order) from the NOS Android app.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orderId' => ['required', 'string', 'max:50'],
            'productId' => ['required', 'string', 'max:50'],
            'productName' => ['required', 'string', 'max:255'],
            'variantId' => ['nullable', 'string', 'max:50'],
            'variantName' => ['nullable', 'string', 'max:255'],
            'variantSpecifications' => ['nullable', 'array'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'totalAmount' => ['required', 'numeric', 'min:0'],
            'customerName' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'paymentMethod' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'string'],
            'productDetails' => ['nullable', 'array'],
        ]);

        if (SalesLead::where('order_id', $validated['orderId'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Order ID already exists',
            ], 422);
        }

        $lead = SalesLead::create([
            'order_id' => $validated['orderId'],
            'product_id' => $validated['productId'],
            'product_name' => $validated['productName'],
            'variant_id' => $validated['variantId'] ?? null,
            'variant_name' => $validated['variantName'] ?? null,
            'variant_specifications' => $validated['variantSpecifications'] ?? null,
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unitPrice'],
            'total_amount' => $validated['totalAmount'],
            'customer_name' => $validated['customerName'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'street' => $validated['street'] ?? null,
            'city' => $validated['city'] ?? null,
            'district' => $validated['district'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'payment_method' => $validated['paymentMethod'] ?? 'Cash on Delivery',
            'order_date' => isset($validated['date']) ? $validated['date'] : null,
            'product_details' => $validated['productDetails'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully',
            'data' => [
                'id' => $lead->id,
                'orderId' => $lead->order_id,
            ],
        ], 201);
    }
}
