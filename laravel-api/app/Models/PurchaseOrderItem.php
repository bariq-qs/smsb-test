<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PurchaseOrderItem extends Model implements AuditableContract
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_name_snapshot',
        'unit_price_snapshot',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_snapshot' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
