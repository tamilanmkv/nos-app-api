<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Create or update app user profile by phone.
     * Called after OTP verification when user saves details in the app.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'dob' => ['nullable', 'date'],
        ]);

        $user = AppUser::updateOrCreate(
            ['phone' => $validated['phone']],
            [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'city' => $validated['city'] ?? null,
                'dob' => isset($validated['dob']) ? $validated['dob'] : null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile saved successfully',
            'data' => [
                'id' => (string) $user->id,
                'phone' => $user->phone,
                'name' => $user->name,
                'email' => $user->email,
                'city' => $user->city,
                'dob' => $user->dob?->format('Y-m-d'),
            ],
        ]);
    }
}
