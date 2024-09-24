<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    /**
     * Get all regions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllRegions() : JsonResponse
    {
        $regions = Region::select('id', 'region_name')->get();
        return response()->json($regions);
    }

    /**
     * Get all cities.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCities() : JsonResponse
    {
        $cities = City::select('id', 'city_name')->get();
        return response()->json($cities);
    }

    /**
     * Get all providers in a specific city.
     *
     * @param  int  $cityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProvidersByCity($cityId) : JsonResponse
    {
        $city = City::findOrFail($cityId);
        $providers = $city->users()->role('provider')->get();
        return response()->json($providers);
    }

    /**
     * Get all cities in a specific region.
     *
     * @param  int  $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCitiesByRegion($regionId)
    {
        $region = Region::findOrFail($regionId);
        $cities = $region->cities;
        return response()->json($cities);
    }




    // Admin
    /**
     *  Delete , Create , Region and city By Admin
     *
     *
     */

    /**
     * Add a new region.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addRegion(Request $request) : JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'region_name' => 'required|string|max:255',
        ]);

        if (Region::where('region_name', $validated['region_name'])->exists()) {
            return response()->json(['error' => 'This region already exists'], 409);
        }

        $region = Region::create([
            'region_name' => $validated['region_name'],
        ]);

        return response()->json([
            'message' => 'Region added successfully',
            'region' => $region,
        ], 201);
    }

    /**
     * Delete a region by ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRegion(int $id) : JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $region = Region::find($id);

        if (!$region) {
            return response()->json(['error' => 'Region not found'], 404);
        }

        $region->delete();

        return response()->json([
            'message' => 'Region deleted successfully',
        ], 200);
    }

    /**
     * Add a new city.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCity(Request $request) : JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'city_name' => 'required|string|max:255',
            'region_id' => 'required|integer|exists:regions,id',
        ]);

        if (City::where('city_name', $validated['city_name'])
            ->where('region_id', $validated['region_id'])->exists()) {
            return response()->json(['error' => 'This city already exists in the region'], 409);
        }

        $city = City::create([
            'city_name' => $validated['city_name'],
            'region_id' => $validated['region_id'],
        ]);

        return response()->json([
            'message' => 'City added successfully',
            'city' => $city,
        ], 201);
    }

    /**
     * Delete a city by ID.
     *
     * @param  int  $cityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCity(int $cityId) : JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $city = City::find($cityId);

        if (!$city) {
            return response()->json(['error' => 'City not found'], 404);
        }

        $city->delete();

        return response()->json([
            'message' => 'City deleted successfully',
        ], 200);
    }


}
