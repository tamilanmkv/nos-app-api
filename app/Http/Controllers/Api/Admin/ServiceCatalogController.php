<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD for service catalog (Manage Services page). Not service leads/bookings.
 */
class ServiceCatalogController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * List catalog services with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServiceCatalog::query()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = min((int) $request->input('per_page', self::PER_PAGE), 100);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (ServiceCatalog $s) => $this->formatService($s));

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
     * Get a single catalog service.
     */
    public function show(int $id): JsonResponse
    {
        $service = ServiceCatalog::find($id);

        if (! $service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatService($service),
        ]);
    }

    /**
     * Create a new catalog service.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'serviceTypes' => ['required', 'array', 'min:1'],
            'serviceTypes.*.name' => ['required', 'string', 'max:255'],
            'serviceTypes.*.description' => ['nullable', 'string'],
            'serviceTypes.*.price' => ['required', 'numeric', 'min:0'],
            'serviceTypes.*.duration' => ['nullable', 'string', 'max:100'],
        ]);

        $types = [];
        foreach ($validated['serviceTypes'] as $i => $t) {
            $types[] = [
                'id' => 'srv-type-' . ($i + 1) . '-' . uniqid(),
                'name' => $t['name'],
                'description' => $t['description'] ?? '',
                'price' => (float) $t['price'],
                'duration' => $t['duration'] ?? '',
            ];
        }

        $service = ServiceCatalog::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'service_types' => $types,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => $this->formatService($service),
        ], 201);
    }

    /**
     * Update a catalog service.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $service = ServiceCatalog::find($id);

        if (! $service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'serviceTypes' => ['sometimes', 'required', 'array', 'min:1'],
            'serviceTypes.*.id' => ['nullable', 'string'],
            'serviceTypes.*.name' => ['required', 'string', 'max:255'],
            'serviceTypes.*.description' => ['nullable', 'string'],
            'serviceTypes.*.price' => ['required', 'numeric', 'min:0'],
            'serviceTypes.*.duration' => ['nullable', 'string', 'max:100'],
        ]);

        if (array_key_exists('name', $validated)) {
            $service->name = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $service->description = $validated['description'];
        }
        if (array_key_exists('serviceTypes', $validated)) {
            $types = [];
            foreach ($validated['serviceTypes'] as $i => $t) {
                $types[] = [
                    'id' => $t['id'] ?? 'srv-type-' . ($i + 1) . '-' . uniqid(),
                    'name' => $t['name'],
                    'description' => $t['description'] ?? '',
                    'price' => (float) $t['price'],
                    'duration' => $t['duration'] ?? '',
                ];
            }
            $service->service_types = $types;
        }
        $service->save();

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
            'data' => $this->formatService($service->fresh()),
        ]);
    }

    /**
     * Delete a catalog service.
     */
    public function destroy(int $id): JsonResponse
    {
        $service = ServiceCatalog::find($id);

        if (! $service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully',
        ]);
    }

    private function formatService(ServiceCatalog $service): array
    {
        $types = $service->service_types ?? [];
        foreach ($types as &$t) {
            if (! isset($t['price'])) {
                $t['price'] = 0;
            }
            $t['price'] = (float) $t['price'];
        }

        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'serviceTypes' => $types,
            'createdAt' => $service->created_at?->toIso8601String(),
            'updatedAt' => $service->updated_at?->toIso8601String(),
        ];
    }
}
