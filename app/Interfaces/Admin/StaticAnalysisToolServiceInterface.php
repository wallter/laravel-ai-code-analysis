<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface StaticAnalysisToolServiceInterface
{
    /**
     * Retrieve all Static Analysis Tools.
     *
     * @return Collection
     */
    public function getAllTools(): Collection;

    /**
     * Create a new Static Analysis Tool.
     *
     * @param array $data
     * @return mixed
     */
    public function createTool(array $data);

    /**
     * Retrieve a specific Static Analysis Tool by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getToolById(int $id);

    /**
     * Update a specific Static Analysis Tool.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateTool(int $id, array $data);

    /**
     * Delete a specific Static Analysis Tool.
     *
     * @param int $id
     * @return void
     */
    public function deleteTool(int $id): void;
}
