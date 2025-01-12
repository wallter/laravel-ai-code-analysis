<?php

use App\Http\Controllers\Admin\AiConfigurationController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\AIPassController;
use App\Http\Controllers\Admin\PassOrderController;
use App\Http\Controllers\Admin\StaticAnalysisToolController;
use App\Http\Controllers\AnalysisController;
use Illuminate\Support\Facades\Route;

// Redirect to the analysis index page by default
Route::get('/', fn () => redirect()->route('analysis.index'));

Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis.index');
Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.show');

// CRUD Routes for Analysis
Route::get('/analysis/create', [AnalysisController::class, 'create'])->name('analysis.create');
Route::post('/analysis', [AnalysisController::class, 'store'])->name('analysis.store');
Route::get('/analysis/{analysis}/edit', [AnalysisController::class, 'edit'])->name('analysis.edit');
Route::put('/analysis/{analysis}', [AnalysisController::class, 'update'])->name('analysis.update');
Route::delete('/analysis/{analysis}', [AnalysisController::class, 'destroy'])->name('analysis.destroy');
Route::post('/analysis/parse', [AnalysisController::class, 'parse'])->name('analysis.parse');
Route::post('/analysis/analyze', [AnalysisController::class, 'analyze'])->name('analysis.analyze');

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('ai-models', AiModelController::class);
    Route::resource('ai-configurations', AiConfigurationController::class);
    Route::resource('static-analysis-tools', StaticAnalysisToolController::class);
    Route::resource('pass-orders', PassOrderController::class);
    Route::resource('ai-passes', AIPassController::class);
});
