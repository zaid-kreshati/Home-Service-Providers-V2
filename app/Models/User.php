<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\reports;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,hasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'device_token',
        'city_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function appointments() : ?HasMany
    {

        if ($this->hasRole('client')) {
            return $this->hasMany(Appointment::class, 'client_id');
        } elseif ($this->hasRole('provider')) {
            return $this->hasMany(Appointment::class, 'provider_id');
        }

        return null;
    }


    public function clientAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

//    public function hasRole($role)
//    {
//        // Check if the user has the specified role using Spatie's method
//        return $this->roles()->where('name', $role)->exists();
//    }
    /**
     * Get the emergencies that the user created as a client.
     */
    public function clientEmergencies(): HasMany
    {
        return $this->hasMany(Emergency::class, 'client_id');
    }

    /**
     * Get the emergencies that the user accepted as a provider.
     */
    public function providerEmergencies(): HasMany
    {
        return $this->hasMany(Emergency::class, 'provider_id');
    }

    /**
     * @return HasOne
     * Get The provider profile
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile_Provider::class, 'provider_id');
    }


    /**
     * Get the ratings given by the user (client).
     */
    public function givenRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'client_id');
    }

    /**
     * Get the ratings received by the user (provider).
     */
    public function receivedRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'provider_id');
    }

    public function city() : BelongsTo
    {
        return $this->belongsTo(City::class , 'city_id');
    }

    public function wallet() : HasOne
    {
        return $this->hasOne(Wallet::class, 'provider_id');
    }

    public function advertisements() : HasMany
    {
        return $this->hasMany(Advertisement::class, 'provider_id');
    }

    public function subscription() : HasOne
    {
        return $this->hasOne(Subscription::class, 'provider_id');
    }
}















