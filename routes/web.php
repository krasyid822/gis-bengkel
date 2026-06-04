<?php

use App\Http\Controllers\GisController;
use App\Http\Controllers\SupabaseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. Halaman PERTAMA yang muncul saat buka Laravel (Menampilkan Index.vue)
Route::get('/', function () {
    return Inertia::render('Index'); // Mengarah ke Index.vue
})->name('home');

// 2. Halaman Dashboard
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard'); // Mengarah ke Dashboard.vue
})->name('dashboard');

// 3. Halaman Process
Route::get('/process', function () {
    return Inertia::render('Process'); // Mengarah ke Process.vue
})->name('process');

// 4. Halaman Result
Route::get('/result', function () {
    return Inertia::render('Result'); // Mengarah ke Result.vue
})->name('result');

// 5. Halaman CRUD Kelola Lokasi
Route::get('/crud', function () {
    return Inertia::render('Crud'); 
})->name('crud');


Route::prefix('api')->group(function () {
    Route::get('/locations', [GisController::class, 'getLocations']);
    Route::get('/competitors', [GisController::class, 'getCompetitors']);
    Route::get('/boundary', [SupabaseController::class, 'getBoundary']);
    Route::get('/roads', [SupabaseController::class, 'getRoads']);
    Route::post('/save-ranking', [SupabaseController::class, 'saveRanking']);
    Route::get('/ranking', [SupabaseController::class, 'getRanking']);
    Route::post('/locations', [SupabaseController::class, 'storeLocation']);
    Route::put('/locations/{id}', [SupabaseController::class, 'updateLocation']);
    Route::delete('/locations/{id}', [SupabaseController::class, 'deleteLocation']);
    Route::get('/aturan', [GisController::class, 'getAturan']);
    Route::get('/wilayah', [GisController::class, 'getwilayah']);
    Route::get('/recommendations', [GisController::class, 'getRecommendations']);
    Route::get('/jalan', [GisController::class, 'getJalan']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';