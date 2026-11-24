<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeviceRegisterController extends Controller
{
    /**
     * Device self-registers on first boot.
     * Returns api_key + pairing_code.
     * Idempotent: if MAC already exists, returns existing key.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mac_address' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'name' => ['nullable', 'string', 'max:40'],
        ]);

        $device = Device::where('mac_address', $data['mac_address'])->first();

        if ($device) {
            // Already registered — return existing key + new pairing code
            $code = Device::generatePairingCode();
            $device->update([
                'pairing_code' => $code,
                'pairing_code_expires_at' => Carbon::now()->addMinutes(10),
                'is_online' => true,
                'last_heartbeat_at' => now(),
            ]);

            return response()->json([
                'api_key' => $device->api_key,
                'pairing_code' => $code,
            ]);
        }

        // New device
        $code = Device::generatePairingCode();
        $device = Device::create([
            'mac_address' => $data['mac_address'],
            'name' => strip_tags(trim($data['name'] ?? 'My TibuWise')),
            'api_key' => Device::generateApiKey(),
            'pairing_code' => $code,
            'pairing_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_online' => true,
            'last_heartbeat_at' => now(),
        ]);

        return response()->json([
            'api_key' => $device->api_key,
            'pairing_code' => $code,
        ], 201);
    }
}
