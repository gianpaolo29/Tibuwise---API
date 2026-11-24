<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $mac_address
 * @property string $name
 * @property string $api_key
 * @property string|null $pairing_code
 * @property Carbon|null $pairing_code_expires_at
 * @property string|null $wifi_ssid
 * @property string|null $firmware_version
 * @property Carbon|null $last_heartbeat_at
 * @property bool $is_online
 * @property int|null $signal_strength
 * @property int $pending_uploads
 * @property array|null $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Device extends Model implements Authenticatable
{
    protected $guarded = ['id'];

    public function getAuthIdentifierName(): string { return 'id'; }
    public function getAuthIdentifier(): mixed { return $this->id; }
    public function getAuthPassword(): string { return ''; }
    public function getAuthPasswordName(): string { return ''; }
    public function getRememberToken(): ?string { return null; }
    public function setRememberToken($value): void {}
    public function getRememberTokenName(): string { return ''; }

    protected function casts(): array
    {
        return [
            'pairing_code_expires_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'is_online' => 'boolean',
            'config' => 'array',
        ];
    }

    public static function generateApiKey(): string
    {
        return Str::random(64);
    }

    public static function generatePairingCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function appToken(): HasOne
    {
        return $this->hasOne(AppToken::class);
    }

    public function isPaired(): bool
    {
        return $this->appToken()->exists();
    }

    public function coins(): HasMany
    {
        return $this->hasMany(Coin::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    public function activeGoal(): ?Goal
    {
        return $this->goals()->where('is_active', true)->first();
    }
}
