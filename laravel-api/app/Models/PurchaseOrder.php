<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PurchaseOrder extends Model implements AuditableContract
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'supplier_id',
        'po_number',
        'order_date',
        'status',
        'is_urgent',
        'notes',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'is_urgent' => 'boolean',
            'notes' => 'array',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
