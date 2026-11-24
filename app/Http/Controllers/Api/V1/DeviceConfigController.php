<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SavingsService;
use Illuminate\Http\JsonResponse;

class DeviceConfigController extends Controller
{
    public function __construct(protected SavingsService $savings) {}

    public function show(): JsonResponse
    {
        $device = auth('device')->user();

        return response()->json([
            'balance' => $this->savings->getBalance($device),
            'paired' => $device->isPaired(),
            'config' => $device->config ?? [
                'coin_pulse_map' => [
                    1 => 1,
                    5 => 2,
                    10 => 3,
                    20 => 4,
                ],
                'heartbeat_interval' => 60,
                'upload_interval' => 5,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
