<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $device_id
 * @property string $name
 * @property int $target_amount
 * @property Carbon|null $deadline
 * @property Carbon|null $reached_at
 * @property bool $is_active
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'device_id',
    'name',
    'target_amount',
    'deadline',
    'is_active',
    'status',
])]
class Goal extends Model
{
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'reached_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function isReached(): bool
    {
        return $this->reached_at !== null;
    }
}
