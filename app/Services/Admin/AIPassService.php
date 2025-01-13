<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\AIPassServiceInterface;
use App\Models\AIPass;
use Illuminate\Support\Collection;

class AIPassService implements AIPassServiceInterface
{
    public function __construct(protected AIPass $aiPass) {}

    /**
     * Retrieve all AI passes.
     */
    public function getAllPasses(): Collection
    {
        return $this->aiPass->all();
    }

    /**
     * Create a new AI pass.
     *
     * @return AIPass
     */
    public function createPass(array $data)
    {
        return $this->aiPass->create($data);
    }

    /**
     * Retrieve a specific AI pass by ID.
     *
     * @return AIPass
     */
    public function getPassById(int $id)
    {
        return $this->aiPass->findOrFail($id);
    }

    /**
     * Update a specific AI pass.
     *
     * @return AIPass
     */
    public function updatePass(int $id, array $data)
    {
        $pass = $this->getPassById($id);
        $pass->update($data);

        return $pass;
    }

    /**
     * Delete a specific AI pass.
     */
    public function deletePass(int $id): void
    {
        $pass = $this->getPassById($id);
        $pass->delete();
    }
}
