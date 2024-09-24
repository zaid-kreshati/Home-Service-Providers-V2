<?php

namespace App\Services;

use App\Http\Requests\CreateAppointmentRequest;
use App\Jobs\CreateRatingJob;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Rating;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AppointmentClientService
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /*
     * --------------------
     * Client functions
     * --------------------
     */

    public function createAppointment(CreateAppointmentRequest $request, int $provider_id): JsonResponse
    {
        $client = Auth::user();

        // The validation is already handled by the FormRequest.
        // Authorization is also handled by the FormRequest.

        // Create the appointment
        $appointment = Appointment::create([
            'client_id' => $client->id,
            'provider_id' => $provider_id,
            'date' => $request->input('date'),
            'hours' => $request->input('hours'),
            'description' => $request->input('description'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Appointment created successfully', 'appointment' => $appointment], 201);
    }


    public function getClientAppointments(): JsonResponse
    {
        $client = Auth::user();
        if (!$client->hasRole('client')) {
            return response()->json(['Message' => "You Don't have permissions | Just for Client"], 403);
        }

        // Define a cache key
        $cacheKey = 'appointments_for_client_'.$client->id;
        $appointments = Cache::remember($cacheKey, 1000, function () use ($client) {
            return Appointment::where('client_id', $client->id)
                ->with(['provider:id,name'])->with('provider.city') // Select specific columns
                ->select('id', 'provider_id', 'status', 'date', 'hours', 'description') // Select only needed columns
                ->get()
                ->transform(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'provider' => $appointment->provider->name ?? 'N/A',
                        'status' => $appointment->status,
                        'date' => $appointment->date,
                        'hour' => $appointment->hours,
                        'city' => $appointment->provider->city->city_name ?? 'N/A',
                        'description' => $appointment->description,
                    ];
                });
        });

       /*
       * Test Cache Memory
       * Cache::put('$cacheKey' , '$appointments' , seconds:60);
       */

        return response()->json(['Appointments' => $appointments], 200);
    }


    public function finishAppointmentAndRate($appointment_id, $data) : JsonResponse
    {
        // Get the authenticated client
        $client = Auth::user();

        // Fetch the appointment by appointment_id, client_id, and status in a single query
        $appointment = Appointment::with('provider')
            ->where([
                ['id', $appointment_id],
                ['client_id', $client->id],
            ])->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found or you do not have permission to update this appointment'], 404);
        }

        // Wrap in a transaction to ensure atomicity
        DB::beginTransaction();
        try {
            // Update the appointment status to "finished"
            $appointment->update(['status' => 'finished']);

            // Create notification for the provider
            Notification::create([
                'user_id' => $appointment->provider->id,
                'title' => "Finished Appointment",
                'details' => "The Client " . $client->name . " finished the appointment",
            ]);

            // Dispatch the job for rating creation if provided
            if (!empty($data['rating']) || !empty($data['comment'])) {
                CreateRatingJob::dispatch(
                    $client->id,
                    $appointment->provider_id,
                    $data['rating'] ?? null,
                    $data['comment'] ?? null
                );
            }

            DB::commit();

            return response()->json(['message' => 'Appointment finished and rating added successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error finishing appointment: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while finishing the appointment. Please try again later.', 'Error' => $e->getMessage()], 500);
        }
    }


    public function showAppointment($app_id) : JsonResponse
    {
        $client = Auth::user();

        $cacheKey = sprintf('appointment.client.%d.appointment.%d', $client->id, $app_id);

        $appointment = Cache::remember($cacheKey, 1000, function () use ($client, $app_id) {
            return Appointment::where('id', $app_id)
                ->where('client_id', $client->id)
                ->first(); // Use first() if you expect a single appointment
        });

        return response()->json($appointment);
    }


    public function cancelAppointment(Request $request, $appointment_id): JsonResponse
    {
        $client = Auth::user();
        if (!$client->hasRole('client')) {
            return response()->json(['message' => "You don't have permissions | Just for Client"], 403);
        }

        $request->validate(['status' => 'required|in:canceled']);

        $appointment = Appointment::where('id', $appointment_id)
            ->where('client_id', $client->id)
            ->whereIn('status', ['approved', 'pending'])
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found or you do not have permission to update this appointment'], 404);
        }

        // Check if cancellation is allowed (24-hour rule)
        if ($this->isCancellationAllowed($appointment->date)) {
            return response()->json(['message' => 'Cannot cancel the appointment. Less than 24 hours remain before the appointment date.'], 400);
        }

        $appointment->update(['status' => 'canceled']);

        return response()->json(['message' => 'Appointment canceled successfully']);
    }

    private function isCancellationAllowed(string $appointmentDate): bool
    {
        return Carbon::now()->diffInHours(Carbon::parse($appointmentDate), false) <= 24;
    }


    /*
     * --------------------
     * Provider functions
     * --------------------
     */

    // get Requests , Pending | Accepted Appointments for provider
    public function getRequestsAndAcceptedAppointment() : JsonResponse
    {
        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['message' => "Not Authorized | Providers Only"] , 403);
        }

        $cacheKey = 'requests'.$provider->id;

        $appointments = Cache::remember($cacheKey , 1000 , function () use ($provider) {
           return Appointment::where('provider_id', $provider->id)
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
                       'city'=>$appointment->client->city->city_name ?? null ,
                   ];
               });
        });

        return response()->json(['appointments' => $appointments]);

    }

    public function getCompletedAppointment() : JsonResponse
    {
        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => "Not Authorized | Providers Only"] , 403);
        }

        $cacheKey = 'Completed'.$provider->id;

        $appointments = Cache::remember($cacheKey , 1000 , function () use ($provider) {
            return Appointment::where('provider_id', $provider->id)
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
        });

        return response()->json(['Completed' => $appointments]);
    }

    public function getRejectedAppointment() : JsonResponse
    {
        $provider = Auth::user();
        if(!$provider->hasRole('provider')) {
            return response()->json(['Message' => "Not Authorized | Providers Only"] , 403);
        }

        $cacheKey = 'Rejected'.$provider->id;
        $appointments = Cache::remember($cacheKey , 1000 , function () use ($provider) {
            return  Appointment::where('provider_id', $provider->id)
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
        });
        return response()->json(['Rejected' => $appointments]);
    }

    public function updateAppointmentStatus(Request $request, $appointment_id): JsonResponse
    {
        $provider = Auth::user();

        // Authorization check
        if (!$provider->hasRole('provider')) {
            return response()->json(['message' => "Not Authorized | Providers Only"], 403);
        }

        // Validate the input data
        $validatedData = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        // Find the appointment
        $appointment = Appointment::where('id', $appointment_id)
            ->where('provider_id', $provider->id)
            ->with('client:id,name,device_token')
            ->first();

        if (!$appointment) {
            return response()->json(['message' => "Appointment not found or you don't have permission to update this appointment"], 404);
        }

        // Update the appointment status
        $appointment->update(['status' => $validatedData['status']]);

        // Prepare and send notification
        $this->sendAppointmentNotification($appointment, $provider);

        return response()->json(['message' => 'Appointment status updated successfully', 'appointment' => $appointment], 200);
    }

    protected function sendAppointmentNotification(Appointment $appointment, $provider)
    {
        $title = "Appointment " . ucfirst($appointment->status);
        $body = "Your appointment has been " . $appointment->status . " by " . $provider->name;

        // Create notification record
        Notification::create([
            'user_id' => $appointment->client->id,
            'title' => $title,
            'details' => $body
        ]);

        // Send notification
        $this->sendNotification($appointment->client->device_token, $title, $body);
    }

    protected function sendNotification($deviceToken, $title, $body)
    {
        try {
            $this->firebaseService->sendNotification([$deviceToken], $title, $body);
        } catch (\Exception $e) {
            // Log the error but don't interrupt the flow
            Log::error('Failed to send notification: ' . $e->getMessage());
        }
    }
}

