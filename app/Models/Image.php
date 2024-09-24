<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use HasFactory;

    protected $table = 'images';


    protected $fillable = ['path'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile_Provider::class, 'image_id');
    }

    public function service() : BelongsTo {
        return $this->belongsTo(Service::class , 'image_id' );
    }
}
