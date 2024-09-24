<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function show()
    {
        $wallet = auth()->user()->wallet;

        if(is_null($wallet)) {
            return response()->json(['Message' => 'You Dont have wallet yet !']);
        }
        return response()->json([
            'Message'=> 'Your Balance !',
            'balance'=> $wallet->balance
        ]);
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $wallet = auth()->user()->wallet;
        $wallet->balance += $request->amount;
        $wallet->save();

        $data = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => $request->amount,
            'type' => 'deposit',
            'description' => ' money deposited',
        ]);


        return response()->json([
            'Transaction'=> 'Deposited Successfully',
            'amount'=> $data->amount,
            'type'=> $data->type,
            'description'=> $data->description
        ]);
    }

    public function payForSubscription($planId)
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $wallet = auth()->user()->wallet;

        if ($wallet->balance < $plan->price) {
            return response()->json(['Message'=> 'Your Balance Not Enough']);
        }

        $wallet->balance -= $plan->price;
        $wallet->save();

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => $plan->price,
            'type' => 'payment',
            'description' => 'Subscription payment for ' . $plan->name,
        ]);

        $data = Subscription::create([
            'provider_id' => auth()->id(),
            'subscription_plan_id' => $plan->id,
            'is_active'=> true,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonth(), // adjust based on plan duration
        ]);

        return response()->json([
            'Message'=> " Subscription Successfully Activated",
            'Plan'=> $plan->name,
            'start_date'=> $data->start_date->format('d-m-Y'),
            'end_date'=> $data->end_date->format('d-m-Y')
        ]);
    }

    public function payForAdvertisement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'Message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Parse the dates using Carbon
        $startDate = Carbon::parse($request['start_date']);
        $endDate = Carbon::parse($request['end_date']);

        // Ensure the end date is not before the start date
        if ($endDate->lt($startDate)) {
            return response()->json(['error' => 'End date must be after the start date'], 400);
        }

        // Calculate the number of days between the dates (inclusive)
        $days = $startDate->diffInDays($endDate) + 1;

        // Define the cost per day
        $costPerDay = 50;

        // Calculate the total cost
        $totalCost = $days * $costPerDay;

        // Example of accessing the user's wallet (assuming you have a Wallet model relationship set up)
        $wallet = auth()->user()->wallet;

        // Check if the user has enough balance in their wallet
        if ($wallet->balance < $totalCost) {
            return response()->json(['error' => 'Insufficient balance in wallet'], 400);
        }


        $wallet->balance -= $totalCost;
        $wallet->save();

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => $totalCost,
            'type' => 'payment',
            'description' => 'Advertisement payment for ' . auth()->user()->name,
        ]);

       $data = Advertisement::create([
            'provider_id' => auth()->id(),
            'is_active'=> true,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'Message' => 'Done! Your Ad will display in few days',
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
        ]);
    }
}
