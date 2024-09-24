<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Emergency_Provider extends Model
{
    use HasFactory;

    protected $table = 'emergency_providers';

    protected $fillable = ['provider_id', 'emergency_id'];

    /**
     * Get the provider that was notified about the emergency.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the emergency that the provider was notified about.
     */
    public function emergency(): BelongsTo
    {
        return $this->belongsTo(Emergency::class, 'emergency_id');
    }
}
