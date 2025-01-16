<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface AiConfigurationServiceInterface
{
    /**
     * Retrieve all AI configurations.
     */
    public function getAllConfigurations(): Collection;

    /**
     * Create a new AI configuration.
     *
     * @return mixed
     */
    public function createConfiguration(array $data);

    /**
     * Retrieve a specific AI configuration by ID.
     *
     * @return mixed
     */
    public function getConfigurationById(int $id);

    /**
     * Update a specific AI configuration.
     *
     * @return mixed
     */
    public function updateConfiguration(int $id, array $data);

    /**
     * Delete a specific AI configuration.
     */
    public function deleteConfiguration(int $id): void;
}
