<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';  // Use plural form for table names to follow Laravel conventions

    protected $fillable = ['region_name'];

    /**
     * Get the cities for the region.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

}
