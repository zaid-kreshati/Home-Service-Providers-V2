<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RotateAdvertisements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ads:rotate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate advertisements on home interface';

    /**
     * Execute the console command.
     */
  //  protected $signature = 'ads:rotate';
  //  protected $description = 'Rotate advertisements on home interface';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get current date
        $now = Carbon::now();

        // Deactivate expired ads
        Advertisement::where('end_date', '<', $now)->update(['is_active' => false]);

        // Activate new ads if less than 5 are active
        $activeAdsCount = Advertisement::where('is_active', true)->count();
        if ($activeAdsCount < 5) {
            $adsToActivate = Advertisement::where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->where('is_active', false)
                ->take(5 - $activeAdsCount)
                ->update(['is_active' => true]);
        }

        $this->info('Advertisements rotated successfully.');
    }
}
