<?php

use App\Http\Controllers\AiderController;
use App\Http\Controllers\AnalysisController;
use Illuminate\Support\Facades\Route;

// Redirect to the analysis index page by default
Route::get('/', fn () => redirect()->route('analysis.index'));

Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis.index');
Route::post('/aider/interact', [AiderController::class, 'interact'])->name('aider.interact');
Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.show');

// CRUD Routes for Analysis
Route::get('/analysis/create', [AnalysisController::class, 'create'])->name('analysis.create');
Route::post('/analysis', [AnalysisController::class, 'store'])->name('analysis.store');
Route::get('/analysis/{analysis}/edit', [AnalysisController::class, 'edit'])->name('analysis.edit');
Route::put('/analysis/{analysis}', [AnalysisController::class, 'update'])->name('analysis.update');
Route::delete('/analysis/{analysis}', [AnalysisController::class, 'destroy'])->name('analysis.destroy');
Route::post('/analysis/parse', [AnalysisController::class, 'parse'])->name('analysis.parse');
Route::post('/analysis/analyze', [AnalysisController::class, 'analyze'])->name('analysis.analyze');
