<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        // Display available subscription plans
        $plans = SubscriptionPlan::all()
            ->map(function ($plan){
                return [
                    'id'=> $plan->id,
                    'name'=> $plan->name,
                    'price'=> $plan->price,
                    'duration'=> $plan->duration
                ];
            });
        return response()->json($plans);
    }


    public function details()
    {
        // Display provider's subscription details
      //  $subscription = auth()->user()->subscription;

        $data = Subscription::where('provider_id' , auth()->id())
            ->with('subscriptionPlan')
            ->get()
            ->map(function ($dat)
             {
                return [
                    'start_date'=> $dat->start_date,
                    'end_date'=>$dat->end_date,
                    'subscription'=> $dat->subscriptionPlan->name,
                    'price'=>$dat->subscriptionPlan->price,
                    'duration'=>$dat->subscriptionPlan->duration
                ];
             });

        return response()->json($data);
    }


}
