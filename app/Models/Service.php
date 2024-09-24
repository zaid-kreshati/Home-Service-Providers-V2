<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = ['name' , 'description' , 'image_id'];

    /**
     * Get the profiles associated with the service.
     */
    public function profilesProviders(): HasMany
    {
        return $this->hasMany(Profile_Provider::class);
    }

    public function image() : BelongsTo {
        return $this->belongsTo(Image::class);
    }

}
