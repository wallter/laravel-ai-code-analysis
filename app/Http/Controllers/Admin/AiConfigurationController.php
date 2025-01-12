<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\AiConfigurationServiceInterface;
use Illuminate\Http\Request;

class AiConfigurationController extends Controller
{
    protected AiConfigurationServiceInterface $aiConfigurationService;

    public function __construct(AiConfigurationServiceInterface $aiConfigurationService)
    {
        $this->aiConfigurationService = $aiConfigurationService;
    }

    /**
     * Display a listing of the AI configurations.
     */
    public function index()
    {
        $aiConfigurations = $this->aiConfigurationService->getAllConfigurations();
        return view('admin.ai_configurations.index', compact('aiConfigurations'));
    }

    /**
     * Show the form for creating a new AI configuration.
     */
    public function create()
    {
        return view('admin.ai_configurations.create');
    }

    /**
     * Store a newly created AI configuration in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            // Add other necessary fields if applicable
        ]);

        $this->aiConfigurationService->createConfiguration($validated);

        return redirect()->route('admin.ai-configurations.index')
                         ->with('success', 'AI Configuration created successfully.');
    }

    /**
     * Display the specified AI configuration.
     */
    public function show(string $id)
    {
        $aiConfiguration = $this->aiConfigurationService->getConfigurationById($id);
        return view('admin.ai_configurations.show', compact('aiConfiguration'));
    }

    /**
     * Show the form for editing the specified AI configuration.
     */
    public function edit(string $id)
    {
        $aiConfiguration = $this->aiConfigurationService->getConfigurationById($id);
        return view('admin.ai_configurations.edit', compact('aiConfiguration'));
    }

    /**
     * Update the specified AI configuration in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            // Add other necessary fields if applicable
        ]);

        $this->aiConfigurationService->updateConfiguration($id, $validated);

        return redirect()->route('admin.ai-configurations.index')
                         ->with('success', 'AI Configuration updated successfully.');
    }

    /**
     * Remove the specified AI configuration from storage.
     */
    public function destroy(string $id)
    {
        $this->aiConfigurationService->deleteConfiguration($id);
        return redirect()->route('admin.ai-configurations.index')
                         ->with('success', 'AI Configuration deleted successfully.');
    }
}
