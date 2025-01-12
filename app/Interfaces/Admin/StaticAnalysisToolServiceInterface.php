<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface StaticAnalysisToolServiceInterface
{
    /**
     * Retrieve all Static Analysis Tools.
     */
    public function getAllTools(): Collection;

    /**
     * Create a new Static Analysis Tool.
     *
     * @return mixed
     */
    public function createTool(array $data);

    /**
     * Retrieve a specific Static Analysis Tool by ID.
     *
     * @return mixed
     */
    public function getToolById(int $id);

    /**
     * Update a specific Static Analysis Tool.
     *
     * @return mixed
     */
    public function updateTool(int $id, array $data);

    /**
     * Delete a specific Static Analysis Tool.
     */
    public function deleteTool(int $id): void;
}
