<?php

namespace App\Jobs;

use App\Models\Emergency_Provider;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyProvidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $providers;
    protected $emergency;

    public function __construct($providers, $emergency)
    {
        $this->providers = $providers;
        $this->emergency = $emergency;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $firebaseService = app(FirebaseService::class);
        $title = 'New Emergency Request';
        $body = 'You have a new Emergency Request';
        $deviceTokens = $this->providers->pluck('device_token')->filter()->toArray();

        try {
            foreach ($this->providers as $provider) {
                Emergency_Provider::firstOrCreate([
                    'provider_id' => $provider->id,
                    'emergency_id' => $this->emergency->id,
                ]);

                Notification::create([
                    'user_id' => $provider->id,
                    'title' => $title,
                    'details' => $body,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Job failed: ' . $e->getMessage());
            throw $e; // Optionally rethrow to mark the job as failed
        }

        // Send notification (assuming you have a firebaseService)
        $firebaseService->sendNotification($deviceTokens, $title, $body);
    }

}
