<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchaseOrderDemoSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = collect(['Acme Trading Co.', 'Nusantara Supplies', 'Blue Ocean Distribution'])
            ->map(fn ($name) => Supplier::create([
                'name' => $name,
                'email' => Str::slug($name).'@example.com',
                'phone' => fake()->phoneNumber(),
                'is_active' => true,
                'metadata' => ['bank_account' => fake()->bankAccountNumber(), 'contact_person' => fake()->name()],
            ]));

        $products = $suppliers->flatMap(function (Supplier $supplier) {
            return collect(range(1, 4))->map(fn ($i) => Product::create([
                'supplier_id' => $supplier->id,
                'name' => fake()->words(3, true),
                'sku' => strtoupper(Str::random(8)),
                'price' => fake()->randomFloat(2, 10, 500),
                'is_active' => true,
                'attributes' => ['unit' => fake()->randomElement(['pcs', 'box', 'kg']), 'weight_kg' => fake()->randomFloat(1, 0.1, 20)],
            ]));
        });

        foreach (range(1, 5) as $i) {
            $supplier = $suppliers->random();
            $supplierProducts = $products->where('supplier_id', $supplier->id)->values();

            $po = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'po_number' => 'PO-'.now()->format('Ym').'-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(random_int(0, 30)),
                'status' => fake()->randomElement(['draft', 'submitted', 'approved', 'completed']),
                'is_urgent' => fake()->boolean(20),
                'notes' => ['remarks' => fake()->sentence()],
            ]);

            foreach ($supplierProducts->random(min(3, $supplierProducts->count())) as $product) {
                $quantity = random_int(1, 10);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'unit_price_snapshot' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ]);
            }
        }
    }
}
