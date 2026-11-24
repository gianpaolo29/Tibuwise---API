<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Carbon;

class BadgeService
{
    /**
     * Check and award badges after a coin deposit.
     * Returns newly awarded badge types.
     */
    public function checkAndAward(Device $device): array
    {
        $awarded = [];
        $existing = $device->badges()->pluck('badge_type')->toArray();

        $totalSaved = (int) $device->coins()->sum('denomination');
        $streak = app(SavingsService::class)->getStreak($device);
        $latestCoin = $device->coins()->orderByDesc('deposited_at')->first();

        // First coin
        if (! in_array('first_coin', $existing) && $device->coins()->exists()) {
            $awarded[] = $this->award($device, 'first_coin');
        }

        // ₱100 Club
        if (! in_array('saved_100', $existing) && $totalSaved >= 100) {
            $awarded[] = $this->award($device, 'saved_100');
        }

        // ₱500 Club
        if (! in_array('saved_500', $existing) && $totalSaved >= 500) {
            $awarded[] = $this->award($device, 'saved_500');
        }

        // ₱1,000 Club
        if (! in_array('saved_1000', $existing) && $totalSaved >= 1000) {
            $awarded[] = $this->award($device, 'saved_1000');
        }

        // 7-Day Streak
        if (! in_array('streak_7', $existing) && $streak >= 7) {
            $awarded[] = $this->award($device, 'streak_7');
        }

        // 30-Day Streak
        if (! in_array('streak_30', $existing) && $streak >= 30) {
            $awarded[] = $this->award($device, 'streak_30');
        }

        // Mission Complete (a goal was completed)
        $completedGoals = $device->goals()->where('status', 'completed')->count();
        if (! in_array('mission_complete', $existing) && $completedGoals > 0) {
            $awarded[] = $this->award($device, 'mission_complete');
        }

        // Big Coin (first ₱20)
        if (! in_array('big_coin', $existing) && $latestCoin && $latestCoin->denomination >= 20) {
            $awarded[] = $this->award($device, 'big_coin');
        }
        if (! in_array('big_coin', $existing) && $device->coins()->where('denomination', 20)->exists()) {
            $awarded[] = $this->award($device, 'big_coin');
        }

        // Early Bird (deposit before 8 AM)
        if (! in_array('early_bird', $existing) && $latestCoin) {
            $hour = Carbon::parse($latestCoin->deposited_at)->hour;
            if ($hour < 8) {
                $awarded[] = $this->award($device, 'early_bird');
            }
        }

        // Comeback (deposit after 7-day gap)
        if (! in_array('comeback', $existing) && $device->coins()->count() >= 2) {
            $coins = $device->coins()->orderBy('deposited_at')->get(['deposited_at']);
            for ($i = 1; $i < $coins->count(); $i++) {
                $gap = Carbon::parse($coins[$i - 1]->deposited_at)
                    ->diffInDays(Carbon::parse($coins[$i]->deposited_at));
                if ($gap >= 7) {
                    $awarded[] = $this->award($device, 'comeback');
                    break;
                }
            }
        }

        return array_unique($awarded);
    }

    private function award(Device $device, string $type): string
    {
        $device->badges()->firstOrCreate(
            ['badge_type' => $type],
            ['earned_at' => now()],
        );

        return $type;
    }
}
