<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppointmentRequest;
use App\Http\Requests\FinishAppointmentRequest;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Rating;
use App\Services\AppointmentClientService;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
class AppointmentController extends Controller
{
    protected $firebaseService;
    protected $appointmentService;


    public function __construct(FirebaseService $firebaseService , AppointmentClientService $appointmentService)
    {
        $this->firebaseService = $firebaseService;
        $this->appointmentService = $appointmentService;
    }

    /*
     *********************** Client *************************
     */

    // Create Appointment by Client
    public function createAppointment(CreateAppointmentRequest $request, int $provider_id): JsonResponse
    {
        return $this->appointmentService->createAppointment($request, $provider_id);
    }

    public function getClientAppointments(): JsonResponse
    {
        return $this->appointmentService->getClientAppointments();
    }

    public function finishAppointmentAndRate(FinishAppointmentRequest $request, $appointment_id)
    {
        return $this->appointmentService->finishAppointmentAndRate($appointment_id, $request->validated());
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
