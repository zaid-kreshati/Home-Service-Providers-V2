<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdsController extends Controller
{

    // Get the First active Ads for providers
    public function index()
    {
        // Retrieve active advertisements with provider data
        $advertisements = Advertisement::where('is_active', true)
            ->with(['provider.city' , 'provider.profile'])
            ->orderBy('start_date', 'asc')
            ->get()
            ->unique('provider_id')
            ->take(5)
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'provider' => $ad->provider->name,
                    'start_date' => $ad->start_date,
                    'end_date'=> $ad->end_date,
                    'city'=> $ad->provider->city->city_name,

                    'service'=> $ad->provider->profile->service->name,
                    'phone'=> $ad->provider->profile->phone,
                    'years_Exp'=> $ad->provider->profile->years_experience,
                    'description'=>$ad->provider->profile->description,
                ];
            });


        return response()->json([
            'Message'=> 'First 5 Active Providers',
            'data'=> $advertisements
        ]);
    }
}
