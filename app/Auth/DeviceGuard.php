<?php

namespace App\Auth;

use App\Models\Device;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class DeviceGuard implements Guard
{
    protected ?Device $device = null;

    protected bool $resolved = false;

    public function __construct(protected Request $request) {}

    public function check(): bool
    {
        return $this->device() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function id(): ?int
    {
        return $this->device()?->id;
    }

    public function user(): ?Device
    {
        return $this->device();
    }

    public function device(): ?Device
    {
        if ($this->resolved) {
            return $this->device;
        }

        $this->resolved = true;

        $token = $this->request->bearerToken();

        if (! $token || trim($token) === '' || strlen($token) > 128) {
            return null;
        }

        $this->device = Device::where('api_key', $token)->first();

        return $this->device;
    }

    public function validate(array $credentials = []): bool
    {
        $device = Device::where('api_key', $credentials['api_key'] ?? '')->first();

        return $device !== null;
    }

    public function hasUser(): bool
    {
        return $this->device !== null;
    }

    public function setUser($user): static
    {
        $this->device = $user;
        $this->resolved = true;

        return $this;
    }
}
