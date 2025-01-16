<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\AIPassServiceInterface;
use Illuminate\Http\Request;

class AIPassController extends Controller
{
    public function __construct(protected AIPassServiceInterface $aiPassService) {}

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
            'ai_configuration_id' => 'required|integer|exists:ai_configurations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'operation_identifier' => 'required|string|max:255',
            'model_id' => 'nullable|integer|exists:ai_models,id',
            'max_tokens' => 'nullable|integer|min:1',
            'temperature' => 'nullable|numeric|between:0,1',
            'type' => 'required|in:single,both',
            'supports_system_message' => 'nullable|boolean',
            'system_message' => 'nullable|string',
            'prompt_sections' => 'nullable|json',
        ]);

        // Handle the checkbox for supports_system_message, since unchecked checkboxes are not sent.
        $validated['supports_system_message'] = $request->has('supports_system_message');

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
