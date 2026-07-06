<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderItemRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Validation\ValidationException;

class PurchaseOrderItemController extends Controller
{
    public function store(PurchaseOrderItemRequest $request, PurchaseOrder $purchaseOrder)
    {
        $product = Product::findOrFail($request->validated('product_id'));
        $quantity = $request->validated('quantity');

        $item = $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'unit_price_snapshot' => $product->price,
            'quantity' => $quantity,
            'subtotal' => $product->price * $quantity,
        ]);

        return response()->json($item->load('product:id,name,sku'), 201);
    }

    public function update(PurchaseOrderItemRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderItem $item)
    {
        $this->assertBelongsToOrder($purchaseOrder, $item);

        $quantity = $request->validated('quantity');

        $item->update([
            'quantity' => $quantity,
            'subtotal' => $item->unit_price_snapshot * $quantity,
        ]);

        return response()->json($item->load('product:id,name,sku'));
    }

    public function destroy(PurchaseOrder $purchaseOrder, PurchaseOrderItem $item)
    {
        $this->assertBelongsToOrder($purchaseOrder, $item);

        $item->delete();

        return response()->json(['message' => 'Item removed']);
    }

    public function audits(PurchaseOrder $purchaseOrder, PurchaseOrderItem $item)
    {
        $this->assertBelongsToOrder($purchaseOrder, $item);

        return response()->json(
            $item->audits()->with('user:id,name')->latest()->get()
        );
    }

    private function assertBelongsToOrder(PurchaseOrder $purchaseOrder, PurchaseOrderItem $item): void
    {
        if ($item->purchase_order_id !== $purchaseOrder->id) {
            throw ValidationException::withMessages([
                'item' => 'This item does not belong to the given purchase order.',
            ]);
        }
    }
}
