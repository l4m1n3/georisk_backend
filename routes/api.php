<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommuneController;
use App\Http\Controllers\Api\DrainageStructureController;
use App\Http\Controllers\Api\NeighborhoodController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RiskZoneController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/communes', [CommuneController::class, 'index']);
    Route::get('/communes/{commune}/neighborhoods', [CommuneController::class, 'neighborhoods']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::post('/reports/{report}/validate', [ReportController::class, 'validateReport']);
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject']);

    Route::apiResource('risk-zones', RiskZoneController::class)->except(['store']);
    Route::post('/risk-zones/{riskZone}/validate', [RiskZoneController::class, 'validate']);
    Route::post('/risk-zones/{riskZone}/reject', [RiskZoneController::class, 'reject']);

    Route::apiResource('drainage-structures', DrainageStructureController::class)->except(['store']);
    Route::post('/drainage-structures/{drainageStructure}/validate', [DrainageStructureController::class, 'validate']);
    Route::post('/drainage-structures/{drainageStructure}/reject', [DrainageStructureController::class, 'reject']);
    Route::apiResource('reports', ReportController::class);
    Route::apiResource('risk-zones', RiskZoneController::class);
    Route::apiResource('drainage-structures', DrainageStructureController::class);

    Route::apiResource('communes', CommuneController::class)->only(['index', 'show']);
    Route::apiResource('neighborhoods', NeighborhoodController::class)->only(['index', 'show']);
});
