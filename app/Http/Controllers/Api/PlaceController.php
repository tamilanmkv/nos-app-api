<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\ProductCatalog;
use App\Models\ServiceCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    /**
     * List places for mobile app.
     * By default only active places are returned.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Place::query()->orderBy('name');

        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        }

        $places = $query->get();

        $serviceIds = $places
            ->pluck('service_ids')
            ->flatten()
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $productIds = $places
            ->pluck('product_ids')
            ->flatten()
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $servicesById = ServiceCatalog::whereIn('id', $serviceIds)->get(['id', 'name'])->keyBy('id');
        $productsById = ProductCatalog::whereIn('id', $productIds)->get(['id', 'name'])->keyBy('id');

        $data = $places->map(function (Place $place) use ($servicesById, $productsById) {
            $serviceList = collect($place->service_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->map(function (int $id) use ($servicesById) {
                    $service = $servicesById->get($id);
                    return $service ? ['id' => $service->id, 'name' => $service->name] : null;
                })
                ->filter()
                ->values()
                ->all();

            $productList = collect($place->product_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->map(function (int $id) use ($productsById) {
                    $product = $productsById->get($id);
                    return $product ? ['id' => $product->id, 'name' => $product->name] : null;
                })
                ->filter()
                ->values()
                ->all();

            return [
                'id' => $place->id,
                'name' => $place->name,
                'address' => $place->address,
                'isActive' => (bool) $place->is_active,
                'services' => $serviceList,
                'products' => $productList,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

