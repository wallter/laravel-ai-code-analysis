<?php

namespace App\Services\Admin;

use App\Interfaces\Admin\PassOrderServiceInterface;
use App\Models\PassOrder;
use Illuminate\Support\Collection;

class PassOrderService implements PassOrderServiceInterface
{
    public function __construct(protected PassOrder $passOrder)
    {
    }

    /**
     * Retrieve all pass orders.
     */
    public function getAllPassOrders(): Collection
    {
        return $this->passOrder->all();
    }

    /**
     * Create a new pass order.
     *
     * @return PassOrder
     */
    public function createPassOrder(array $data)
    {
        return $this->passOrder->create($data);
    }

    /**
     * Retrieve a specific pass order by ID.
     *
     * @return PassOrder
     */
    public function getPassOrderById(int $id)
    {
        return $this->passOrder->findOrFail($id);
    }

    /**
     * Update a specific pass order.
     *
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
     */
    public function deletePassOrder(int $id): void
    {
        $passOrder = $this->getPassOrderById($id);
        $passOrder->delete();
    }
}
