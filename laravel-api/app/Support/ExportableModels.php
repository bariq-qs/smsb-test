<?php

namespace App\Support;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class ExportableModels
{
    private const EXAMPLE_SUPPLIER_NAME = 'Example Supplier Ltd';

    /**
     * Central registry of every entity exposed to the Excel export/import feature.
     * `fields` drives the dynamic column picker on export; `import` maps a recognized
     * column header back onto the model (with any necessary lookups) when a file is uploaded.
     */
    public static function config(string $model): array
    {
        return match ($model) {
            'suppliers' => [
                'class' => Supplier::class,
                'query' => fn () => Supplier::query(),
                'fields' => [
                    'name' => fn ($m) => $m->name,
                    'email' => fn ($m) => $m->email,
                    'phone' => fn ($m) => $m->phone,
                    'is_active' => fn ($m) => $m->is_active ? 'Yes' : 'No',
                    'created_at' => fn ($m) => optional($m->created_at)->toDateTimeString(),
                ],
                'match_key' => 'name',
                'import' => [
                    'name' => fn ($value) => ['name' => $value],
                    'email' => fn ($value) => ['email' => $value],
                    'phone' => fn ($value) => ['phone' => $value],
                    'is_active' => fn ($value) => ['is_active' => self::toBool($value)],
                ],
                'template_example' => [
                    'name' => self::EXAMPLE_SUPPLIER_NAME,
                    'email' => 'supplier@example.com',
                    'phone' => '+1 555-0100',
                    'is_active' => 'Yes',
                ],
            ],
            'products' => [
                'class' => Product::class,
                'query' => fn () => Product::query()->with('supplier:id,name'),
                'fields' => [
                    'name' => fn ($m) => $m->name,
                    'sku' => fn ($m) => $m->sku,
                    'price' => fn ($m) => $m->price,
                    'supplier' => fn ($m) => $m->supplier?->name,
                    'is_active' => fn ($m) => $m->is_active ? 'Yes' : 'No',
                    'created_at' => fn ($m) => optional($m->created_at)->toDateTimeString(),
                ],
                'match_key' => 'sku',
                'import' => [
                    'name' => fn ($value) => ['name' => $value],
                    'sku' => fn ($value) => ['sku' => $value],
                    'price' => fn ($value) => ['price' => (float) $value],
                    'supplier' => fn ($value) => ['supplier_id' => Supplier::where('name', $value)->value('id')],
                    'is_active' => fn ($value) => ['is_active' => self::toBool($value)],
                ],
                'template_example' => [
                    'name' => 'Example Product',
                    'sku' => 'SKU-0001',
                    'price' => '19.99',
                    'supplier' => self::EXAMPLE_SUPPLIER_NAME,
                    'is_active' => 'Yes',
                ],
            ],
            'purchase-orders' => [
                'class' => PurchaseOrder::class,
                'query' => fn () => PurchaseOrder::query()->with('supplier:id,name')->withCount('items')->withSum('items', 'subtotal'),
                'fields' => [
                    'po_number' => fn ($m) => $m->po_number,
                    'supplier' => fn ($m) => $m->supplier?->name,
                    'order_date' => fn ($m) => optional($m->order_date)->toDateString(),
                    'status' => fn ($m) => $m->status,
                    'is_urgent' => fn ($m) => $m->is_urgent ? 'Yes' : 'No',
                    'items_count' => fn ($m) => $m->items_count,
                    'total' => fn ($m) => $m->items_sum_subtotal,
                ],
                'match_key' => 'po_number',
                'import' => [
                    'po_number' => fn ($value) => ['po_number' => $value],
                    'supplier' => fn ($value) => ['supplier_id' => Supplier::where('name', $value)->value('id')],
                    'order_date' => fn ($value) => ['order_date' => $value],
                    'status' => fn ($value) => ['status' => $value ?: 'draft'],
                    'is_urgent' => fn ($value) => ['is_urgent' => self::toBool($value)],
                ],
                'template_example' => [
                    'po_number' => 'PO-0001',
                    'supplier' => self::EXAMPLE_SUPPLIER_NAME,
                    'order_date' => now()->toDateString(),
                    'status' => 'draft',
                    'is_urgent' => 'No',
                ],
            ],
            'users' => [
                'class' => User::class,
                'query' => fn () => User::query()->with('roles:id,name'),
                'fields' => [
                    'name' => fn ($m) => $m->name,
                    'email' => fn ($m) => $m->email,
                    'role' => fn ($m) => $m->roles->pluck('name')->join(', '),
                    'created_at' => fn ($m) => optional($m->created_at)->toDateTimeString(),
                ],
                'match_key' => 'email',
                'import' => [
                    'name' => fn ($value) => ['name' => $value],
                    'email' => fn ($value) => ['email' => $value],
                    'role' => fn ($value) => ['__role' => $value],
                ],
                'template_example' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane.doe@example.com',
                    'role' => 'Staff',
                ],
            ],
            'roles' => [
                'class' => Role::class,
                'query' => fn () => Role::query()->with('permissions:id,name'),
                'fields' => [
                    'name' => fn ($m) => $m->name,
                    'permissions' => fn ($m) => $m->permissions->pluck('name')->join(', '),
                    'created_at' => fn ($m) => optional($m->created_at)->toDateTimeString(),
                ],
                'match_key' => 'name',
                'import' => [
                    'name' => fn ($value) => ['name' => $value],
                    'permissions' => fn ($value) => ['__permissions' => $value],
                ],
                'template_example' => [
                    'name' => 'Inventory Manager',
                    'permissions' => 'view products, edit products',
                ],
            ],
            default => throw new InvalidArgumentException("Unsupported model [{$model}] for export/import."),
        };
    }

    public static function models(): array
    {
        return ['suppliers', 'products', 'purchase-orders', 'users', 'roles'];
    }

    public static function query(string $model): Builder
    {
        return (self::config($model)['query'])();
    }

    private static function toBool(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'yes', 'true', 'active'], true);
    }
}
