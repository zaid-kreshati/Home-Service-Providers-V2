<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and deactivate expired subscriptions';

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $now = Carbon::now();

        // Find subscriptions that have expired
        $expiredSubscriptions = Subscription::where('end_date', '<', $now)
            ->where('is_active', true)
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->is_active = false;
            $subscription->save();
        }

        $this->info('Expired subscriptions have been deactivated.');
    }
}
