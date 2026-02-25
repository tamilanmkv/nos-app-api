<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\ProductCatalogController;
use App\Http\Controllers\Api\SalesLeadController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ServiceCatalogController;
use App\Http\Controllers\Api\ServiceLeadController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\SalesLeadController as AdminSalesLeadController;
use App\Http\Controllers\Api\Admin\ServiceLeadController as AdminServiceLeadController;
use App\Http\Controllers\Api\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Api\Admin\PlaceController as AdminPlaceController;
use App\Http\Controllers\Api\Admin\ServiceCatalogController as AdminServiceCatalogController;
use App\Http\Controllers\Api\Admin\ProductCatalogController as AdminProductCatalogController;

/*
|--------------------------------------------------------------------------
| API Routes - NOS Android App
|--------------------------------------------------------------------------
|
| Endpoints for Namma Ooru Service (NOS) mobile app: auth, sales leads,
| and service bookings.
|
*/

Route::prefix('v1')->group(function () {
    // Auth (OTP via WhatsApp)
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    // App user profile (create/update by phone after OTP verification)
    Route::post('/profile', [ProfileController::class, 'store']);

    // Sales leads (product orders)
    Route::post('/sales-lead', [SalesLeadController::class, 'store']);

    // Service leads (service bookings)
    Route::post('/service-lead', [ServiceLeadController::class, 'store']);

    // Places for mobile app (active places by default)
    Route::get('/places', [PlaceController::class, 'index']);

    // Public catalog for mobile app
    Route::get('/products', [ProductCatalogController::class, 'index']);
    Route::get('/services', [ServiceCatalogController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Admin API - NOS Master Web
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        // Public: admin login
        Route::post('/login', [AdminAuthController::class, 'login']);

        // Serve product images via signed URL (no Bearer required; signature validates)
        Route::get('/products/image/{path}', [AdminProductCatalogController::class, 'serveImage'])
            ->where('path', '[a-zA-Z0-9._-]+')
            ->middleware('signed')
            ->name('admin.products.image');

        // Protected: require Bearer token
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout']);
            Route::get('/me', [AdminAuthController::class, 'me']);
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);
            Route::get('/sales-leads', [AdminSalesLeadController::class, 'index']);
            Route::post('/sales-leads', [AdminSalesLeadController::class, 'store']);
            Route::get('/sales-leads/{id}', [AdminSalesLeadController::class, 'show']);
            Route::patch('/sales-leads/{id}', [AdminSalesLeadController::class, 'update']);
            Route::get('/service-leads', [AdminServiceLeadController::class, 'index']);
            Route::post('/service-leads', [AdminServiceLeadController::class, 'store']);
            Route::get('/service-leads/{id}', [AdminServiceLeadController::class, 'show']);
            Route::patch('/service-leads/{id}', [AdminServiceLeadController::class, 'update']);
            Route::get('/staffs', [AdminStaffController::class, 'index']);
            Route::get('/staffs/{id}', [AdminStaffController::class, 'show']);
            Route::post('/staffs', [AdminStaffController::class, 'store']);
            Route::patch('/staffs/{id}', [AdminStaffController::class, 'update']);
            Route::delete('/staffs/{id}', [AdminStaffController::class, 'destroy']);
            Route::get('/places', [AdminPlaceController::class, 'index']);
            Route::get('/places/{id}', [AdminPlaceController::class, 'show']);
            Route::post('/places', [AdminPlaceController::class, 'store']);
            Route::patch('/places/{id}', [AdminPlaceController::class, 'update']);
            Route::delete('/places/{id}', [AdminPlaceController::class, 'destroy']);
            Route::get('/services', [AdminServiceCatalogController::class, 'index']);
            Route::get('/services/{id}', [AdminServiceCatalogController::class, 'show']);
            Route::post('/services', [AdminServiceCatalogController::class, 'store']);
            Route::patch('/services/{id}', [AdminServiceCatalogController::class, 'update']);
            Route::delete('/services/{id}', [AdminServiceCatalogController::class, 'destroy']);
            Route::post('/products/upload-image', [AdminProductCatalogController::class, 'uploadImage']);
            Route::get('/products', [AdminProductCatalogController::class, 'index']);
            Route::get('/products/{id}', [AdminProductCatalogController::class, 'show']);
            Route::post('/products', [AdminProductCatalogController::class, 'store']);
            Route::patch('/products/{id}', [AdminProductCatalogController::class, 'update']);
            Route::delete('/products/{id}', [AdminProductCatalogController::class, 'destroy']);
        });
    });
});
