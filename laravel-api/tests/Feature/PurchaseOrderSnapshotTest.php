<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseOrderSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_item_snapshot_is_unaffected_by_later_product_changes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrator');
        Sanctum::actingAs($user);

        $supplier = Supplier::create(['name' => 'Test Supplier', 'is_active' => true]);
        $product = Product::create([
            'supplier_id' => $supplier->id,
            'name' => 'Original Name',
            'sku' => 'SKU-1',
            'price' => 100,
            'is_active' => true,
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-TEST-1',
            'order_date' => now(),
            'status' => 'draft',
        ]);

        $this->postJson("/api/purchase-orders/{$purchaseOrder->id}/items", [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $item = $purchaseOrder->items()->first();
        $this->assertSame('Original Name', $item->product_name_snapshot);
        $this->assertSame('100.00', $item->unit_price_snapshot);

        // Master data changes after the order was placed.
        $product->update(['name' => 'Renamed Product', 'price' => 999]);

        $item->refresh();

        $this->assertSame('Original Name', $item->product_name_snapshot);
        $this->assertSame('100.00', $item->unit_price_snapshot);
        $this->assertSame('200.00', $item->subtotal);
    }
}
