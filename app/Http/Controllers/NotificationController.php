<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Notification;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function getNotifications() : JsonResponse
    {
        $user_id = Auth::id();
        $notifications = DB::table('notification')
            ->where('user_id', $user_id)
            ->select('title', 'details', DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at"))
            ->get();
        return response()->json($notifications);
    }


    public function clientProfile() : JsonResponse
    {
        $client = Auth::user();

        $city = City::where('id' , $client->city_id)->first();

        if(!Auth::user()->hasRole('client')) {
            return response()->json(['Message'=> 'Not Authorized Client']);
        }

        $rates = Rating::where('client_id' , $client->id )->get()->map(function ($rate) {
            return [
                'Rating'=> $rate->rating,
                'Comment'=>$rate->comment,
                'date'=>$rate->created_at->format('Y-m-d')
            ];

        });

        $data = [
            'Name'=> $client->name,
            'Email'=> $client->email,
            'City'=> $city->city_name,
            'Rates'=> $rates
        ];

        return response()->json($data);
    }
}
