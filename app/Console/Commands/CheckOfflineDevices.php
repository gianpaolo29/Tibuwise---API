<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOfflineDevices extends Command
{
    protected $signature = 'devices:check-offline';

    protected $description = 'Mark devices as offline if no heartbeat for 10 minutes';

    public function handle(): void
    {
        $cutoff = Carbon::now()->subMinutes(10);

        $count = Device::where('is_online', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('last_heartbeat_at', '<', $cutoff)
                  ->orWhereNull('last_heartbeat_at');
            })
            ->update(['is_online' => false]);

        $this->info("Marked {$count} device(s) as offline.");
    }
}
