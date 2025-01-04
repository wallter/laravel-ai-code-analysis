<?php

use App\Http\Controllers\AnalysisController;
use Illuminate\Support\Facades\Route;

// Redirect to the analysis index page by default
Route::get('/', function () {
    return redirect()->route('analysis.index');
});

Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis.index');
Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.show');
Route::post('/analysis/parse', [AnalysisController::class, 'parse'])->name('analysis.parse');
Route::post('/analysis/analyze', [AnalysisController::class, 'analyze'])->name('analysis.analyze');
