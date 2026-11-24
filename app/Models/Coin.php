<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $device_id
 * @property int $denomination
 * @property Carbon $deposited_at
 * @property Carbon $received_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'device_id',
    'denomination',
    'deposited_at',
    'received_at',
    'client_id',
])]
class Coin extends Model
{
    public const VALID_DENOMINATIONS = [1, 5, 10, 20];

    protected function casts(): array
    {
        return [
            'deposited_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
