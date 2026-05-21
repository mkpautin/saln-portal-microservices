<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PurgeInactiveUsers extends Command
{
    protected $signature = 'users:purge-inactive';

    protected $description = 'Delete users inactive for at least 5 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(5);

        $query = User::query()
            ->where(function ($query) use ($cutoff): void {
                $query->whereNotNull('last_active_at')
                    ->where('last_active_at', '<', $cutoff);
            })
            ->orWhere(function ($query) use ($cutoff): void {
                $query->whereNull('last_active_at')
                    ->where('created_at', '<', $cutoff);
            });

        $count = (clone $query)->count();

        $query->delete();

        $this->info("Purged {$count} inactive user account(s).");

        return self::SUCCESS;
    }
}
