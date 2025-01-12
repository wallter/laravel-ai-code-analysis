<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\AiModelServiceInterface;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    protected AiModelServiceInterface $aiModelService;

    public function __construct(AiModelServiceInterface $aiModelService)
    {
        $this->aiModelService = $aiModelService;
    }

    public function index()
    {
        $aiModels = $this->aiModelService->getAllModels();
        return view('admin.ai_models.index', compact('aiModels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->aiModelService->createModel($validated);

        return redirect()->route('admin.ai-models.index')->with('success', 'AI Model created successfully.');
    }

    public function show(string $id)
    {
        $aiModel = $this->aiModelService->getModelById($id);
        return view('admin.ai_models.show', compact('aiModel'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->aiModelService->updateModel($id, $validated);

        return redirect()->route('admin.ai-models.index')->with('success', 'AI Model updated successfully.');
    }

    public function destroy(string $id)
    {
        $this->aiModelService->deleteModel($id);
        return redirect()->route('admin.ai-models.index')->with('success', 'AI Model deleted successfully.');
    }
}
