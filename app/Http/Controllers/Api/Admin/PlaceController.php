<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * List places with pagination, search, status and date filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Place::query()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = min((int) $request->input('per_page', self::PER_PAGE), 100);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (Place $place) => $this->formatPlace($place));

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
     * Get a single place.
     */
    public function show(int $id): JsonResponse
    {
        $place = Place::find($id);

        if (! $place) {
            return response()->json(['success' => false, 'message' => 'Place not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPlace($place),
        ]);
    }

    /**
     * Create a new place.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'isActive' => ['nullable', 'boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer'],
        ]);

        $place = Place::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
            'service_ids' => $validated['services'] ?? [],
            'product_ids' => $validated['products'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Place created successfully',
            'data' => $this->formatPlace($place),
        ], 201);
    }

    /**
     * Update a place.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $place = Place::find($id);

        if (! $place) {
            return response()->json(['success' => false, 'message' => 'Place not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'isActive' => ['nullable', 'boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer'],
        ]);

        if (array_key_exists('name', $validated)) {
            $place->name = $validated['name'];
        }
        if (array_key_exists('address', $validated)) {
            $place->address = $validated['address'];
        }
        if (array_key_exists('isActive', $validated)) {
            $place->is_active = $validated['isActive'];
        }
        if (array_key_exists('services', $validated)) {
            $place->service_ids = $validated['services'];
        }
        if (array_key_exists('products', $validated)) {
            $place->product_ids = $validated['products'];
        }
        $place->save();

        return response()->json([
            'success' => true,
            'message' => 'Place updated successfully',
            'data' => $this->formatPlace($place->fresh()),
        ]);
    }

    /**
     * Delete a place.
     */
    public function destroy(int $id): JsonResponse
    {
        $place = Place::find($id);

        if (! $place) {
            return response()->json(['success' => false, 'message' => 'Place not found'], 404);
        }

        $place->delete();

        return response()->json([
            'success' => true,
            'message' => 'Place deleted successfully',
        ]);
    }

    private function formatPlace(Place $place): array
    {
        return [
            'id' => $place->id,
            'name' => $place->name,
            'address' => $place->address,
            'isActive' => $place->is_active,
            'services' => $place->service_ids ?? [],
            'products' => $place->product_ids ?? [],
            'createdAt' => $place->created_at?->toIso8601String(),
            'updatedAt' => $place->updated_at?->toIso8601String(),
        ];
    }
}
