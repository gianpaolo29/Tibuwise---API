<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Carbon;

class GameService
{
    /**
     * Level thresholds and titles.
     */
    private const LEVELS = [
        ['xp' => 0,    'level' => 1, 'title' => 'Saver'],
        ['xp' => 100,  'level' => 2, 'title' => 'Collector'],
        ['xp' => 300,  'level' => 3, 'title' => 'Hoarder'],
        ['xp' => 600,  'level' => 4, 'title' => 'Treasurer'],
        ['xp' => 1000, 'level' => 5, 'title' => 'Tycoon'],
        ['xp' => 1500, 'level' => 6, 'title' => 'Legend'],
        ['xp' => 2500, 'level' => 7, 'title' => 'Mythic'],
    ];

    /**
     * Calculate total XP: 1 XP per ₱1 saved + 10 XP per streak day.
     */
    public function getXp(Device $device): int
    {
        $coinXp = (int) $device->coins()->sum('denomination');
        $streakXp = $this->getStreakDaysTotal($device) * 10;

        return $coinXp + $streakXp;
    }

    /**
     * Get current level info.
     */
    public function getLevel(Device $device): array
    {
        $xp = $this->getXp($device);
        $current = self::LEVELS[0];
        $next = self::LEVELS[1] ?? null;

        foreach (self::LEVELS as $i => $lvl) {
            if ($xp >= $lvl['xp']) {
                $current = $lvl;
                $next = self::LEVELS[$i + 1] ?? null;
            }
        }

        $xpInLevel = $xp - $current['xp'];
        $xpForNext = $next ? ($next['xp'] - $current['xp']) : 0;
        $progress = $next ? min(100, ($xpInLevel / max(1, $xpForNext)) * 100) : 100;

        return [
            'level' => $current['level'],
            'title' => $current['title'],
            'xp' => $xp,
            'xp_in_level' => $xpInLevel,
            'xp_for_next' => $xpForNext,
            'progress' => round($progress, 1),
            'next_title' => $next['title'] ?? null,
        ];
    }

    /**
     * Streak with at-risk detection and freeze support.
     */
    public function getStreakInfo(Device $device): array
    {
        $savings = app(SavingsService::class);
        $streak = $savings->getStreak($device);

        $today = Carbon::today();
        $hasToday = $device->coins()->whereDate('deposited_at', $today)->exists();

        // At risk: no deposit today and it's past noon
        $atRisk = ! $hasToday && Carbon::now()->hour >= 12;

        // Streak freeze: one per week
        $freezeUsedAt = $device->streak_freeze_used_at
            ? Carbon::parse($device->streak_freeze_used_at)
            : null;
        $freezeAvailable = ! $freezeUsedAt || $freezeUsedAt->diffInDays($today) >= 7;

        return [
            'current' => $streak,
            'has_today' => $hasToday,
            'at_risk' => $atRisk,
            'freeze_available' => $freezeAvailable,
        ];
    }

    /**
     * Use a streak freeze.
     */
    public function useStreakFreeze(Device $device): bool
    {
        $info = $this->getStreakInfo($device);
        if (! $info['freeze_available']) {
            return false;
        }

        $device->update(['streak_freeze_used_at' => Carbon::today()]);

        return true;
    }

    /**
     * Total unique days with deposits (for XP calculation).
     */
    private function getStreakDaysTotal(Device $device): int
    {
        return (int) $device->coins()
            ->selectRaw('COUNT(DISTINCT DATE(deposited_at)) as days')
            ->value('days');
    }
}
