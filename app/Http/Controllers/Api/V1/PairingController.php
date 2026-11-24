<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppToken;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PairingController extends Controller
{
    /**
     * Device publishes a pairing code.
     * Called by the device after it connects to Wi-Fi.
     */
    public function publishCode(): JsonResponse
    {
        $device = auth('device')->user();

        $code = Device::generatePairingCode();
        $device->update([
            'pairing_code' => $code,
            'pairing_code_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        return response()->json([
            'pairing_code' => $code,
            'expires_at' => $device->pairing_code_expires_at->toIso8601String(),
        ]);
    }

    /**
     * App claims a device using the 6-digit pairing code.
     * Returns an app token. One phone per jar.
     */
    public function claim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $device = Device::where('pairing_code', $data['code'])
            ->where('pairing_code_expires_at', '>', Carbon::now())
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Invalid or expired pairing code.',
            ], 422);
        }

        // One phone per jar
        if ($device->isPaired()) {
            return response()->json([
                'message' => 'This jar is already paired to a phone. Unpair first.',
            ], 422);
        }

        $token = AppToken::create([
            'device_id' => $device->id,
            'token' => AppToken::generateToken(),
            'name' => 'Phone',
        ]);

        // Clear the pairing code
        $device->update([
            'pairing_code' => null,
            'pairing_code_expires_at' => null,
        ]);

        return response()->json([
            'token' => $token->token,
            'device_name' => $device->name,
        ], 201);
    }
}
