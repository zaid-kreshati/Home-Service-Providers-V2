<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = ['provider_id', 'is_active' ,  'start_date', 'end_date'];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
