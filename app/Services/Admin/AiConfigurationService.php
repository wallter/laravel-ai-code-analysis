<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\AiConfigurationServiceInterface;
use App\Models\AIConfiguration;
use Illuminate\Support\Collection;

class AiConfigurationService implements AiConfigurationServiceInterface
{
    public function __construct(protected AIConfiguration $aiConfiguration)
    {
    }

    /**
     * Retrieve all AI configurations.
     */
    public function getAllConfigurations(): Collection
    {
        return $this->aiConfiguration->all();
    }

    /**
     * Create a new AI configuration.
     *
     * @return AIConfiguration
     */
    public function createConfiguration(array $data)
    {
        return $this->aiConfiguration->create($data);
    }

    /**
     * Retrieve a specific AI configuration by ID.
     *
     * @return AIConfiguration
     */
    public function getConfigurationById(int $id)
    {
        return $this->aiConfiguration->findOrFail($id);
    }

    /**
     * Update a specific AI configuration.
     *
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
     */
    public function deleteConfiguration(int $id): void
    {
        $configuration = $this->getConfigurationById($id);
        $configuration->delete();
    }
}
