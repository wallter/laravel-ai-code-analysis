<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface AiModelServiceInterface
{
    public function getAllModels(): Collection;
    public function createModel(array $data);
    public function getModelById(int $id);
    public function updateModel(int $id, array $data);
    public function deleteModel(int $id);
}
