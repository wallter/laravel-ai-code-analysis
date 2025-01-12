<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\AiConfigurationServiceInterface;
use App\Models\AIConfiguration;
use Illuminate\Support\Collection;

class AiConfigurationService implements AiConfigurationServiceInterface
{
    protected AIConfiguration $aiConfiguration;

    public function __construct(AIConfiguration $aiConfiguration)
    {
        $this->aiConfiguration = $aiConfiguration;
    }

    /**
     * Retrieve all AI configurations.
     *
     * @return Collection
     */
    public function getAllConfigurations(): Collection
    {
        return $this->aiConfiguration->all();
    }

    /**
     * Create a new AI configuration.
     *
     * @param array $data
     * @return AIConfiguration
     */
    public function createConfiguration(array $data)
    {
        return $this->aiConfiguration->create($data);
    }

    /**
     * Retrieve a specific AI configuration by ID.
     *
     * @param int $id
     * @return AIConfiguration
     */
    public function getConfigurationById(int $id)
    {
        return $this->aiConfiguration->findOrFail($id);
    }

    /**
     * Update a specific AI configuration.
     *
     * @param int $id
     * @param array $data
     * @return AIConfiguration
     */
    public function updateConfiguration(int $id, array $data)
    {
        $configuration = $this->getConfigurationById($id);
        $configuration->update($data);
        return $configuration;
    }

    /**
     * Delete a specific AI configuration.
     *
     * @param int $id
     * @return void
     */
    public function deleteConfiguration(int $id): void
    {
        $configuration = $this->getConfigurationById($id);
        $configuration->delete();
    }
}
