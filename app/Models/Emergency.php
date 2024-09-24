<?php

namespace App\Models;

use App\Models\Emergency_Provider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Emergency extends Model
{
    use HasFactory;

    protected $table = 'emergencies';


    protected $fillable = ['client_id', 'provider_id','service' ,'status' ,'description'];


    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the provider that owns the emergency.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the emergency providers that were notified.
     */
    public function emergencyProviders(): HasMany
    {
        return $this->hasMany(Emergency_Provider::class, 'emergency_id');
    }

}

/*
 *
 * public function createEmergency(Request $request)
    {
        $emergency = Emergency::create([
            'client_id' => auth()->id(),
            'service' => $request->service,
            'status' => 'pending',
            'description' => $request->description,
        ]);

        $providers = User::role('Provider')->where('service', $request->service)->get();

        foreach ($providers as $provider) {
            EmergencyProvider::create([
                'provider_id' => $provider->id,
                'emergency_id' => $emergency->id,
            ]);

            // Send notification to the provider
            Notification::send($provider, new EmergencyNotification($emergency));
        }

        return response()->json(['message' => 'Emergency created and providers notified.']);
    }

    public function acceptEmergency(Request $request, $emergencyId)
    {
        $emergency = Emergency::findOrFail($emergencyId);

        if ($emergency->status === 'pending') {
            $emergency->update([
                'provider_id' => auth()->id(),
                'status' => 'approved',
            ]);

            return response()->json(['message' => 'Emergency accepted.']);
        }

        return response()->json(['message' => 'Emergency already accepted by another provider.'], 400);
    }
 */
