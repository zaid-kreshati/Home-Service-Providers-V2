<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['provider_id', 'balance'];

    public function provider() : BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function transactions() : HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
