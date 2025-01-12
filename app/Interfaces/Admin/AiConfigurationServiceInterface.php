<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface AiConfigurationServiceInterface
{
    /**
     * Retrieve all AI configurations.
     *
     * @return Collection
     */
    public function getAllConfigurations(): Collection;

    /**
     * Create a new AI configuration.
     *
     * @param array $data
     * @return mixed
     */
    public function createConfiguration(array $data);

    /**
     * Retrieve a specific AI configuration by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getConfigurationById(int $id);

    /**
     * Update a specific AI configuration.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateConfiguration(int $id, array $data);

    /**
     * Delete a specific AI configuration.
     *
     * @param int $id
     * @return void
     */
    public function deleteConfiguration(int $id): void;
}
