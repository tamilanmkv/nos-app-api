<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * List customers (app users) with search, date range, place filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AppUser::query()->with('place')->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%");
            });
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

        $perPage = min((int) $request->input('per_page', self::PER_PAGE), 200);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (AppUser $user) => $this->formatCustomer($user));

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
     * Create a customer (app user).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:app_users,phone'],
            'dob' => ['nullable', 'date'],
            'placeId' => ['nullable', 'integer', 'exists:places,id'],
        ]);

        $user = AppUser::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'dob' => $validated['dob'] ?? null,
            'place_id' => $validated['placeId'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $this->formatCustomer($user->fresh('place')),
        ], 201);
    }

    /**
     * Update a customer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = AppUser::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:app_users,phone,' . $id],
            'dob' => ['nullable', 'date'],
            'placeId' => ['nullable', 'integer', 'exists:places,id'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'] ?? $user->phone;
        }
        if (array_key_exists('dob', $validated)) {
            $user->dob = $validated['dob'];
        }
        if (array_key_exists('placeId', $validated)) {
            $user->place_id = $validated['placeId'];
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $this->formatCustomer($user->fresh('place')),
        ]);
    }

    /**
     * Delete a customer.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = AppUser::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }

    private function formatCustomer(AppUser $user): array
    {
        return [
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'email' => $user->email,
            'city' => $user->city,
            'dob' => $user->dob?->format('Y-m-d'),
            'placeId' => $user->place_id,
            'place' => $user->place?->name,
            'isLoggedIn' => true,
            'createdAt' => $user->created_at?->toIso8601String(),
            'updatedAt' => $user->updated_at?->toIso8601String(),
        ];
    }
}
