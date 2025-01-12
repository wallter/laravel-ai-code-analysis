<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\PassOrderServiceInterface;
use App\Models\PassOrder;
use Illuminate\Support\Collection;

class PassOrderService implements PassOrderServiceInterface
{
    protected PassOrder $passOrder;

    public function __construct(PassOrder $passOrder)
    {
        $this->passOrder = $passOrder;
    }

    /**
     * Retrieve all pass orders.
     *
     * @return Collection
     */
    public function getAllPassOrders(): Collection
    {
        return $this->passOrder->all();
    }

    /**
     * Create a new pass order.
     *
     * @param array $data
     * @return PassOrder
     */
    public function createPassOrder(array $data)
    {
        return $this->passOrder->create($data);
    }

    /**
     * Retrieve a specific pass order by ID.
     *
     * @param int $id
     * @return PassOrder
     */
    public function getPassOrderById(int $id)
    {
        return $this->passOrder->findOrFail($id);
    }

    /**
     * Update a specific pass order.
     *
     * @param int $id
     * @param array $data
     * @return PassOrder
     */
    public function updatePassOrder(int $id, array $data)
    {
        $passOrder = $this->getPassOrderById($id);
        $passOrder->update($data);
        return $passOrder;
    }

    /**
     * Delete a specific pass order.
     *
     * @param int $id
     * @return void
     */
    public function deletePassOrder(int $id): void
    {
        $passOrder = $this->getPassOrderById($id);
        $passOrder->delete();
    }
}
