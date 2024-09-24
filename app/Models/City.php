<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class City extends Model
{
    use HasFactory;


    protected $table = 'cities';  // Use plural form for table names to follow Laravel conventions

    protected $fillable = ['city_name', 'region_id'];

    /**
     * Get the region that the city belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the users for the city.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * If we need to retrieve only providers in specific city
     *
     * To optimize performance and avoid N+1 query issues, you can eager load related models:
     *
     * $providersInCity = User::role('Provider')->where('city_id', $cityId)->with('city', 'roles')->get();
     */

}
