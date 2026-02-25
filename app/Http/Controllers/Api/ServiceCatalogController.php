<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCatalogController extends Controller
{
    /**
     * Public services list for mobile app.
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

        $services = $query->get()->map(fn (ServiceCatalog $service) => $this->formatService($service));

        return response()->json([
            'success' => true,
            'data' => $services->values()->all(),
        ]);
    }

    private function formatService(ServiceCatalog $service): array
    {
        $serviceTypes = collect($service->service_types ?? [])->map(function (array $type, int $index) {
            return [
                'id' => (string) ($type['id'] ?? ('service-type-' . $index)),
                'name' => (string) ($type['name'] ?? 'General'),
                'description' => (string) ($type['description'] ?? ''),
                'price' => (float) ($type['price'] ?? 0),
                'duration' => (string) ($type['duration'] ?? ''),
                'isCustom' => (float) ($type['price'] ?? 0) <= 0,
            ];
        })->values();

        $priceMin = $serviceTypes->pluck('price')->filter(fn ($price) => $price > 0)->min();
        $priceRange = $priceMin ? 'From ₹' . number_format($priceMin, 0, '.', ',') : 'Custom';

        $category = $this->inferCategory($service->name, (string) $service->description);
        $icon = $this->inferIcon($category, $service->name, (string) $service->description);

        return [
            'id' => (string) $service->id,
            'name' => $service->name,
            'category' => $category,
            'description' => (string) ($service->description ?? ''),
            'priceRange' => $priceRange,
            'icon' => $icon,
            'image' => null,
            'serviceTypes' => $serviceTypes->all(),
        ];
    }

    private function inferCategory(string $name, string $description): string
    {
        $text = Str::lower($name . ' ' . $description);

        if (Str::contains($text, ['ac', 'air conditioner'])) {
            return 'AC Service';
        }
        if (Str::contains($text, ['ro', 'water purifier'])) {
            return 'Water Purifier';
        }
        if (Str::contains($text, ['cctv', 'camera'])) {
            return 'CCTV';
        }
        if (Str::contains($text, ['electrical', 'electrician'])) {
            return 'Electrician';
        }
        if (Str::contains($text, ['plumb', 'pipe', 'tap'])) {
            return 'Plumbing';
        }
        if (Str::contains($text, ['clean', 'housekeeping', 'bathroom', 'tank'])) {
            return 'Cleaning';
        }
        if (Str::contains($text, ['event', 'wedding', 'party'])) {
            return 'Event Management';
        }

        return 'General';
    }

    private function inferIcon(string $category, string $name, string $description): string
    {
        $text = Str::lower($category . ' ' . $name . ' ' . $description);

        if (Str::contains($text, ['water', 'ro'])) {
            return 'water-drop';
        }
        if (Str::contains($text, ['ac'])) {
            return 'snowflake';
        }
        if (Str::contains($text, ['cctv', 'camera'])) {
            return 'camera';
        }
        if (Str::contains($text, ['electrical', 'electrician'])) {
            return 'lightning';
        }
        if (Str::contains($text, ['bathroom'])) {
            return 'bathroom';
        }
        if (Str::contains($text, ['tank'])) {
            return 'tank';
        }
        if (Str::contains($text, ['clean'])) {
            return 'broom';
        }
        if (Str::contains($text, ['plumb', 'pipe', 'tap'])) {
            return 'wrench';
        }
        if (Str::contains($text, ['event', 'wedding', 'party'])) {
            return 'confetti';
        }

        return 'water-drop';
    }
}

