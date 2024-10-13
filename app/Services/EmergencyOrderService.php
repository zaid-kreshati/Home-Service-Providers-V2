<?php

namespace App\Services;

use App\Models\Emergency;
use App\Models\Emergency_Provider;
use App\Models\Profile_Provider;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class EmergencyOrderService
{
    /**
     * @throws Exception
     */
    public function createEmergency($request)
    {
        $authUserCityId = Auth::user()->city_id;

        if (!Auth::user()->hasRole('client')) {
            throw new Exception('Not Authorized Client');
        }

        $providers = Profile_Provider::where('service_id', $request->service_id)
            ->whereHas('provider', function ($query) use ($authUserCityId) {
                $query->where('city_id', $authUserCityId);
            })
            ->with('provider')
            ->distinct('provider.id')
            ->get()
            ->pluck('provider');

        if ($providers->isEmpty()) {
            throw new Exception('There are No Providers Work In Your City');
        }

        $emergency = Emergency::create([
            'client_id' => auth()->id(),
            'service' => $request->service_id,
            'status' => 'pending',
            'description' => $request->description,
        ]);

        return [$emergency, $providers];
    }

    /**
     * @throws Exception
     */
    public function getProviderEmergencyRequest()
    {
        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            throw new Exception('Not Authorized ');
        }

        $cacheKey = 'emergency_requests:provider:'.$provider->id;
        $emergencyRequests = Cache::remember($cacheKey , 1000 , function () use ($provider) {
            return Emergency_Provider::where('provider_id' , $provider->id)
                    ->with('emergency')
                    ->whereHas('emergency', function ($query) {
                        $query->where('status', 'pending');
                    })
                    ->get();
        });

        if($emergencyRequests->isEmpty()) {
            abort(404, 'You do not have emergency requests');
            //throw new Exception('You Do not Have Emergency Requests');
        }

        return $emergencyRequests;

    }

}
