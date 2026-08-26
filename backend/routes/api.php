<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/site', [DashboardController::class, 'site']);
Route::get('/testimonials', [TestimonialController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Orders — permission: orders.view / orders.create / orders.update / orders.delete / orders.payment
    Route::post('/orders', [DashboardController::class, 'store'])
        ->middleware('permission:orders.create');
    Route::put('/orders/{invoice_no}', [DashboardController::class, 'update'])
        ->where('invoice_no', '.*')
        ->middleware('permission:orders.update');
    Route::delete('/orders/{invoice_no}', [DashboardController::class, 'destroy'])
        ->where('invoice_no', '.*')
        ->middleware('permission:orders.delete');
    Route::post('/orders/{invoice_no}/payments', [DashboardController::class, 'storePayment'])
        ->where('invoice_no', '.*')
        ->middleware('permission:orders.payment');

    // Settings — permission: settings.update
    Route::put('/settings', [DashboardController::class, 'updateSettings'])
        ->middleware('permission:settings.update');

    // Catalog — permission: catalog.update
    Route::put('/catalog', [DashboardController::class, 'updateCatalog'])
        ->middleware('permission:catalog.update');

    // Testimonials CRUD — permission: testimonials.create / testimonials.update / testimonials.delete
    Route::post('/testimonials', [TestimonialController::class, 'store'])
        ->middleware('permission:testimonials.create');
    Route::put('/testimonials/{id}', [TestimonialController::class, 'update'])
        ->middleware('permission:testimonials.update');
    Route::delete('/testimonials/{id}', [TestimonialController::class, 'destroy'])
        ->middleware('permission:testimonials.delete');

    // Admin CRUD — permission: admins.view / admins.create / admins.update / admins.delete
    Route::get('/admins', [AdminController::class, 'index'])
        ->middleware('permission:admins.view');
    Route::post('/admins', [AdminController::class, 'store'])
        ->middleware('permission:admins.create');
    Route::put('/admins/{id}', [AdminController::class, 'update'])
        ->middleware('permission:admins.update');
    Route::delete('/admins/{id}', [AdminController::class, 'destroy'])
        ->middleware('permission:admins.delete');

    // Roles CRUD — super admin only (or roles.view / roles.create / etc.)
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create');
    Route::get('/roles/{id}', [RoleController::class, 'show'])
        ->middleware('permission:roles.view');
    Route::put('/roles/{id}', [RoleController::class, 'update'])
        ->middleware('permission:roles.update');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete');

    // Permissions — read only
    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('permission:permissions.view');
    Route::get('/permissions/all', [PermissionController::class, 'all'])
        ->middleware('permission:permissions.view');

    // Reports export routes
    Route::get('/reports/excel', [\App\Http\Controllers\Api\ReportController::class, 'exportExcel'])
        ->middleware('permission:reports.export');
    Route::get('/reports/pdf', [\App\Http\Controllers\Api\ReportController::class, 'exportPdf'])
        ->middleware('permission:reports.export');
});
