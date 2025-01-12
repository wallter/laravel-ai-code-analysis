<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\StaticAnalysisToolServiceInterface;
use Illuminate\Http\Request;

class StaticAnalysisToolController extends Controller
{
    public function __construct(protected StaticAnalysisToolServiceInterface $staticAnalysisToolService)
    {
    }

    /**
     * Display a listing of the Static Analysis Tools.
     */
    public function index()
    {
        $tools = $this->staticAnalysisToolService->getAllTools();

        return view('admin.static_analysis_tools.index', compact('tools'));
    }

    /**
     * Show the form for creating a new Static Analysis Tool.
     */
    public function create()
    {
        return view('admin.static_analysis_tools.create');
    }

    /**
     * Store a newly created Static Analysis Tool in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'command' => 'required|string',
            'options' => 'nullable|array',
            'enabled' => 'required|boolean',
            // Add other necessary fields if applicable
        ]);

        $this->staticAnalysisToolService->createTool($validated);

        return redirect()->route('admin.static-analysis-tools.index')
            ->with('success', 'Static Analysis Tool created successfully.');
    }

    /**
     * Display the specified Static Analysis Tool.
     */
    public function show(string $id)
    {
        $tool = $this->staticAnalysisToolService->getToolById($id);

        return view('admin.static_analysis_tools.show', compact('tool'));
    }

    /**
     * Show the form for editing the specified Static Analysis Tool.
     */
    public function edit(string $id)
    {
        $tool = $this->staticAnalysisToolService->getToolById($id);

        return view('admin.static_analysis_tools.edit', compact('tool'));
    }

    /**
     * Update the specified Static Analysis Tool in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'command' => 'required|string',
            'options' => 'nullable|array',
            'enabled' => 'required|boolean',
            // Add other necessary fields if applicable
        ]);

        $this->staticAnalysisToolService->updateTool($id, $validated);

        return redirect()->route('admin.static-analysis-tools.index')
            ->with('success', 'Static Analysis Tool updated successfully.');
    }

    /**
     * Remove the specified Static Analysis Tool from storage.
     */
    public function destroy(string $id)
    {
        $this->staticAnalysisToolService->deleteTool($id);

        return redirect()->route('admin.static-analysis-tools.index')
            ->with('success', 'Static Analysis Tool deleted successfully.');
    }
}
