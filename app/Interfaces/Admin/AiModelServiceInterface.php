<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface AiModelServiceInterface
{
    /**
     * Retrieve all AI models.
     */
    public function getAllModels(): Collection;

    /**
     * Create a new AI model.
     *
     * @return mixed
     */
    public function createModel(array $data);

    /**
     * Retrieve a specific AI model by ID.
     *
     * @return mixed
     */
    public function getModelById(int $id);

    /**
     * Update a specific AI model.
     *
     * @return mixed
     */
    public function updateModel(int $id, array $data);

    /**
     * Delete a specific AI model.
     */
    public function deleteModel(int $id): void;
}
