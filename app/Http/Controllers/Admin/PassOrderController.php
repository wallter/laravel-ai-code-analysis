<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\PassOrderServiceInterface;
use Illuminate\Http\Request;

class PassOrderController extends Controller
{
    public function __construct(protected PassOrderServiceInterface $passOrderService) {}

    /**
     * Display a listing of the Pass Orders.
     */
    public function index()
    {
        $passOrders = $this->passOrderService->getAllPassOrders();

        return view('admin.pass_orders.index', compact('passOrders'));
    }

    /**
     * Show the form for creating a new Pass Order.
     */
    public function create()
    {
        return view('admin.pass_orders.create');
    }

    /**
     * Store a newly created Pass Order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pass_name' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
            // Add other necessary fields if applicable
        ]);

        $this->passOrderService->createPassOrder($validated);

        return redirect()->route('admin.pass-orders.index')
            ->with('success', 'Pass Order created successfully.');
    }

    /**
     * Display the specified Pass Order.
     */
    public function show(string $id)
    {
        $passOrder = $this->passOrderService->getPassOrderById($id);

        return view('admin.pass_orders.show', compact('passOrder'));
    }

    /**
     * Show the form for editing the specified Pass Order.
     */
    public function edit(string $id)
    {
        $passOrder = $this->passOrderService->getPassOrderById($id);

        return view('admin.pass_orders.edit', compact('passOrder'));
    }

    /**
     * Update the specified Pass Order in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'pass_name' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
            // Add other necessary fields if applicable
        ]);

        $this->passOrderService->updatePassOrder($id, $validated);

        return redirect()->route('admin.pass-orders.index')
            ->with('success', 'Pass Order updated successfully.');
    }

    /**
     * Remove the specified Pass Order from storage.
     */
    public function destroy(string $id)
    {
        $this->passOrderService->deletePassOrder($id);

        return redirect()->route('admin.pass-orders.index')
            ->with('success', 'Pass Order deleted successfully.');
    }
}
