<?php

namespace App\Http\Controllers;

use App\Models\CodeAnalysis;
use App\Services\AI\CodeAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller handling analysis-related actions.
 */
class AnalysisController extends Controller
{
    /**
     * Initialize the AnalysisController with necessary services.
     *
     * @param  CodeAnalysisService  $codeAnalysisService  The service handling code analysis.
     */
    public function __construct(protected CodeAnalysisService $codeAnalysisService)
    {
        // No auth or middleware needed, as requested.
    }

    /**
     * Display a listing of all CodeAnalysis records.
     *
     * @return \Illuminate\View\View The view displaying all analyses.
     */
    public function index()
    {
        // Eager-load AI results and static analyses for efficient querying
        $analyses = CodeAnalysis::with([
            'aiResults',
            'aiScores',
            'staticAnalyses',
        ])->orderBy('id', 'desc')->get();

        return view('analysis.index', compact('analyses'));
    }

    /**
     * Show details (including AI results and static analyses) for a single CodeAnalysis record.
     *
     * @param  int  $id  The ID of the CodeAnalysis record.
     * @return \Illuminate\View\View The view displaying the analysis details.
     */
    public function show(int $id)
    {
        // Eager-load both AI results and static analyses
        $analysis = CodeAnalysis::with([
            'aiResults',
            'staticAnalyses',
        ])->findOrFail($id);

        // Summation of all AI cost estimates
        $totalAICost = $analysis->aiResults->sum(fn($result) => $result->metadata['cost_estimate_usd'] ?? 0);

        // Summation of static analysis errors
        $totalStaticErrors = $analysis->staticAnalyses->sum(fn($staticAnalysis) => count($staticAnalysis->results['errors'] ?? []));

        return view('analysis.show', compact('analysis', 'totalAICost', 'totalStaticErrors'));
    }

    /**
     * Trigger the parse + queued AI passes for a single file or folder path.
     *
     * Example form submission:
     *  <form action="{{ route('analysis.analyze') }}" method="POST">
     *
     *    @csrf
     *    <input name="filePath" />
     *    <button type="submit">Analyze</button>
     *  </form>
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @return \Illuminate\Http\RedirectResponse Redirects back with status or errors.
     */
    public function analyze(Request $request)
    {
        $filePath = $request->input('filePath');

        // Basic validation
        if (! $filePath) {
            return back()->withErrors(['filePath' => 'Please provide a valid file/folder path']);
        }

        try {
            // 1) Create or update a CodeAnalysis record
            $analysis = $this->codeAnalysisService->analyzeFile($filePath, false);

            // 2) Queue the multi-pass AI analysis
            $this->codeAnalysisService->runAnalysis($analysis, false);

            return redirect()->route('analysis.index')
                ->with('status', "Queued analysis for: {$analysis->file_path}");
        } catch (\Throwable $throwable) {
            Log::error("AnalysisController: Failed to analyze [{$filePath}]", ['error' => $throwable->getMessage()]);

            return back()->withErrors(['analysis' => "Failed to analyze [{$filePath}]: {$throwable->getMessage()}"]);
        }
    }

    /**
     * Show the form for creating a new CodeAnalysis.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('analysis.create');
    }

    /**
     * Store a newly created CodeAnalysis in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'filePath' => 'required|string|max:255',
        ]);

        try {
            // Create and analyze the file
            $analysis = $this->codeAnalysisService->analyzeFile($request->filePath, false);
            $this->codeAnalysisService->runAnalysis($analysis, false);

            return redirect()->route('analysis.index')
                ->with('status', "Successfully queued analysis for: {$analysis->file_path}");
        } catch (\Throwable $throwable) {
            Log::error("AnalysisController: Failed to store analysis [{$request->filePath}]", ['error' => $throwable->getMessage()]);

            return back()->withErrors(['analysis' => "Failed to analyze [{$request->filePath}]: {$throwable->getMessage()}"]);
        }
    }

    /**
     * Show the form for editing the specified CodeAnalysis.
     *
     * @return \Illuminate\View\View
     */
    public function edit(CodeAnalysis $analysis)
    {
        return view('analysis.edit', compact('analysis'));
    }

    /**
     * Update the specified CodeAnalysis in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CodeAnalysis $analysis)
    {
        // Validate input
        $request->validate([
            'filePath' => 'required|string|max:255',
        ]);

        try {
            // Update file path
            $analysis->file_path = $request->filePath;
            $analysis->save();

            // Optionally, re-run analysis if needed
            $this->codeAnalysisService->analyzeFile($analysis->file_path, true);
            $this->codeAnalysisService->runAnalysis($analysis, false);

            return redirect()->route('analysis.index')
                ->with('status', "Successfully updated analysis for: {$analysis->file_path}");
        } catch (\Throwable $throwable) {
            Log::error("AnalysisController: Failed to update analysis [ID: {$analysis->id}]", ['error' => $throwable->getMessage()]);

            return back()->withErrors(['analysis' => "Failed to update [{$analysis->file_path}]: {$throwable->getMessage()}"]);
        }
    }

    /**
     * Remove the specified CodeAnalysis from storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CodeAnalysis $analysis)
    {
        try {
            $analysis->delete();

            return redirect()->route('analysis.index')
                ->with('status', "Successfully deleted analysis for: {$analysis->file_path}");
        } catch (\Throwable $throwable) {
            Log::error("AnalysisController: Failed to delete analysis [ID: {$analysis->id}]", ['error' => $throwable->getMessage()]);

            return back()->withErrors(['analysis' => "Failed to delete [{$analysis->file_path}]: {$throwable->getMessage()}"]);
        }
    }
}
