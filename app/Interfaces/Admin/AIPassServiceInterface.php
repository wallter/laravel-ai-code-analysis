<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface AIPassServiceInterface
{
    /**
     * Retrieve all AI passes.
     *
     * @return Collection
     */
    public function getAllPasses(): Collection;

    /**
     * Create a new AI pass.
     *
     * @param array $data
     * @return mixed
     */
    public function createPass(array $data);

    /**
     * Retrieve a specific AI pass by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getPassById(int $id);

    /**
     * Update a specific AI pass.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updatePass(int $id, array $data);

    /**
     * Delete a specific AI pass.
     *
     * @param int $id
     * @return void
     */
    public function deletePass(int $id): void;
}
