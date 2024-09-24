<?php

namespace App\Models;

use App\Models\providers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';


    protected $fillable = ['client_id', 'provider_id', 'date', 'hours', 'status', 'description'];


    public function client() : BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the provider that owns the appointment.
     */
    public function provider() : BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

}
