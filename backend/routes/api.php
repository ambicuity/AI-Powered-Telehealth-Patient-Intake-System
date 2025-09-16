<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\IntakeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// API v1 routes
Route::prefix('v1')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        
        // Patient routes
        Route::prefix('patients')->group(function () {
            Route::get('me', [PatientController::class, 'show']);
            Route::patch('me', [PatientController::class, 'update']);
            Route::get('me/stats', [PatientController::class, 'stats']);
        });

        // Intake form routes
        Route::prefix('intake')->group(function () {
            Route::post('/', [IntakeController::class, 'store']);
            Route::get('/', [IntakeController::class, 'index']);
            Route::get('{id}', [IntakeController::class, 'show']);
        });

        // Provider routes
        Route::prefix('providers')->middleware('role:provider')->group(function () {
            Route::get('/', function () {
                return response()->json([
                    'data' => [],
                    'message' => 'Provider endpoints coming soon',
                ]);
            });
        });

        // Appointment routes
        Route::prefix('appointments')->group(function () {
            Route::get('/', function () {
                return response()->json([
                    'data' => [],
                    'message' => 'Appointment endpoints coming soon',
                ]);
            });
        });
    });
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found',
    ], 404);
});