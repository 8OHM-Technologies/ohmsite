<?php

use App\Http\Controllers\Api\CaseController;
use App\Http\Middleware\VerifyDeveloperApiKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        Cache::driver()->get('health_check');

        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'cache' => 'connected',
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::post('/send-website-enquiry', [\App\Http\Controllers\Api\EnquiryController::class, 'store']);

Route::middleware(VerifyDeveloperApiKey::class)->prefix('v1')->group(function () {
    Route::get('/cases', [CaseController::class, 'index']);
    Route::get('/cases/{id}', [CaseController::class, 'show']);
});
