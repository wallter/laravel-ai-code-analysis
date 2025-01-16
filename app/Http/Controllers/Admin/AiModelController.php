<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\AiModelServiceInterface;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    public function __construct(protected AiModelServiceInterface $aiModelService)
    {
        // $this->middleware(['auth', 'can:manage-ai-models']);
    }

    /**
     * Show the confirmation page for deleting the specified AI model.
     */
    public function confirmDelete(string $id)
    {
        $aiModel = $this->aiModelService->getModelById($id);

        return view('admin.ai_models.confirm_delete', compact('aiModel'));
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
            'max_tokens' => 'nullable|integer|min:1',
            'temperature' => 'nullable|numeric|between:0,1',
            'supports_system_message' => 'required|boolean',
            'token_limit_parameter' => 'nullable|string|max:255',
            // Add other necessary fields if applicable
        ]);

        // Create the AI model using the service
        try {
            $this->aiModelService->createModel($validated);

            return redirect()->route('admin.ai-models.index')
                ->with('success', 'AI Model created successfully.');
        } catch (\Exception $exception) {
            \Log::error('Error creating AI Model: '.$exception->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating the AI Model.');
        }
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
            'max_tokens' => 'nullable|integer|min:1',
            'temperature' => 'nullable|numeric|between:0,1',
            'supports_system_message' => 'required|boolean',
            'token_limit_parameter' => 'nullable|string|max:255',
            // Add other necessary fields if applicable
        ]);

        // Update the AI model using the service
        try {
            $this->aiModelService->updateModel($id, $validated);

            return redirect()->route('admin.ai-models.index')
                ->with('success', 'AI Model updated successfully.');
        } catch (\Exception $exception) {
            \Log::error('Error updating AI Model: '.$exception->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the AI Model.');
        }
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
