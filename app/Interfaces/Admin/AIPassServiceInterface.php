<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface AIPassServiceInterface
{
    /**
     * Retrieve all AI passes.
     */
    public function getAllPasses(): Collection;

    /**
     * Create a new AI pass.
     *
     * @return mixed
     */
    public function createPass(array $data);

    /**
     * Retrieve a specific AI pass by ID.
     *
     * @return mixed
     */
    public function getPassById(int $id);

    /**
     * Update a specific AI pass.
     *
     * @return mixed
     */
    public function updatePass(int $id, array $data);

    /**
     * Delete a specific AI pass.
     */
    public function deletePass(int $id): void;
}
