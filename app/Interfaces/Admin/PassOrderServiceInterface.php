<?php

namespace App\Interfaces\Admin;

use Illuminate\Support\Collection;

interface PassOrderServiceInterface
{
    /**
     * Retrieve all pass orders.
     *
     * @return Collection
     */
    public function getAllPassOrders(): Collection;

    /**
     * Create a new pass order.
     *
     * @param array $data
     * @return mixed
     */
    public function createPassOrder(array $data);

    /**
     * Retrieve a specific pass order by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getPassOrderById(int $id);

    /**
     * Update a specific pass order.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updatePassOrder(int $id, array $data);

    /**
     * Delete a specific pass order.
     *
     * @param int $id
     * @return void
     */
    public function deletePassOrder(int $id): void;
}
