<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\AiModelServiceInterface;
use App\Models\AIModel;
use Illuminate\Support\Collection;

class AiModelService implements AiModelServiceInterface
{
    protected AIModel $aiModel;

    public function __construct(AIModel $aiModel)
    {
        $this->aiModel = $aiModel;
    }

    /**
     * Retrieve all AI models.
     *
     * @return Collection
     */
    public function getAllModels(): Collection
    {
        return $this->aiModel->all();
    }

    /**
     * Create a new AI model.
     *
     * @param array $data
     * @return AIModel
     */
    public function createModel(array $data)
    {
        return $this->aiModel->create($data);
    }

    /**
     * Retrieve a specific AI model by ID.
     *
     * @param int $id
     * @return AIModel
     */
    public function getModelById(int $id)
    {
        return $this->aiModel->findOrFail($id);
    }

    /**
     * Update a specific AI model.
     *
     * @param int $id
     * @param array $data
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
     *
     * @param int $id
     * @return void
     */
    public function deleteModel(int $id): void
    {
        $model = $this->getModelById($id);
        $model->delete();
    }
}
