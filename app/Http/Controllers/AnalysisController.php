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
        // Eager-load AI results if you want quick access to them
        $analyses = CodeAnalysis::with('aiResults')->orderBy('id', 'desc')->get();

        return view('analysis.index', compact('analyses'));
    }

    /**
     * Show details (including AI results) for a single CodeAnalysis record.
     *
     * @param  int  $id  The ID of the CodeAnalysis record.
     * @return \Illuminate\View\View The view displaying the analysis details.
     */
    public function show(int $id)
    {
        $analysis = CodeAnalysis::with('aiResults')->findOrFail($id);

        // Summation of all cost_estimate_usd
        $totalCost = $analysis->aiResults->sum(fn($result) => $result->metadata['cost_estimate_usd'] ?? 0);

        return view('analysis.show', compact('analysis', 'totalCost'));
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
}
