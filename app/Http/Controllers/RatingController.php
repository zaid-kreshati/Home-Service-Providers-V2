<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    // Get rates for Authenticated  provider
    public function getRates() {

        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['Message'=> "Not Authorized | Providers Only "]);
        }

        $rates = Rating::where('provider_id', $provider->id)
            ->with(['client.city'])
            ->get()
            ->map(function($rating) {
                return [
                    'rating' => $rating->rating,
                    'comment' => $rating->comment,
                    'name' => $rating->client->name,
                    'email' => $rating->client->email,
                    'city' => $rating->client->city->city_name,
                ];
            });

        return response()->json($rates);
    }
}
