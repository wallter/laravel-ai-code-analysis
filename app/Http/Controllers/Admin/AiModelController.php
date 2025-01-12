<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\AiModelServiceInterface;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    public function __construct(protected AiModelServiceInterface $aiModelService)
    {
    }

    /**
     * Display a listing of the AI models.
     */
    public function index()
    {
        $aiModels = $this->aiModelService->getAllModels();

        return view('admin.ai_models.index', compact('aiModels'));
    }

    /**
     * Show the form for creating a new AI model.
     */
    public function create()
    {
        return view('admin.ai_models.create');
    }

    /**
     * Store a newly created AI model in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Add other necessary fields if applicable
        ]);

        $this->aiModelService->createModel($validated);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'AI Model created successfully.');
    }

    /**
     * Display the specified AI model.
     */
    public function show(string $id)
    {
        $aiModel = $this->aiModelService->getModelById($id);

        return view('admin.ai_models.show', compact('aiModel'));
    }

    /**
     * Show the form for editing the specified AI model.
     */
    public function edit(string $id)
    {
        $aiModel = $this->aiModelService->getModelById($id);

        return view('admin.ai_models.edit', compact('aiModel'));
    }

    /**
     * Update the specified AI model in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Add other necessary fields if applicable
        ]);

        $this->aiModelService->updateModel($id, $validated);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'AI Model updated successfully.');
    }

    /**
     * Remove the specified AI model from storage.
     */
    public function destroy(string $id)
    {
        $this->aiModelService->deleteModel($id);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'AI Model deleted successfully.');
    }
}
