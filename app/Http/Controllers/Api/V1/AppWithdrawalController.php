<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SavingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppWithdrawalController extends Controller
{
    public function __construct(protected SavingsService $savings) {}

    public function index(): JsonResponse
    {
        $device = auth('app')->user();

        $withdrawals = $device->withdrawals()
            ->orderByDesc('withdrawn_at')
            ->paginate(min(request()->integer('per_page', 20), 100));

        return response()->json($withdrawals);
    }

    public function store(Request $request): JsonResponse
    {
        $device = auth('app')->user();

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
            'keep_goal_progress' => ['boolean'],
        ]);

        $note = isset($data['note']) ? strip_tags(trim($data['note'])) : null;

        // Use a transaction to prevent race conditions
        return DB::transaction(function () use ($device, $data, $note) {
            // Lock the device row to prevent concurrent withdrawals
            $device = $device->lockForUpdate()->find($device->id);
            $balance = $this->savings->getBalance($device);

            if ($data['amount'] > $balance) {
                return response()->json([
                    'message' => "Amount exceeds balance. Current balance: ₱{$balance}.",
                ], 422);
            }

            $withdrawal = $device->withdrawals()->create([
                'amount' => $data['amount'],
                'note' => $note,
                'keep_goal_progress' => $data['keep_goal_progress'] ?? false,
                'withdrawn_at' => now(),
            ]);

            return response()->json([
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'note' => $withdrawal->note,
                'withdrawn_at' => $withdrawal->withdrawn_at->toIso8601String(),
                'new_balance' => $this->savings->getBalance($device),
            ], 201);
        });
    }
}
