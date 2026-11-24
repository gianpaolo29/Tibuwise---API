<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Goal;
use Illuminate\Support\Carbon;

class SavingsService
{
    /**
     * Net balance: all coins minus all withdrawals (whole jar lifetime).
     * Always uses deposited_at for coin timing.
     */
    public function getBalance(Device $device): int
    {
        $deposited = (int) $device->coins()->sum('denomination');
        $withdrawn = (int) $device->withdrawals()->sum('amount');

        return $deposited - $withdrawn;
    }

    /**
     * Sum of coins deposited today (by deposited_at).
     */
    public function getTodayDeposits(Device $device): int
    {
        return (int) $device->coins()
            ->whereDate('deposited_at', Carbon::today())
            ->sum('denomination');
    }

    /**
     * Count of coins deposited today.
     */
    public function getTodayCoinCount(Device $device): int
    {
        return (int) $device->coins()
            ->whereDate('deposited_at', Carbon::today())
            ->count();
    }

    /**
     * This month total.
     */
    public function getMonthDeposits(Device $device): int
    {
        return (int) $device->coins()
            ->whereYear('deposited_at', Carbon::now()->year)
            ->whereMonth('deposited_at', Carbon::now()->month)
            ->sum('denomination');
    }

    /**
     * Most recent coins for display.
     */
    public function getRecentCoins(Device $device, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $device->coins()
            ->orderByDesc('deposited_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Goal-scoped progress.
     * Only counts coins with deposited_at >= goal.created_at.
     * Subtracts withdrawals in that window UNLESS keep_goal_progress is true.
     */
    public function getGoalProgress(Device $device, Goal $goal): array
    {
        $goalStart = $goal->created_at;

        $coinsTotal = (int) $device->coins()
            ->where('deposited_at', '>=', $goalStart)
            ->sum('denomination');

        $withdrawalsTotal = (int) $device->withdrawals()
            ->where('withdrawn_at', '>=', $goalStart)
            ->where('keep_goal_progress', false)
            ->sum('amount');

        $current = max(0, $coinsTotal - $withdrawalsTotal);
        $progress = min(100, ($current / max(1, $goal->target_amount)) * 100);
        $remaining = max(0, $goal->target_amount - $current);

        return [
            'goal' => $goal,
            'current_amount' => $current,
            'progress_percent' => round($progress, 1),
            'remaining' => $remaining,
            'projected_finish_date' => $this->getProjectedFinishDate($device, $goal, $current),
        ];
    }

    /**
     * Active goal with progress info (convenience).
     */
    public function getActiveGoalProgress(Device $device): ?array
    {
        $goal = $device->activeGoal();

        if (! $goal) {
            return null;
        }

        $progress = $this->getGoalProgress($device, $goal);

        // Auto-complete: if progress >= 100% and not yet reached
        if ($progress['progress_percent'] >= 100 && ! $goal->reached_at) {
            $goal->update([
                'reached_at' => now(),
                'is_active' => false,
                'status' => 'completed',
            ]);
            $progress['goal'] = $goal->fresh();
        }

        return $progress;
    }

    /**
     * Projected finish date.
     * Uses average daily savings since the goal started, capped at 30 days.
     * Returns null when average is 0.
     */
    public function getProjectedFinishDate(Device $device, Goal $goal, ?int $currentAmount = null): ?string
    {
        if ($currentAmount === null) {
            $progress = $this->getGoalProgress($device, $goal);
            $currentAmount = $progress['current_amount'];
        }

        $remaining = $goal->target_amount - $currentAmount;
        if ($remaining <= 0) {
            return null;
        }

        $goalStart = $goal->created_at;
        $daysSinceStart = max(1, (int) Carbon::now()->diffInDays($goalStart));
        $windowDays = min($daysSinceStart, 30);

        $windowStart = Carbon::now()->subDays($windowDays);
        $recentTotal = (int) $device->coins()
            ->where('deposited_at', '>=', $windowStart)
            ->sum('denomination');

        $dailyAverage = $recentTotal / $windowDays;

        if ($dailyAverage <= 0) {
            return null;
        }

        $daysToGo = (int) ceil($remaining / $dailyAverage);

        return Carbon::now()->addDays($daysToGo)->toDateString();
    }

    /**
     * Current streak: consecutive days with at least one deposit (by deposited_at).
     */
    public function getStreak(Device $device): int
    {
        $today = Carbon::today();
        $streak = 0;

        $hasToday = $device->coins()->whereDate('deposited_at', $today)->exists();
        $checkDate = $hasToday ? $today->copy() : $today->copy()->subDay();

        while (true) {
            $hasDeposit = $device->coins()
                ->whereDate('deposited_at', $checkDate)
                ->exists();

            if (! $hasDeposit) {
                break;
            }

            $streak++;
            $checkDate = $checkDate->subDay();
        }

        return $streak;
    }

    /**
     * Check which milestones were just crossed for the active goal.
     * Returns array of newly hit thresholds: [25, 50, 75, 100].
     */
    public function checkMilestones(Device $device): array
    {
        $goal = $device->activeGoal();
        if (! $goal) {
            return [];
        }

        $progress = $this->getGoalProgress($device, $goal);
        $percent = $progress['progress_percent'];

        $thresholds = [25, 50, 75, 100];
        $hit = [];

        foreach ($thresholds as $t) {
            if ($percent >= $t) {
                $hit[] = $t;
            }
        }

        return $hit;
    }
}
