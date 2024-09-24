<?php

namespace App\Http\Controllers;

use App\Models\Profile_Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchProviders(Request $request) : JsonResponse
    {

        if ($request->has('provider_name') || $request->has('service_name')) {
            $query = Profile_Provider::with([
                'provider' => function ($query) {
                    $query->select('id', 'name', 'email', 'city_id')->with('city:id,city_name');
                },
                'service:id,name',
                'image:id,path'
            ]);

        }
        else {
            return response()->json(['Message'=>'TEST']);
        }



        if ($request->has('provider_name')) {
            $providerName = $request->input('provider_name');
            $query->whereHas('provider', function ($q) use ($providerName) {
                $q->where('name', 'like', '%' . $providerName . '%');
            });
        }

        if ($request->has('service_name')) {
            $serviceName = $request->input('service_name');
            $query->whereHas('service', function ($q) use ($serviceName) {
                $q->where('name', 'like', '%' . $serviceName . '%');
            });
        }

        $profileProviders = $query->get(['id', 'provider_id', 'image_id', 'service_id', 'years_experience', 'description', 'phone', 'created_at', 'updated_at']);

        // Transform the data to exclude unnecessary fields and flatten the structure
        $transformedProviders = $profileProviders->map(function ($profileProvider) {
            return [
                'id' => $profileProvider->id,
                'provider' => [
                    'name' => $profileProvider->provider->name,
                    'email' => $profileProvider->provider->email,
                    'city' => $profileProvider->provider->city->city_name,
                ],
                'image' => $profileProvider->image->path,
                'service' => $profileProvider->service->name,
                'years_experience' => $profileProvider->years_experience,
                'description' => $profileProvider->description,
                'phone' => $profileProvider->phone,
            ];
        });

        return response()->json($transformedProviders);
    }
}
