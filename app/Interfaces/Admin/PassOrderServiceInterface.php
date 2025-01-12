<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface PassOrderServiceInterface
{
    /**
     * Retrieve all pass orders.
     */
    public function getAllPassOrders(): Collection;

    /**
     * Create a new pass order.
     *
     * @return mixed
     */
    public function createPassOrder(array $data);

    /**
     * Retrieve a specific pass order by ID.
     *
     * @return mixed
     */
    public function getPassOrderById(int $id);

    /**
     * Update a specific pass order.
     *
     * @return mixed
     */
    public function updatePassOrder(int $id, array $data);

    /**
     * Delete a specific pass order.
     */
    public function deletePassOrder(int $id): void;
}
