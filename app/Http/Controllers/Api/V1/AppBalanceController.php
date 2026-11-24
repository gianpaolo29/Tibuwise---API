<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GameService;
use App\Services\SavingsService;
use Illuminate\Http\JsonResponse;

class AppBalanceController extends Controller
{
    public function __construct(
        protected SavingsService $savings,
        protected GameService $game,
    ) {}

    public function index(): JsonResponse
    {
        $device = auth('app')->user();

        $recentCoins = $this->savings->getRecentCoins($device)->map(fn ($coin) => [
            'id' => $coin->id,
            'denomination' => $coin->denomination,
            'deposited_at' => $coin->deposited_at->toIso8601String(),
        ]);

        return response()->json([
            'balance' => $this->savings->getBalance($device),
            'today_deposits' => $this->savings->getTodayDeposits($device),
            'today_coins' => $this->savings->getTodayCoinCount($device),
            'month_deposits' => $this->savings->getMonthDeposits($device),
            'streak' => $this->game->getStreakInfo($device),
            'level' => $this->game->getLevel($device),
            'recent_coins' => $recentCoins,
            'active_goal' => $this->savings->getActiveGoalProgress($device),
            'milestones_hit' => $this->savings->checkMilestones($device),
            'pending_uploads' => $device->pending_uploads,
            'is_online' => $device->is_online || ($device->last_heartbeat_at && $device->last_heartbeat_at->gt(now()->subMinutes(3))),
            'badges' => $device->badges()->orderByDesc('earned_at')->get()->map(fn ($b) => [
                'type' => $b->badge_type,
                'earned_at' => $b->earned_at->toIso8601String(),
            ]),
        ]);
    }
}
