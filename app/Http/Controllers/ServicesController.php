<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Profile_Provider;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class ServicesController extends Controller
{

    //    Get Service providers By specific Service
    public function providersByService($serviceId) : JsonResponse
    {
        $profileProviders = Profile_Provider::with([
            'provider' => function ($query) {
                $query->select('id', 'name', 'email', 'city_id')->with('city:id,city_name')
                    ->with(['receivedRatings' => function ($query) {
                        $query->select('provider_id', DB::raw('AVG(rating) as average_rating'))
                            ->groupBy('provider_id');
                    }]);
            },
            'service:id,name',
            'image:id,path'
        ])
            ->where('service_id', $serviceId)
            ->get(['id', 'provider_id', 'image_id', 'service_id', 'years_experience', 'description', 'phone']);


        // Transform the data to exclude unnecessary fields and flatten the structure
        $transformedProviders = $profileProviders->map(function ($profileProvider) {
            return [
                'id' => $profileProvider->id,
                'provider' => [
                    'provider_id' => $profileProvider->provider->id,
                    'name' => $profileProvider->provider->name,
                    'email' => $profileProvider->provider->email,
                    'city' => $profileProvider->provider->city->city_name,
                    'rating'=> $profileProvider->provider->receivedRatings
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

    public function getServices() : JsonResponse
    {
        $services = Service::query()->with('image')->get()
            ->map(function ($service) {
                return [
                    'id'=> $service->id,
                    'name'=> $service->name,
                    'description'=> $service->description,
                    'image' => $service->image->path
                ];
            });
        return response()->json($services);
    }

    // Get provider in the same city of the client
    public function getProvidersByUserCityAndService($serviceId) : JsonResponse
    {

        $user = Auth::user();

        if (!$user->hasRole('client')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userCityId = $user->city_id;
//        dd($userCityId);

        $profileProviders = Profile_Provider::with([
            'provider' => function ($query) use ($userCityId) {
                $query->select('id', 'name', 'email', 'city_id')
                    ->where('city_id', $userCityId)
                    ->with('city:id,city_name')
                    ->with(['receivedRatings' => function ($query) {
                        $query->select('provider_id', DB::raw('AVG(rating) as average_rating'))
                            ->groupBy('provider_id');
                    }]);
            },
            'service:id,name',
            'image:id,path'
        ])
            ->where('service_id', $serviceId)
            ->get(['id', 'provider_id', 'image_id', 'service_id', 'years_experience', 'description', 'phone']);

//        return response()->json($profileProviders);


        // Filter out profile providers with null provider and transform the data
        $transformedProviders = $profileProviders->filter(function ($profileProvider) {
            return $profileProvider->provider !== null;
        })->map(function ($profileProvider) {
            return [
                'id' => $profileProvider->id,
                'provider' => [
                    'provider_id' => $profileProvider->provider->id,
                    'name' => $profileProvider->provider->name,
                    'email' => $profileProvider->provider->email,
                    'city' => $profileProvider->provider->city->city_name,
                    'rating'=> $profileProvider->provider->receivedRatings->first()->average_rating

                ],
                'image' => $profileProvider->image ? $profileProvider->image->path : null,
                'service' => $profileProvider->service ? $profileProvider->service->name : null,
                'years_experience' => $profileProvider->years_experience,
                'description' => $profileProvider->description,
                'phone' => $profileProvider->phone,
            ];
        });

        return response()->json($transformedProviders);
    }


    public function selectSpecificProvider($provider_id) {
        $profile_provider = Profile_Provider::where('provider_id' , $provider_id)
            ->with('provider')
            ->with('image')
            ->with('service')
            ->get()
            ->unique('id')
            ->map(function ($profile_provider) {
                return [
                    'profile_id' => $profile_provider->id,
                    'provider_id'=> $profile_provider->provider->id,
                    'Name'=> $profile_provider->provider->name,
                    'image' => $profile_provider->image->path,
                    'service' => $profile_provider->service->name,
                    'years_experience'=> $profile_provider->years_experience,
                    'description '=>$profile_provider->description,
                    'phone'=> $profile_provider->phone
                ];
            });

        return response()->json($profile_provider);

    }

    // ADMIN

    public function createService(Request $request) : JsonResponse
    {

        // Check if authenticated user is a provider
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',  // Adjust validation as needed
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('images', $imageName, 'public');
            $imageModel = Image::create(['path' => $imageName]);
            $imageId = $imageModel->id;
        } else {
            return response()->json(['error' => 'Image upload failed'], 400);
        }

        // Create the service
        $service = Service::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'image_id' => $imageId,
        ]);

        return response()->json($service, 201);
    }


    public function deleteService($id) : JsonResponse
    {
        // Find the service
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        // Delete the associated image file
        if ($service->image) {
            $imagePath = 'images/' . $service->image->path;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            // Optionally, delete the image record
            $service->image->delete();
        }

        // Delete the service record
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }
}
