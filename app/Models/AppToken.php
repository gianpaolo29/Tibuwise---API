<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $device_id
 * @property string $token
 * @property string $name
 * @property string|null $expo_push_token
 * @property string|null $reminder_time
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'device_id',
    'token',
    'name',
    'expo_push_token',
    'reminder_time',
])]
class AppToken extends Model
{
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
