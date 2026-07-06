<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    use HasUuids;

    protected $fillable = [
        'model',
        'fields',
        'status',
        'file_path',
        'error_message',
        'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
