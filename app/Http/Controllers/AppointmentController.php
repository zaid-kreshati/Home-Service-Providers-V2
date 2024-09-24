<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Emergency;
use App\Models\Notification;
use App\Models\Rating;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class AppointmentController extends Controller
{

    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

   // ############ Client #############
    // Create Appointment by Client

   /* public function createAppointment(Request $request , $provider_id) : JsonResponse
    {

        $client = Auth::user();
        if(!$client->hasRole('client')) {
            return response()->json(['Message'=> "You Don't have permissions | Just for Client"]);
        }

        // Define validation rules
        $rules = [
            'date' => 'required|date|after:now', // Validate the date column
            'hours' => 'required|date_format:H:i',
            'description' => 'nullable|string'
        ];
        $provider_id = (int) $provider_id;

       // dd($request->all());
        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            // Return validation errors
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

      //  dd($validatedData);

        // Create the appointment
        $appointment = Appointment::create([
            'client_id' => $client->id,
            'provider_id' => $provider_id,
            'date' => $validatedData['date'],
            'hours' => $validatedData['hours'],
            'description' => $validatedData['description'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Appointment created successfully', 'appointment' => $appointment], 201);

    }
   */

    public function createAppointment(Request $request , $provider_id) : JsonResponse
    {
        $client = Auth::user();

        // Ensure the user is a client
        if (!$client->hasRole('client')) {
            return response()->json(['message'=> "You don't have permissions | Just for Client"], 403);
        }

        // Validate the request data
        $validatedData = $request->validate([
            'date' => 'required|date|after:now',
            'hours' => 'required|date_format:H:i',
            'description' => 'nullable|string',
        ]);

        // Ensure the provider ID is an integer
        $provider_id = (int) $provider_id;

        // Create the appointment
        $appointment = Appointment::create([
            'client_id' => $client->id,
            'provider_id' => $provider_id,
            'date' => $validatedData['date'],
            'hours' => $validatedData['hours'],
            'description' => $validatedData['description'] ?? '',
        ]);

        return response()->json(['message' => 'Appointment created successfully', 'appointment' => $appointment], 201);

    }

    public function getClientAppointments() : JsonResponse
    {
        $client = Auth::user();
        if(!$client->hasRole('client')) {
            return response()->json(['Message'=> "You Don't have permissions | Just for Client"]);
        }

        $appointments = Appointment::where('client_id' , $client->id)->where('status' , 'approved')->with('provider')->with('provider.city')->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'provider' => $appointment->provider->name,
                    'status' => $appointment->status,
                    'date'=> $appointment->date,
                    'hour'=> $appointment->hours,
                    'city'=> $appointment->provider->city->city_name,
                    'description'=> $appointment->description
                ];
            });

        // Check if the collection is empty
        if ($appointments->isEmpty()) {
            $appointments = collect([
                [
                    'message' => 'No approved appointments found.',
                    'data' => []
                ]
            ]);
        }

        $ems = Emergency::where('client_id' , $client->id)->with('provider')->where('status' , 'approved')->with('provider.city')->get()
            ->map(function($em) {
                return [
                    'id'=> $em->id,
                    'provider'=> $em->provider->name ?? 'No One Approved Yet!' ,
                    'hour'=> '',
                    'date'=> '',
                    'status'=> $em->status,
                    'city'=> $em->provider->city->city_name ?? 'No One Approved Yet!',
                    'description'=> $em->description
                ];
            });

        // Check if the collection is empty
        if ($ems->isEmpty()) {
            $ems = collect([
                [
                    'message' => 'No emergencies found for this client.',
                    'data' => []
                ]
            ]);
        }



        // Merge the two collections
        $merged = $appointments->merge($ems);

        $response = $merged->toArray();

        return response()->json($response , 200);

        // return response()->json([ 'Appointments' => $appointments , 'Emergency' => $ems] , 200);
    }


    // Get Completed Finished Appointments
    public function getClientCompleted() : JsonResponse
    {
        $client = Auth::user();
        if (!$client->hasRole('client')) {
            return response()->json(['Message' => "You Don't have permissions | Just for Client"]);
        }

        $appointments = Appointment::where('client_id', $client->id)->where('status', 'finished')->with('provider')->with('provider.city')->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'provider' => $appointment->provider->name,
                    'status' => $appointment->status,
                    'date' => $appointment->date,
                    'hour' => $appointment->hours,
                    'city' => $appointment->provider->city->city_name,
                    'description' => $appointment->description
                ];
            });

        // Check if the collection is empty
        if ($appointments->isEmpty()) {
            $appointments = collect([
                [
                    'message' => 'No Completed | Finished appointments found.',
                    'data' => []
                ]
            ]);
        }

        return response()->json($appointments , 200);
    }

    // update client appointment to Finished , and add rate and comment
   /* public function finishAppointmentAndRate(Request $request, $appointment_id)
    {
        // Get the authenticated user
        $client = Auth::user();
        if(!$client->hasRole('client')) {
            return response()->json(['Message'=> "You Don't have permissions | Just for Client"]);
        }

        // Validate the input data
        $validator = Validator::make($request->all(), [
            'rating' => 'nullable|integer|between:1,5',
            'comment' => 'nullable|string',
            'status'=> 'required|in:finished'
        ]);
      //  dd($validator);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        // Fetch the appointment by appointment_id, client_id, and status
        $appointment = Appointment::where('id', $appointment_id)
            ->where('client_id', $client->id)
            ->where('status', 'approved')
            ->with('provider')
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found or you do not have permission to update this appointment'], 404);
        }

        // Update the appointment status to "finished"
        $appointment->status = 'finished';
        $appointment->save();

        $title = "Finished Appointment";
        $body = "The Client ". $client->name ." Finish the appointment";

        Notification::create([
           'user_id'=> $appointment->provider->id,
           'title'=> $title,
           'details'=>$body
        ]);

        $deviceTokens = [$appointment->provider->device_token];

        try {
            // Send notification
            $response = $this->firebaseService->sendNotification($deviceTokens, $title, $body);
            // Handle the response if needed
        } catch (\Exception $e) {
            // Handle the exception if needed
            return response()->json(['error' => $e->getMessage()], 500);
        }

        if (!(is_null($request->rating) || is_null($request->comment))) {
            // Create the rating
            Rating::create([
                'client_id' => $client->id,
                'provider_id' => $appointment->provider_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

        }
        return response()->json(['message' => 'Appointment finished and rating added successfully']);
    }
    */

    public function finishAppointmentAndRate(Request $request, $appointment_id)
    {
        $client = Auth::user();

        if (!$client->hasRole('client')) {
            return response()->json(['Message'=> "You don't have permissions | Just for Client"], 403);
        }

        $validatedData = $request->validate([
            'rating' => 'nullable|integer|between:1,5',
            'comment' => 'nullable|string',
            'status' => 'required|in:finished',
        ]);

        // Use a raw query or a more efficient query builder method to fetch the appointment
        $appointment = Appointment::where([
            ['id', '=', $appointment_id],
            ['client_id', '=', $client->id],
            ['status', '=', 'approved'],
        ])
            ->with('provider:id,device_token')
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found or you do not have permission to update this appointment'], 404);
        }

        // Update the appointment status asynchronously if this is a bottleneck
        $appointment->status = 'finished';
        $appointment->save();

        // Prepare notification data
        $title = "Finished Appointment";
        $body = "The Client " . $client->name . " has finished the appointment";

        // Offload the notification creation and sending to a job
        Notification::create([
            'user_id' => $appointment->provider->id,
            'title' => $title,
            'details' => $body
        ]);

        // Dispatch the notification to a queue
        dispatch(function() use ($appointment, $title, $body) {
            $deviceTokens = [$appointment->provider->device_token];
            try {
                $this->firebaseService->sendNotification($deviceTokens, $title, $body);
            } catch (\Exception $e) {
                \Log::error('Notification sending failed: ' . $e->getMessage());
            }
        });

        // Conditionally create the rating asynchronously
        if ($validatedData['rating'] !== null || $validatedData['comment'] !== null) {
            dispatch(function() use ($client, $appointment, $validatedData) {
                Rating::create([
                    'client_id' => $client->id,
                    'provider_id' => $appointment->provider_id,
                    'rating' => $validatedData['rating'] ?? 0,
                    'comment' => $validatedData['comment'] ?? '',
                ]);
            });
        }

        return response()->json(['message' => 'Appointment finished and rating added successfully']);
    }

    // Show specific appointment by client
    public function showAppointment($app_id) {
        $client = Auth::user();

       // dd($client->id);
        $appointment = Appointment::where('id' , $app_id)->where('client_id' , $client->id)
            ->get();

        return response()->json($appointment);

    }
    // Cancel Appointment By client
    public function cancelAppointment( Request $request , $appointment_id) {
        // Get the authenticated user
        $client = Auth::user();
        if(!$client->hasRole('client')) {
            return response()->json(['Message'=> "You Don't have permissions | Just for Client"]);
        }

        // Validate the input data
        $validator = Validator::make($request->all(), [
            'status'=> 'required|in:canceled'
        ]);



        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        // Fetch the appointment by appointment_id, client_id, and status
        $appointment = Appointment::where('id', $appointment_id)
            ->where('client_id', $client->id)
            ->whereIn('status', ['approved', 'pending'])
            ->first();



        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found or you do not have permission to update this appointment'], 404);
        }

        // Get the current time and the appointment date
        $currentDateTime = Carbon::now();
        $appointmentDateTime = Carbon::parse($appointment->date);

        // Check if there are at least 24 hours before the appointment date
        if ($currentDateTime->diffInHours($appointmentDateTime, false) < 24) {
            return response()->json(['message' => 'Cannot cancel the appointment. Less than 24 hours remain before the appointment date.'], 400);
        }

        // Update the appointment status to "finished"
        $appointment->status = 'canceled';
        $appointment->save();


        return response()->json(['message' => 'Appointment Canceled  successfully']);

    }



   // ############ Provider #############
    // get Requests , Pending | Accepted Appointments for provider
    public function getRequestsAndAcceptedAppointment() : JsonResponse
    {

        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => "Not Authorized | Providers Only"]);
        }

        $appointments = Appointment::where('provider_id', $provider->id)
            ->whereIn('status', ['pending', 'approved'])
            ->with('client:id,name')->with('client.city') // Assuming there is a relationship defined in the Appointment model
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'client' => $appointment->client->name,
                    'status' => $appointment->status,
                    'date'=> $appointment->date,
                    'hour'=> $appointment->hours,
                    'description' => $appointment->description,
                    'city'=>$appointment->client->city->city_name
                ];
            });

        return response()->json(['appointments' => $appointments]);

    }

    // Get Completed Appointments
    public function getCompletedAppointment() : JsonResponse
    {
        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => "Not Authorized | Providers Only"]);
        }

        $appointments = Appointment::where('provider_id', $provider->id)
            ->where('status', 'finished')
            ->with('client')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'client' => $appointment->client->name,
                    'status' => $appointment->status,
                    'date'=> $appointment->date,
                    'hour'=> $appointment->hours,
                    'city'=>$appointment->client->city->city_name,
                    'description'=> $appointment->description
                ];
            });

        return response()->json(['Completed' => $appointments]);
    }
    // update the status request , provider accept or reject appointments
    public function getRejectedAppointment() : JsonResponse
    {
        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => "Not Authorized | Providers Only"]);
        }

        $appointments = Appointment::where('provider_id', $provider->id)
            ->where('status', 'rejected')
            ->with('client')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'client' => $appointment->client->name,
                    'status' => $appointment->status,
                    'date'=> $appointment->date,
                    'hour'=> $appointment->hours,
                    'city'=>$appointment->client->city->city_name,
                    'description'=> $appointment->description
                ];
            });

        return response()->json(['Rejected' => $appointments]);
    }
    public function updateAppointmentStatus(Request $request, $appointment_id)
    {
        // Check if the authenticated user is the provider of the appointment
        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => "Not Authorized | Providers Only"]);
        }

        // Validate the input data
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the appointment by appointment_id and provider_id
        $appointment = Appointment::where('id', $appointment_id)
            ->where('provider_id', $provider->id)
            ->with('client')
            ->first();

        if (!$appointment) {
            return response()->json(['message' => "Appointment not found or you don't have permission to update this appointment"], 404);
        }

        // Update the appointment status
        $appointment->status = $request->status;
        $appointment->save();

        $title = "Appointment ". ucfirst($request->status) ;
        $body = "Your appointment ". $request->status . " By ". $provider->name;
        $client_id = $appointment->client->id;

        Notification::create([
            'user_id'=> $client_id,
            'title'=> $title,
            'details'=> $body
        ]);
        $deviceTokens = [$appointment->client->device_token];

        try {
            // Send notification
            $response = $this->firebaseService->sendNotification($deviceTokens, $title, $body);
            // Handle the response if needed
        } catch (\Exception $e) {
            // Handle the exception if needed
            return response()->json(['error' => $e->getMessage()], 500);
        }


        return response()->json(['message' => 'Appointment status updated successfully', 'appointment' => $appointment], 200);
    }

}
