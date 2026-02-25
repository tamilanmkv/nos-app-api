<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCatalogController extends Controller
{
    /**
     * Public products list for mobile app.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductCatalog::query()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $products = $query->get()->map(fn (ProductCatalog $product) => $this->formatProduct($product));

        return response()->json([
            'success' => true,
            'data' => $products->values()->all(),
        ]);
    }

    private function formatProduct(ProductCatalog $product): array
    {
        $variants = collect($product->variants ?? [])->map(function (array $variant, int $index) {
            return [
                'id' => (string) ($variant['id'] ?? ('variant-' . $index)),
                'name' => (string) ($variant['name'] ?? 'Default'),
                'price' => (float) ($variant['price'] ?? 0),
                'originalPrice' => isset($variant['originalPrice']) ? (float) $variant['originalPrice'] : null,
                'specifications' => $variant['specifications'] ?? [],
                'inStock' => (bool) ($variant['inStock'] ?? true),
                'stockCount' => (int) ($variant['stockCount'] ?? 0),
            ];
        })->values();

        $basePrice = $variants->pluck('price')->filter(fn ($v) => $v > 0)->min() ?? 0.0;
        $originalPrice = $variants->pluck('originalPrice')->filter(fn ($v) => $v !== null && $v > 0)->max();
        $discount = null;
        if ($originalPrice && $basePrice > 0 && $originalPrice > $basePrice) {
            $discount = (int) round((($originalPrice - $basePrice) / $originalPrice) * 100);
        }

        $firstVariantSpecs = (array) (($variants->first()['specifications'] ?? []));

        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'category' => $this->inferCategory($product->name, (string) $product->description),
            'description' => (string) ($product->description ?? ''),
            'basePrice' => (float) $basePrice,
            'originalPrice' => $originalPrice ? (float) $originalPrice : null,
            'discount' => $discount,
            'images' => $product->images ?? [],
            'specifications' => $firstVariantSpecs,
            'variants' => $variants->all(),
            // Keep required app fields stable until ratings/reviews are modeled in DB.
            'rating' => 4.5,
            'reviewCount' => 0,
            'warranty' => null,
            'model' => Str::upper(Str::slug($product->name, '-')),
        ];
    }

    private function inferCategory(string $name, string $description): string
    {
        $text = Str::lower($name . ' ' . $description);

        if (Str::contains($text, ['ro', 'purifier', 'water purifier'])) {
            return 'RO Purifier';
        }
        if (Str::contains($text, ['cctv', 'camera', 'surveillance'])) {
            return 'CCTV';
        }
        if (Str::contains($text, ['ac', 'air conditioner', 'inverter ac'])) {
            return 'AC';
        }
        if (Str::contains($text, ['solar', 'panel', 'inverter'])) {
            return 'Solar';
        }

        return 'General';
    }
}

