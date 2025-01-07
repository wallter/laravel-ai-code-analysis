<?php

use App\Http\Controllers\AiderController;
use App\Http\Controllers\AnalysisController;
use Illuminate\Support\Facades\Route;

// Redirect to the analysis index page by default
Route::get('/', fn () => redirect()->route('analysis.index'));

Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis.index');
Route::post('/aider/interact', [AiderController::class, 'interact'])->name('aider.interact');
Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.show');
Route::post('/analysis/parse', [AnalysisController::class, 'parse'])->name('analysis.parse');
Route::post('/analysis/analyze', [AnalysisController::class, 'analyze'])->name('analysis.analyze');
