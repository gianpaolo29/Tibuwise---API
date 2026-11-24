<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Services\SavingsService;
use Illuminate\Http\JsonResponse;

class AppGoalController extends Controller
{
    public function __construct(protected SavingsService $savings) {}

    public function index(): JsonResponse
    {
        $device = auth('app')->user();

        $active = $device->goals()
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        $completed = $device->goals()
            ->where('status', 'completed')
            ->orderByDesc('reached_at')
            ->get();

        $activeData = null;
        if ($active) {
            $activeData = $this->savings->getGoalProgress($device, $active);
        }

        return response()->json([
            'active' => $activeData,
            'completed' => $completed,
        ]);
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $device = auth('app')->user();

        if ($device->goals()->where('is_active', true)->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'You already have an active goal. Complete or cancel it before creating a new one.',
            ], 422);
        }

        $data = $request->validated();

        $goal = $device->goals()->create([
            'name' => $data['name'],
            'target_amount' => $data['target_amount'],
            'deadline' => $data['deadline'] ?? null,
            'is_active' => true,
            'status' => 'active',
        ]);

        return response()->json($goal, 201);
    }

    public function show(int $id): JsonResponse
    {
        $device = auth('app')->user();
        $goal = $device->goals()->findOrFail($id);

        return response()->json(
            $this->savings->getGoalProgress($device, $goal)
        );
    }

    public function update(UpdateGoalRequest $request, int $id): JsonResponse
    {
        $device = auth('app')->user();
        $goal = $device->goals()->findOrFail($id);
        $goal->update($request->validated());

        return response()->json($goal);
    }

    /**
     * Cancel a goal (not delete). Keeps it out of the completed list.
     * Only allowed when goal is active.
     */
    public function destroy(int $id): JsonResponse
    {
        $device = auth('app')->user();
        $goal = $device->goals()->findOrFail($id);

        if ($goal->status !== 'active') {
            return response()->json([
                'message' => 'Only active goals can be cancelled.',
            ], 422);
        }

        $goal->update([
            'is_active' => false,
            'status' => 'cancelled',
        ]);

        return response()->json(['message' => 'Goal cancelled.']);
    }

    /**
     * Mark a goal as reached (fallback — auto-complete handles most cases).
     */
    public function markReached(int $id): JsonResponse
    {
        $device = auth('app')->user();
        $goal = $device->goals()->findOrFail($id);

        $goal->update([
            'reached_at' => now(),
            'is_active' => false,
            'status' => 'completed',
        ]);

        return response()->json($goal);
    }
}
