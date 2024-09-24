<?php

namespace App\Http\Controllers;

use App\Models\Emergency;
use App\Models\Emergency_Provider;
use App\Models\Notification;
use App\Models\Profile_Provider;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isEmpty;

class EmergencyOrderController extends Controller
{

    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    // Create Emergency order and Notify all providers in the same city
    public function createEmergency(Request $request) : JsonResponse
    {


        // Get the city_id of the authenticated user
        $authUserCityId = auth()->user()->city_id;

        if(!Auth::user()->hasRole('client')) {
            return response()->json(['Message'=> 'Not Authorized Client']);
        }

        // Fetch the providers who offer the requested service and are in the same city
        $providers = Profile_Provider::where('service_id', $request->service_id)
            ->whereHas('provider', function ($query) use ($authUserCityId) {
                $query->where('city_id', $authUserCityId);
            })
            ->with('provider')
            ->distinct('provider.id')
            ->get()
            ->pluck('provider');

        if($providers->isEmpty()) {
            //dd("No Providers In your city");
            return response()->json(["Message" => "There are No Providers Work In Your City"]);
        }

        // Extract device tokens
        $deviceTokens = $providers->pluck('device_token')->filter()->toArray();
      //  return response()->json($providers);

        $emergency = Emergency::create([
            'client_id' => auth()->id(),
            'service' => $request->service_id,
            'status' => 'pending',
            'description' => $request->description,
        ]);


        $title = 'New Emergency Request';
        $body = 'You have a new Emergency Request';


        foreach ($providers as $provider) {
            // Check if the record already exists
            Emergency_Provider::firstOrCreate([
                'provider_id' => $provider->id,
                'emergency_id' => $emergency->id,
            ]);

            Notification::create([
                'user_id'=> $provider->id,
                'title'=>$title ,
                'details'=> $body
            ]);
        }


        try {
            // Send notification
            $response = $this->firebaseService->sendNotification($deviceTokens, $title, $body);
            // Handle the response if needed
        } catch (\Exception $e) {
            // Handle the exception if needed
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Emergency created and providers notified.',
            'data'=> $response
        ]);
    }


    // Get Provider Requests (Emergency) and update it's status
    public function getProviderEmergencyRequest() : JsonResponse
    {
        $provider = Auth::user();

        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => "Not Authorized"]);
        }

        $emergencyRequests = Emergency_Provider::where('provider_id' , $provider->id)
            ->with('emergency')
            ->whereHas('emergency', function ($query) {
               $query->where('status', 'pending');
            })
            ->get();

        if($emergencyRequests->isEmpty()) {
            return response()->json(['Message' => "You Don't Have Emergency Requests"]);
        }

        return response()->json($emergencyRequests);
    }

    // Update the status of the Emergency request : Approved / Canceled
    public function updateProviderEmergencyRequest(Request $request) : JsonResponse
    {
        $provider = Auth::user();

        if (!$provider->hasRole('provider')) {
            return response()->json(['message' => 'Not Authorized'], 403);
        }

        $emergencyProvider = Emergency_Provider::where('provider_id', $provider->id)
            ->where('emergency_id' , $request->emergency_id)
            ->whereHas('emergency', function ($query) {
                $query->where('status', 'pending');
            })
            ->first();


        if (!$emergencyProvider) {
            return response()->json(['message' => 'Pending emergency request not found'], 404);
        }

        $request->validate([
            'status' => 'required|in:approved,canceled'
        ]);

        if($request->status == 'canceled') {
            $emergency_providers = Emergency_Provider::where('emergency_id' , $emergencyProvider->emergency_id)
                ->where('provider_id' , $provider->id)
                ->get();

            foreach ($emergency_providers as $emergencyProvider) {
                $emergencyProvider->delete();
            }

            return response()->json(['Message' => "Request Deleted"]);
        }

        $emergency = $emergencyProvider->emergency;
        $emergency->status = $request->status;
        $emergency->provider_id = $provider->id;
        $emergency->save();

        // delete data from pivot table
        if ($request->status == 'approved') {
            $emergency_providers = Emergency_Provider::where('emergency_id' , $emergencyProvider->emergency_id)->get();
            foreach ($emergency_providers as $em_pr) {
                $em_pr->delete();
            }

            $emergency = Emergency::with('client')
                ->where('id', $emergencyProvider->emergency_id)
                ->first();

            $client_id = $emergency ? $emergency->client_id : null;

            $deviceTokens = [$emergency->client->device_token];

            $title = "Request Approved";
            $body = "The Provider ". $provider->name . " Approved your emergency request";

            Notification::create([
                'user_id'=> $client_id,
                'title'=>$title ,
                'details'=> $body
            ]);


            try {
                // Send notification
                $response = $this->firebaseService->sendNotification($deviceTokens, $title, $body);
                // Handle the response if needed
            } catch (\Exception $e) {
                // Handle the exception if needed
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        return response()->json(['message' => 'Emergency request status updated successfully']);
    }

}
