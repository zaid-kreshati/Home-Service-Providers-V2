<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['provider_id', 'subscription_plan_id', 'is_active' ,  'start_date', 'end_date'];

    public function subscriptionPlan() : BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function provider() : BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
