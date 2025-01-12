<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\StaticAnalysisToolServiceInterface;
use App\Models\StaticAnalysisTool;
use Illuminate\Support\Collection;

class StaticAnalysisToolService implements StaticAnalysisToolServiceInterface
{
    protected StaticAnalysisTool $staticAnalysisTool;

    public function __construct(StaticAnalysisTool $staticAnalysisTool)
    {
        $this->staticAnalysisTool = $staticAnalysisTool;
    }

    /**
     * Retrieve all Static Analysis Tools.
     *
     * @return Collection
     */
    public function getAllTools(): Collection
    {
        return $this->staticAnalysisTool->all();
    }

    /**
     * Create a new Static Analysis Tool.
     *
     * @param array $data
     * @return StaticAnalysisTool
     */
    public function createTool(array $data)
    {
        return $this->staticAnalysisTool->create($data);
    }

    /**
     * Retrieve a specific Static Analysis Tool by ID.
     *
     * @param int $id
     * @return StaticAnalysisTool
     */
    public function getToolById(int $id)
    {
        return $this->staticAnalysisTool->findOrFail($id);
    }

    /**
     * Update a specific Static Analysis Tool.
     *
     * @param int $id
     * @param array $data
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
     *
     * @param int $id
     * @return void
     */
    public function deleteTool(int $id): void
    {
        $tool = $this->getToolById($id);
        $tool->delete();
    }
}
