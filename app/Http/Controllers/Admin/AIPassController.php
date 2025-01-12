<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\AIPassServiceInterface;
use Illuminate\Http\Request;

class AIPassController extends Controller
{
    protected AIPassServiceInterface $aiPassService;

    public function __construct(AIPassServiceInterface $aiPassService)
    {
        $this->aiPassService = $aiPassService;
    }

    /**
     * Display a listing of the AI passes.
     */
    public function index()
    {
        $aiPasses = $this->aiPassService->getAllPasses();
        return view('admin.ai_passes.index', compact('aiPasses'));
    }

    /**
     * Show the form for creating a new AI pass.
     */
    public function create()
    {
        return view('admin.ai_passes.create');
    }

    /**
     * Store a newly created AI pass in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Add other necessary fields if applicable
        ]);

        $this->aiPassService->createPass($validated);

        return redirect()->route('admin.ai-passes.index')
                         ->with('success', 'AI Pass created successfully.');
    }

    /**
     * Display the specified AI pass.
     */
    public function show(string $id)
    {
        $aiPass = $this->aiPassService->getPassById($id);
        return view('admin.ai_passes.show', compact('aiPass'));
    }

    /**
     * Show the form for editing the specified AI pass.
     */
    public function edit(string $id)
    {
        $aiPass = $this->aiPassService->getPassById($id);
        return view('admin.ai_passes.edit', compact('aiPass'));
    }

    /**
     * Update the specified AI pass in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Add other necessary fields if applicable
        ]);

        $this->aiPassService->updatePass($id, $validated);

        return redirect()->route('admin.ai-passes.index')
                         ->with('success', 'AI Pass updated successfully.');
    }

    /**
     * Remove the specified AI pass from storage.
     */
    public function destroy(string $id)
    {
        $this->aiPassService->deletePass($id);
        return redirect()->route('admin.ai-passes.index')
                         ->with('success', 'AI Pass deleted successfully.');
    }
}
