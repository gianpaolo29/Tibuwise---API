<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppDeviceController extends Controller
{
    /**
     * Get device info and status.
     */
    public function show(): JsonResponse
    {
        $device = auth('app')->user();

        return response()->json([
            'id' => $device->id,
            'name' => $device->name,
            'mac_address' => $device->mac_address,
            'is_online' => $device->is_online,
            'wifi_ssid' => $device->wifi_ssid,
            'firmware_version' => $device->firmware_version,
            'signal_strength' => $device->signal_strength,
            'pending_uploads' => $device->pending_uploads,
            'last_heartbeat_at' => $device->last_heartbeat_at?->toIso8601String(),
            'paired_at' => $device->appToken?->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Rename the device.
     */
    public function update(Request $request): JsonResponse
    {
        $device = auth('app')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:40'],
        ]);

        $name = strip_tags(trim($data['name']));
        if ($name === '') {
            return response()->json(['message' => 'Name cannot be empty.'], 422);
        }

        $device->update(['name' => $name]);

        return response()->json(['name' => $device->name]);
    }
}
