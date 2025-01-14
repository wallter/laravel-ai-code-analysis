<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\AiModelServiceInterface;
use App\Models\AIModel;
use Illuminate\Support\Collection;

class AiModelService implements AiModelServiceInterface
{
    public function __construct(protected AIModel $aiModel) {}

    /**
     * Retrieve all AI models.
     */
    public function getAllModels(): Collection
    {
        return $this->aiModel->all();
    }

    /**
     * Create a new AI model.
     *
     * @return AIModel
     */
    public function createModel(array $data)
    {
        return $this->aiModel->create($data);
    }

    /**
     * Retrieve a specific AI model by ID.
     *
     * @return AIModel
     */
    public function getModelById(int $id)
    {
        return $this->aiModel->findOrFail($id);
    }

    /**
     * Update a specific AI model.
     *
     * @return AIModel
     */
    public function updateModel(int $id, array $data)
    {
        $model = $this->getModelById($id);
        $model->update($data);

        return $model;
    }

    /**
     * Delete a specific AI model.
     */
    public function deleteModel(int $id): void
    {
        $model = $this->getModelById($id);
        $model->delete();
    }
}
