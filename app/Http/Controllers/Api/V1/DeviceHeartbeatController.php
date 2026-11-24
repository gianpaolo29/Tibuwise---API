<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SavingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceHeartbeatController extends Controller
{
    /**
     * Device sends a heartbeat.
     */
    public function store(Request $request): JsonResponse
    {
        $device = auth('device')->user();

        $data = $request->validate([
            'signal_strength' => ['nullable', 'integer', 'min:-100', 'max:0'],
            'firmware_version' => ['nullable', 'string', 'max:20', 'regex:/^[\w.\-]+$/'],
            'wifi_ssid' => ['nullable', 'string', 'max:32'],
        ]);

        $device->update([
            'last_heartbeat_at' => now(),
            'is_online' => true,
            'signal_strength' => $data['signal_strength'] ?? $device->signal_strength,
            'firmware_version' => $data['firmware_version'] ?? $device->firmware_version,
            'wifi_ssid' => $data['wifi_ssid'] ?? $device->wifi_ssid,
            // Don't save pending_uploads from heartbeat — it's stale.
            // The coin upload endpoint resets it to 0 when coins are received.
        ]);

        $savings = app(SavingsService::class);

        return response()->json([
            'status' => 'ok',
            'paired' => $device->isPaired(),
            'balance' => $savings->getBalance($device),
        ]);
    }
}
