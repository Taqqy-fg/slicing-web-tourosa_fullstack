<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TestimonialController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/site', [DashboardController::class, 'site']);
Route::get('/testimonials', [TestimonialController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/orders', [DashboardController::class, 'store']);
    Route::put('/orders/{invoice_no}', [DashboardController::class, 'update'])->where('invoice_no', '.*');
    Route::delete('/orders/{invoice_no}', [DashboardController::class, 'destroy'])->where('invoice_no', '.*');
    Route::put('/settings', [DashboardController::class, 'updateSettings']);
    Route::put('/catalog', [DashboardController::class, 'updateCatalog']);

    // Testimonials CRUD
    Route::post('/testimonials', [TestimonialController::class, 'store']);
    Route::put('/testimonials/{id}', [TestimonialController::class, 'update']);
    Route::delete('/testimonials/{id}', [TestimonialController::class, 'destroy']);

    // Reports export routes
    Route::get('/reports/excel', [\App\Http\Controllers\Api\ReportController::class, 'exportExcel']);
    Route::get('/reports/pdf', [\App\Http\Controllers\Api\ReportController::class, 'exportPdf']);
});

