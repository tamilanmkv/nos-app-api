<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SalesLeadController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * Create a sales lead (admin manual entry).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'product' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:Yet to start,In progress,Completed,pending,in_progress,completed'],
            'assignedTo' => ['nullable', 'string', 'max:50'],
            'placeId' => ['nullable', 'integer', 'exists:places,id'],
        ]);

        $status = isset($validated['status'])
            ? (config('lead.status_values')[$validated['status']] ?? $validated['status'])
            : 'pending';

        $orderId = 'ADMIN-' . Str::upper(Str::random(8)) . '-' . time();

        $lead = SalesLead::create([
            'order_id' => $orderId,
            'product_id' => 'manual',
            'product_name' => $validated['product'],
            'quantity' => 1,
            'unit_price' => 0,
            'total_amount' => 0,
            'customer_name' => $validated['customerName'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'status' => $status,
            'assigned_to' => $validated['assignedTo'] ?? null,
            'place_id' => $validated['placeId'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sales lead created successfully',
            'data' => $this->formatSalesLead($lead->fresh()),
        ], 201);
    }

    /**
     * List sales leads with pagination, search, status and date filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SalesLead::query()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('product_name', 'like', "%{$term}%")
                    ->orWhere('order_id', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $statusValue = config('lead.status_values')[$request->input('status')] ?? $request->input('status');
            $query->where('status', $statusValue);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('place_id')) {
            $query->where('place_id', $request->input('place_id'));
        }

        $perPage = min((int) $request->input('per_page', self::PER_PAGE), 100);
        $paginator = $query->with('place')->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (SalesLead $lead) => $this->formatSalesLead($lead));

        return response()->json([
            'success' => true,
            'data' => $items->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get a single sales lead.
     */
    public function show(int $id): JsonResponse
    {
        $lead = SalesLead::find($id);

        if (! $lead) {
            return response()->json(['success' => false, 'message' => 'Sales lead not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatSalesLead($lead),
        ]);
    }

    /**
     * Update sales lead status and/or assigned_to.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $lead = SalesLead::find($id);

        if (! $lead) {
            return response()->json(['success' => false, 'message' => 'Sales lead not found'], 404);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:Yet to start,In progress,Completed,pending,in_progress,completed'],
            'assignedTo' => ['nullable', 'string', 'max:50'],
            'placeId' => ['nullable', 'integer', 'exists:places,id'],
        ]);

        if (isset($validated['status'])) {
            $status = config('lead.status_values')[$validated['status']] ?? $validated['status'];
            $lead->status = $status;
        }
        if (array_key_exists('assignedTo', $validated)) {
            $lead->assigned_to = $validated['assignedTo'];
        }
        if (array_key_exists('placeId', $validated)) {
            $lead->place_id = $validated['placeId'];
        }

        $lead->save();

        return response()->json([
            'success' => true,
            'message' => 'Sales lead updated successfully',
            'data' => $this->formatSalesLead($lead->fresh()),
        ]);
    }

    private function formatSalesLead(SalesLead $lead): array
    {
        $labels = config('lead.status_labels', [
            'pending' => 'Yet to start',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
        ]);

        return [
            'id' => $lead->id,
            'orderId' => $lead->order_id,
            'customerName' => $lead->customer_name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'product' => $lead->product_name,
            'productId' => $lead->product_id,
            'quantity' => $lead->quantity,
            'unitPrice' => $lead->unit_price ? (float) $lead->unit_price : null,
            'totalAmount' => $lead->total_amount ? (float) $lead->total_amount : null,
            'status' => $labels[$lead->status] ?? $lead->status,
            'assignedTo' => $lead->assigned_to !== null ? (string) $lead->assigned_to : null,
            'placeId' => $lead->place_id,
            'place' => $lead->place ? $lead->place->name : null,
            'createdAt' => $lead->created_at?->toIso8601String(),
            'address' => $lead->address,
            'city' => $lead->city,
            'district' => $lead->district,
            'state' => $lead->state,
            'pincode' => $lead->pincode,
            'paymentMethod' => $lead->payment_method,
            'orderDate' => $lead->order_date?->toIso8601String(),
        ];
    }
}
