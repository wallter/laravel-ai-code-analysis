<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\StaticAnalysisToolServiceInterface;
use App\Models\StaticAnalysisTool;
use Illuminate\Support\Collection;

class StaticAnalysisToolService implements StaticAnalysisToolServiceInterface
{
    public function __construct(protected StaticAnalysisTool $staticAnalysisTool) {}

    /**
     * Retrieve all Static Analysis Tools.
     */
    public function getAllTools(): Collection
    {
        return $this->staticAnalysisTool->all();
    }

    /**
     * Create a new Static Analysis Tool.
     *
     * @return StaticAnalysisTool
     */
    public function createTool(array $data)
    {
        return $this->staticAnalysisTool->create($data);
    }

    /**
     * Retrieve a specific Static Analysis Tool by ID.
     *
     * @return StaticAnalysisTool
     */
    public function getToolById(int $id)
    {
        return $this->staticAnalysisTool->findOrFail($id);
    }

    /**
     * Update a specific Static Analysis Tool.
     *
     * @return StaticAnalysisTool
     */
    public function updateTool(int $id, array $data)
    {
        $tool = $this->getToolById($id);
        $tool->update($data);

        return $tool;
    }

    /**
     * Delete a specific Static Analysis Tool.
     */
    public function deleteTool(int $id): void
    {
        $tool = $this->getToolById($id);
        $tool->delete();
    }
}
