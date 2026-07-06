<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    use HasUuids;

    protected $fillable = [
        'model',
        'file_path',
        'status',
        'created_count',
        'updated_count',
        'errors',
        'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
