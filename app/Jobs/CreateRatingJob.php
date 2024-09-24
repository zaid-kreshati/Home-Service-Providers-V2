<?php

namespace App\Jobs;

use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateRatingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clientId;
    protected $providerId;
    protected $rating;
    protected $comment;
    /**
     * Create a new job instance.
     */
    public function __construct($clientId, $providerId, $rating, $comment)
    {
        $this->clientId = $clientId;
        $this->providerId = $providerId;
        $this->rating = $rating;
        $this->comment = $comment;
    }
    /**
     * Execute the job.
     */
    public function handle()
    {
        Rating::create([
            'client_id' => $this->clientId,
            'provider_id' => $this->providerId,
            'rating' => $this->rating,
            'comment' => $this->comment,
        ]);
    }
}
