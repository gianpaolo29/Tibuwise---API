<?php

namespace App\Auth;

use App\Models\AppToken;
use App\Models\Device;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class AppTokenGuard implements Guard
{
    protected ?AppToken $appToken = null;

    protected ?Device $device = null;

    protected bool $resolved = false;

    public function __construct(protected Request $request) {}

    public function check(): bool
    {
        return $this->resolve() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function id(): ?int
    {
        return $this->resolve()?->device_id;
    }

    /**
     * Returns the Device associated with the app token.
     */
    public function user(): ?Device
    {
        return $this->device();
    }

    public function device(): ?Device
    {
        $this->resolve();

        return $this->device;
    }

    public function appToken(): ?AppToken
    {
        $this->resolve();

        return $this->appToken;
    }

    protected function resolve(): ?AppToken
    {
        if ($this->resolved) {
            return $this->appToken;
        }

        $this->resolved = true;

        $token = $this->request->bearerToken();

        if (! $token || trim($token) === '' || strlen($token) > 128) {
            return null;
        }

        $this->appToken = AppToken::with('device')->where('token', $token)->first();

        if ($this->appToken) {
            $this->device = $this->appToken->device;
        }

        return $this->appToken;
    }

    public function validate(array $credentials = []): bool
    {
        $appToken = AppToken::where('token', $credentials['token'] ?? '')->first();

        return $appToken !== null;
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
