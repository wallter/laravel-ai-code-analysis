<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface AiModelServiceInterface
{
    /**
     * Retrieve all AI models.
     *
     * @return Collection
     */
    public function getAllModels(): Collection;

    /**
     * Create a new AI model.
     *
     * @param array $data
     * @return mixed
     */
    public function createModel(array $data);

    /**
     * Retrieve a specific AI model by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getModelById(int $id);

    /**
     * Update a specific AI model.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateModel(int $id, array $data);

    /**
     * Delete a specific AI model.
     *
     * @param int $id
     * @return void
     */
    public function deleteModel(int $id): void;
}
