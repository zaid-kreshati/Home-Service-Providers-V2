<?php

namespace App\Services;

use App\Http\Requests\CreateAppointmentRequest;
use App\Jobs\CreateRatingJob;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentClientService
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

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


    public function finishAppointmentAndRate($appointment_id, $data)
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

