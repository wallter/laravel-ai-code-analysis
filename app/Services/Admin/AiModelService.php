<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\AiModelServiceInterface;
use App\Models\AIModel;
use Illuminate\Support\Collection;

class AiModelService implements AiModelServiceInterface
{
    public function getAllModels(): Collection
    {
        return AIModel::all();
    }

    public function createModel(array $data)
    {
        return AIModel::create($data);
    }

    public function getModelById(int $id)
    {
        return AIModel::findOrFail($id);
    }

    public function updateModel(int $id, array $data)
    {
        $model = $this->getModelById($id);
        $model->update($data);
        return $model;
    }

    public function deleteModel(int $id)
    {
        $model = $this->getModelById($id);
        $model->delete();
    }
}
