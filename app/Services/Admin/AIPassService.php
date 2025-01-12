<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\AIPassServiceInterface;
use App\Models\AIPass;
use Illuminate\Support\Collection;

class AIPassService implements AIPassServiceInterface
{
    protected AIPass $aiPass;

    public function __construct(AIPass $aiPass)
    {
        $this->aiPass = $aiPass;
    }

    /**
     * Retrieve all AI passes.
     *
     * @return Collection
     */
    public function getAllPasses(): Collection
    {
        return $this->aiPass->all();
    }

    /**
     * Create a new AI pass.
     *
     * @param array $data
     * @return AIPass
     */
    public function createPass(array $data)
    {
        return $this->aiPass->create($data);
    }

    /**
     * Retrieve a specific AI pass by ID.
     *
     * @param int $id
     * @return AIPass
     */
    public function getPassById(int $id)
    {
        return $this->aiPass->findOrFail($id);
    }

    /**
     * Update a specific AI pass.
     *
     * @param int $id
     * @param array $data
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
     *
     * @param int $id
     * @return void
     */
    public function deletePass(int $id): void
    {
        $pass = $this->getPassById($id);
        $pass->delete();
    }
}
