<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class ProductCatalogController extends Controller
{
    private const PER_PAGE = 20;

    private const PRODUCT_IMAGE_DISK = 'product_images';

    private const SIGNED_URL_VALID_DAYS = 7;

    /**
     * Upload a product image. Stores in local disk (storage/app/product-images)
     * and returns a signed URL valid for 7 days (stored in DB; frontend uses as img src).
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $file = $request->file('image');

        // Surface PHP upload errors before validation (e.g. file too large for upload_max_filesize)
        if ($file && ! $file->isValid()) {
            $code = $file->getError();
            $message = match ($code) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large. Maximum size is 5MB. Server may need higher upload_max_filesize.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                default => 'The image failed to upload (error code ' . $code . ').',
            };
            return response()->json([
                'message' => $message,
                'errors' => ['image' => [$message]],
            ], 422);
        }

        $request->validate([
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'], // 5MB
        ]);

        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $dir = Storage::disk(self::PRODUCT_IMAGE_DISK)->path('');
        if (! is_dir($dir)) {
            Storage::disk(self::PRODUCT_IMAGE_DISK)->makeDirectory('');
        }
        $file->storeAs('', $name, self::PRODUCT_IMAGE_DISK);

        $url = URL::temporarySignedRoute(
            'admin.products.image',
            now()->addDays(self::SIGNED_URL_VALID_DAYS),
            ['path' => $name]
        );

        return response()->json([
            'success' => true,
            'data' => ['url' => $url],
        ], 201);
    }

    /**
     * Serve a product image from local storage (validated by signed URL).
     */
    public function serveImage(Request $request, string $path): Response
    {
        if (! Storage::disk(self::PRODUCT_IMAGE_DISK)->exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $fullPath = Storage::disk(self::PRODUCT_IMAGE_DISK)->path($path);
        $mime = Storage::disk(self::PRODUCT_IMAGE_DISK)->mimeType($path);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * List products with pagination and filters.
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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = min((int) $request->input('per_page', self::PER_PAGE), 100);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (ProductCatalog $p) => $this->formatProduct($p));

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

    public function show(int $id): JsonResponse
    {
        $product = ProductCatalog::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.originalPrice' => ['nullable', 'numeric', 'min:0'],
            'variants.*.specifications' => ['nullable', 'array'],
            'variants.*.inStock' => ['nullable', 'boolean'],
            'variants.*.stockCount' => ['nullable', 'integer', 'min:0'],
        ]);

        $variants = $this->normalizeVariants($validated['variants'] ?? []);

        $product = ProductCatalog::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'images' => $validated['images'] ?? [],
            'variants' => $variants,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $this->formatProduct($product),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = ProductCatalog::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'variants' => ['sometimes', 'required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'string'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.originalPrice' => ['nullable', 'numeric', 'min:0'],
            'variants.*.specifications' => ['nullable', 'array'],
            'variants.*.inStock' => ['nullable', 'boolean'],
            'variants.*.stockCount' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_key_exists('name', $validated)) {
            $product->name = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $product->description = $validated['description'];
        }
        if (array_key_exists('images', $validated)) {
            $product->images = $validated['images'];
        }
        if (array_key_exists('variants', $validated)) {
            $product->variants = $this->normalizeVariants($validated['variants']);
        }
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $this->formatProduct($product->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = ProductCatalog::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * @param array<int, array> $variants
     * @return array<int, array>
     */
    private function normalizeVariants(array $variants): array
    {
        $out = [];
        foreach ($variants as $i => $v) {
            $out[] = [
                'id' => $v['id'] ?? 'var-' . ($i + 1) . '-' . uniqid(),
                'name' => $v['name'],
                'price' => (float) $v['price'],
                'originalPrice' => isset($v['originalPrice']) ? (float) $v['originalPrice'] : null,
                'specifications' => $v['specifications'] ?? [],
                'inStock' => $v['inStock'] ?? true,
                'stockCount' => isset($v['stockCount']) ? (int) $v['stockCount'] : 0,
            ];
        }
        return $out;
    }

    private function formatProduct(ProductCatalog $product): array
    {
        $variants = $product->variants ?? [];
        foreach ($variants as &$v) {
            $v['price'] = (float) ($v['price'] ?? 0);
            $v['originalPrice'] = isset($v['originalPrice']) ? (float) $v['originalPrice'] : null;
            $v['stockCount'] = (int) ($v['stockCount'] ?? 0);
        }

        $images = array_map(
            fn ($image) => $this->toDisplayImageUrl((string) $image),
            $product->images ?? []
        );

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'images' => $images,
            'variants' => $variants,
            'createdAt' => $product->created_at?->toIso8601String(),
            'updatedAt' => $product->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Normalize stored image values to a fresh signed URL for rendering.
     * Supports both legacy signed URLs and raw file names.
     */
    private function toDisplayImageUrl(string $image): string
    {
        if ($image === '') {
            return $image;
        }

        $fileName = $image;
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $path = parse_url($image, PHP_URL_PATH);
            if (is_string($path)) {
                $prefix = '/api/v1/admin/products/image/';
                $pos = strpos($path, $prefix);
                if ($pos !== false) {
                    $fileName = substr($path, $pos + strlen($prefix));
                } else {
                    $fileName = basename($path);
                }
            }
        }

        if ($fileName === '') {
            return $image;
        }

        if (! Storage::disk(self::PRODUCT_IMAGE_DISK)->exists($fileName)) {
            return $image;
        }

        return URL::temporarySignedRoute(
            'admin.products.image',
            now()->addDays(self::SIGNED_URL_VALID_DAYS),
            ['path' => $fileName]
        );
    }
}
