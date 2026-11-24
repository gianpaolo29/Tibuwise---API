<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $device_id
 * @property int $amount
 * @property string|null $note
 * @property bool $keep_goal_progress
 * @property Carbon $withdrawn_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'device_id',
    'amount',
    'note',
    'keep_goal_progress',
    'withdrawn_at',
])]
class Withdrawal extends Model
{
    protected function casts(): array
    {
        return [
            'keep_goal_progress' => 'boolean',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
