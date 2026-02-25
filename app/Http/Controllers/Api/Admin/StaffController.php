<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * List staff with pagination, search, role and date filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Staff::query()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('role', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
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

        $items = $paginator->getCollection()->map(fn (Staff $staff) => $this->formatStaff($staff));

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
     * Get a single staff.
     */
    public function show(int $id): JsonResponse
    {
        $staff = Staff::find($id);

        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatStaff($staff),
        ]);
    }

    /**
     * Create a new staff.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:staff,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'placeId' => ['nullable', 'integer', 'exists:places,id'],
        ]);

        $staff = Staff::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'] ?? null,
            'address' => $validated['address'] ?? null,
            'place_id' => $validated['placeId'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff created successfully',
            'data' => $this->formatStaff($staff),
        ], 201);
    }

    /**
     * Update a staff.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $staff = Staff::find($id);

        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:staff,email,' . $id],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'placeId' => ['nullable', 'integer', 'exists:places,id'],
        ]);

        if (array_key_exists('placeId', $validated)) {
            $staff->place_id = $validated['placeId'];
        }
        unset($validated['placeId']);
        $staff->fill($validated)->save();

        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully',
            'data' => $this->formatStaff($staff->fresh()),
        ]);
    }

    /**
     * Delete a staff.
     */
    public function destroy(int $id): JsonResponse
    {
        $staff = Staff::find($id);

        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully',
        ]);
    }

    private function formatStaff(Staff $staff): array
    {
        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'role' => $staff->role,
            'address' => $staff->address,
            'placeId' => $staff->place_id,
            'place' => $staff->place ? $staff->place->name : null,
            'createdAt' => $staff->created_at?->toIso8601String(),
            'updatedAt' => $staff->updated_at?->toIso8601String(),
        ];
    }
}
