<?php

namespace App\Services\AI;

/**
 * Interface AiderServiceInterface
 *
 * Defines the contract for interacting with the Aider API.
 */
interface AiderServiceInterface
{
    /**
     * Send data to the Aider API and receive a response.
     *
     * @param array $data The data to send to Aider.
     * @return array The response from Aider.
     * @throws \Exception If the API request fails.
     */
    public function interact(array $data): array;

    /**
     * Perform an upgrade operation using the Aider API.
     *
     * @param array $data The data required for the upgrade.
     * @return array The response from Aider.
     * @throws \Exception If the API request fails.
     */
    public function upgrade(array $data): array;
}
