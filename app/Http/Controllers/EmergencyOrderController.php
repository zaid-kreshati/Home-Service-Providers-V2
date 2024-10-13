<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyProvidersJob;
use App\Models\Emergency;
use App\Models\Emergency_Provider;
use App\Models\Notification;
use App\Models\Profile_Provider;
use App\Models\User;
use App\Services\EmergencyOrderService;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use function PHPUnit\Framework\isEmpty;

class EmergencyOrderController extends Controller
{

    protected FirebaseService $firebaseService;
    protected EmergencyOrderService $emergencyService;

    public function __construct(FirebaseService $firebaseService , EmergencyOrderService $emergencyService)
    {
        $this->firebaseService = $firebaseService;
        $this->emergencyService = $emergencyService;
    }

    // Create Emergency order and Notify all providers in the same city
    public function createEmergency(Request $request) : JsonResponse
    {
        try {
            [$emergency, $providers] = $this->emergencyService->createEmergency($request);

            // Dispatch the job to notify providers
            NotifyProvidersJob::dispatch($providers, $emergency);

            return response()->json([
                'message' => 'Emergency created and providers will be notified.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get Provider Requests (Emergency) and update it's status
    public function getProviderEmergencyRequest() : JsonResponse
    {
        try {
            $emergencyRequests = $this->emergencyService->getProviderEmergencyRequest();

            return response()->json(['data'=> $emergencyRequests]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

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
            $body = "The Provider ". $provider->name . "Approved your emergency request";

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
