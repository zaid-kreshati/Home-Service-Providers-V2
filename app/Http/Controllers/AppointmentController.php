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

    public function finishAppointmentAndRate(FinishAppointmentRequest $request, $appointment_id): JsonResponse
    {
        return $this->appointmentService->finishAppointmentAndRate($appointment_id, $request->validated());
    }

    public function showAppointment($app_id): JsonResponse
    {
        return $this->appointmentService->showAppointment($app_id);
    }

    public function cancelAppointment(Request $request , $appointment_id)
    {
        return $this->appointmentService->cancelAppointment($request , $appointment_id);
    }

    /*
     *********************** Provider *************************
     */

    public function getRequestsAndAcceptedAppointment() : JsonResponse
    {
        return $this->appointmentService->getRequestsAndAcceptedAppointment();
    }

    public function getCompletedAppointment () : JsonResponse
    {
        return $this->appointmentService->getCompletedAppointment();
    }

    public function getRejectedAppointment() : JsonResponse
    {
        return $this->appointmentService->getRejectedAppointment();
    }

    public function updateAppointmentStatus(Request $request, $appointment_id): JsonResponse
    {
        return $this->appointmentService->updateAppointmentStatus($request , $appointment_id);
    }
}
