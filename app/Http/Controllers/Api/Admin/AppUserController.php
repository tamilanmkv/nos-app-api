<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * List app users (customer login details) with optional search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AppUser::query()->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%");
            });
        }

        $perPage = min((int) $request->input('per_page', self::PER_PAGE), 100);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (AppUser $user) => [
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'email' => $user->email,
            'city' => $user->city,
            'dob' => $user->dob?->format('Y-m-d'),
            'createdAt' => $user->created_at?->toIso8601String(),
            'updatedAt' => $user->updated_at?->toIso8601String(),
        ]);

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
}
