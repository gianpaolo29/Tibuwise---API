<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoinBatchRequest;
use App\Http\Requests\StoreCoinRequest;
use App\Services\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DeviceCoinController extends Controller
{
    public function __construct(protected BadgeService $badges) {}
    public function store(StoreCoinRequest $request): JsonResponse
    {
        $device = auth('device')->user();
        $data = $request->validated();

        // Idempotent: skip if client_id already exists
        if (isset($data['client_id'])) {
            $existing = $device->coins()->where('client_id', $data['client_id'])->first();
            if ($existing) {
                return response()->json([
                    'id' => $existing->id,
                    'denomination' => $existing->denomination,
                    'deposited_at' => $existing->deposited_at->toIso8601String(),
                    'duplicate' => true,
                ], 200);
            }
        }

        // Mark device as online and clear pending count on upload
        $device->update(['last_heartbeat_at' => now(), 'is_online' => true, 'pending_uploads' => 0]);

        $coin = $device->coins()->create([
            'denomination' => $data['denomination'],
            'deposited_at' => $data['deposited_at'],
            'received_at' => now(),
            'client_id' => $data['client_id'] ?? null,
        ]);

        $this->badges->checkAndAward($device);

        return response()->json([
            'id' => $coin->id,
            'denomination' => $coin->denomination,
            'deposited_at' => $coin->deposited_at->toIso8601String(),
        ], 201);
    }

    public function storeBatch(StoreCoinBatchRequest $request): JsonResponse
    {
        $device = auth('device')->user();
        $device->update(['last_heartbeat_at' => now(), 'is_online' => true]);
        $now = Carbon::now();
        $inserted = 0;
        $skipped = 0;

        foreach ($request->validated('coins') as $coinData) {
            $clientId = $coinData['client_id'] ?? null;

            // Skip duplicates
            if ($clientId && $device->coins()->where('client_id', $clientId)->exists()) {
                $skipped++;
                continue;
            }

            $device->coins()->create([
                'denomination' => $coinData['denomination'],
                'deposited_at' => $now->copy()->subSeconds($coinData['age_seconds']),
                'received_at' => $now,
                'client_id' => $clientId,
            ]);
            $inserted++;
        }

        if ($inserted > 0) {
            $this->badges->checkAndAward($device);
        }

        return response()->json([
            'inserted' => $inserted,
            'skipped' => $skipped,
        ], 201);
    }
}
