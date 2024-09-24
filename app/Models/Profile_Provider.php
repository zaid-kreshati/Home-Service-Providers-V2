<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Service;
use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Profile_Provider extends Model
{
    use HasFactory;


    protected $table = 'profiles_providers';


    protected $fillable = ['provider_id', 'service_id','image_id', 'years_experience' ,'phone', 'description'];

    /**
     * Get the provider associated with the profile.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the service associated with the profile.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the image associated with the profile.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }
}
