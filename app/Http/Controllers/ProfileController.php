<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Image;
use App\Models\Profile_Provider;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfileController extends Controller
{
    use HasRoles;
    public function createProfile(Request $request) : JsonResponse
    {
        // Check if authenticated user is a provider
        $user = Auth::user();


        if (!$user->hasRole('provider')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        // Validate the input data
        $validated = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'years_experience' => 'required|integer|min:0',
            'phone' => 'required|string|max:15',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);
        //  dd($validator);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }


        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = $image->getClientOriginalName();
            $image->storeAs('images', $imageName, 'public');
            $image = Image::create(['path' => $imageName]);
            $imageId = $image->id;
        } else {
            return response()->json(['error' => 'Image upload failed'], 400);
        }
        // Create profile provider
        $profileProvider = Profile_Provider::create([
            'provider_id' => Auth::id(),
            'service_id' => $request['service_id'],
            'image_id' => $imageId,
            'years_experience' => $request['years_experience'],
            'phone' => $request['phone'],
            'description' => $request['description'],
        ]);

// , 'profile' => $profileProvider
        return response()->json(['success' => 'Profile created successfully'], 201);
    }


    public function updateProfile(Request $request) : JsonResponse
    {
        // Check if authenticated user is a provider
        $user = Auth::user();

        if (!$user->hasRole('provider')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate the input data
        $validated = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'years_experience' => 'required|integer|min:0',
            'phone' => 'required|string|max:15',
            'description' => 'nullable|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif',
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        // Find the existing profile provider
        $profileProvider = Profile_Provider::where('provider_id', $user->id)->first();

        if (!$profileProvider) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        // Handle image upload if present
        if ($request->hasFile('image')) {
            // Delete the old image if exists
//            if ($profileProvider->image) {
//                Storage::disk('public')->delete($profileProvider->image->path);
//                $profileProvider->image->delete();
//            }

            $image = $request->file('image');
            $imageName = $image->getClientOriginalName();
            $image->storeAs('images', $imageName, 'public');
            $image = Image::create(['path' => $imageName]);

          //  $imagePath = $request->file('image')->store('images', 'public');

            $profileProvider->image_id = $image->id;
            $profileProvider->save();
        }

        // Update profile provider
        $profileProvider->update([
            'service_id' => $request['service_id'],
            'years_experience' => $request['years_experience'],
            'phone' => $request['phone'],
            'description' => $request['description'],
        ]);

        // Load the image relation to include the path in the response
        $profileProvider->load('image');

        return response()->json([
            'success' => 'Profile updated successfully',
            'profile' => [
                'provider_id' => $profileProvider->provider_id,
                'service_id' => $profileProvider->service_id,
                'years_experience' => $profileProvider->years_experience,
                'phone' => $profileProvider->phone,
                'description' => $profileProvider->description,
                'image_name' => $profileProvider->image->path ?: null,
            ]
        ], 200);
    }

    public function getProfile() {
        $provider = Auth::user();

        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => " Not Authorized "]);
        }

        $city = City::where( 'id' , $provider->city_id)->with('region')->first();

        // Check if the provider has a profile
        $profile = $provider->profile;
      //  $imageName = $provider->profile->image->path;

      //  dd($profile);
        // If the profile exists, return it
        if (!is_null($profile)) {
            return response()->json([
                'years_experience' => $profile->years_experience ,
                'image'=>  $profile->image->path ,
                'description' => $profile->description,
                'phone'=> $profile->phone,
                'service' =>$profile->service->name,
                'city'=> $city->city_name,
                'region'=> $city->region->region_name
            ]);
        }

        // If the profile does not exist, return fake data
        $fakeProfile = [
            'provider_id' => $provider->id,
            'service_id' => "No Service Selected", // You can specify fake service_id if needed
            'image_id' => "No Image",   // You can specify fake image_id if needed
            'years_experience' => 0, // Example fake data
            'phone' => '000-000-0000', // Example fake phone number
            'description' => 'This is a default description.', // Example fake description
        ];

        return response()->json(['profile' => $fakeProfile]);
    }

}
