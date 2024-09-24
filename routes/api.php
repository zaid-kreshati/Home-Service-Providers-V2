<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();

});

// Select specific provider
Route::get('getProfile/{provider_id}' , [\App\Http\Controllers\ServicesController::class , 'selectSpecificProvider']);
// Profile Provider
Route::middleware('auth:sanctum')->group(function () {
    Route::post('create/profile', [ProfileController::class, 'createProfile']);
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);
    Route::get('/profile' , [ProfileController::class , 'getProfile']);
});


// Client Auth
Route::prefix('client')->controller(\App\Http\Controllers\ClientController::class)
    ->group(function () {
    Route::post('/login' , 'loginClient');
    Route::post('/register' , 'registerClient');

        Route::group(['middleware' => 'auth:sanctum'], function () {
            Route::post('/logout', 'logout');
        });
});


// Provider Auth
Route::prefix('provider')->controller(\App\Http\Controllers\ProviderController::class)
    ->group(function () {
        Route::post('/login' , 'loginProvider');
        Route::post('/register' , 'registerProvider');


        Route::group(['middleware' => 'auth:sanctum'], function () {
            Route::post('/logout', 'logout');
        });
    });


// Admin Auth
Route::prefix('admin')->controller(\App\Http\Controllers\ProviderController::class)
    ->group(function () {
        Route::post('/login' , 'loginProvider');
        Route::post('/register' , 'registerAdmin');

        Route::group(['middleware' => 'auth:sanctum'], function () {
            Route::post('/logout', 'logout');
        });
    });


// Location api
Route::get('/regions', [LocationController::class, 'getAllRegions']);
Route::get('/cities', [LocationController::class, 'getAllCities']);
Route::get('/cities/{cityId}/providers', [LocationController::class, 'getProvidersByCity']);
Route::get('/regions/{regionId}/cities', [LocationController::class, 'getCitiesByRegion']);


//################################################

// Services api
// Get All service providers by specific service
Route::get('/service/providersBy/service/{serviceId}' , [\App\Http\Controllers\ServicesController::class , 'providersByService']);
Route::middleware('auth:sanctum')->get('/service/providersBy/city/{serviceId}' , [\App\Http\Controllers\ServicesController::class , 'getProvidersByUserCityAndService']);
Route::get('/service/all' , [\App\Http\Controllers\ServicesController::class , 'getServices']);




// Search by Name Or Service
Route::post('/search' , [\App\Http\Controllers\SearchController::class , 'searchProviders']);

### Emergency
// Create Client Emergency Appointments
Route::middleware('auth:sanctum')
      ->post('/emergency/create/{service_id}' , [\App\Http\Controllers\EmergencyOrderController::class , 'createEmergency']);

// provider requests
Route::middleware('auth:sanctum')
    ->get('/provider/emergency/get/' , [\App\Http\Controllers\EmergencyOrderController::class , 'getProviderEmergencyRequest']);
// provider update request status
Route::middleware('auth:sanctum')
    ->post('/provider/emergency/update/{emergency_id}' , [\App\Http\Controllers\EmergencyOrderController::class , 'updateProviderEmergencyRequest']);



################################
// Appointment Routes
// create appointment by client
Route::middleware('auth:sanctum')
    ->post('client/appointment/create/{provider_id}' , [\App\Http\Controllers\AppointmentController::class , 'createAppointment']);

Route::middleware('auth:sanctum')
    ->get('client/appointment/get' , [\App\Http\Controllers\AppointmentController::class , 'getClientAppointments']);

// Show specific appointment
Route::middleware('auth:sanctum')
    ->get('client/appointment/show/{app_id}' , [\App\Http\Controllers\AppointmentController::class , 'showAppointment']);

// Cancel specific appointment
Route::middleware('auth:sanctum')
    ->post('client/appointment/cancel/{appointment_id}' , [\App\Http\Controllers\AppointmentController::class , 'cancelAppointment']);


// Finish and rate the provider
Route::middleware('auth:sanctum')
    ->post('client/appointment/rating/{appointment_id}' , [\App\Http\Controllers\AppointmentController::class , 'finishAppointmentAndRate']);

// Get completed Appointment by provider
Route::middleware('auth:sanctum')
    ->get('provider/appointments/completed' , [\App\Http\Controllers\AppointmentController::class , 'getCompletedAppointment']);

// Get completed Appointment by provider
Route::middleware('auth:sanctum')
    ->get('provider/appointments/rejected' , [\App\Http\Controllers\AppointmentController::class , 'getRejectedAppointment']);


// Get requests Appointment by provider
Route::middleware('auth:sanctum')
    ->get('provider/appointments' , [\App\Http\Controllers\AppointmentController::class , 'getRequestsAndAcceptedAppointment']);
// update status of appointment by provider
Route::middleware('auth:sanctum')
    ->post('provider/appointment/status/{appointment_id}' , [\App\Http\Controllers\AppointmentController::class , 'updateAppointmentStatus']);



###### Rates
// Get provider rates
Route::middleware('auth:sanctum')
    ->get('provider/rates/get' , [\App\Http\Controllers\RatingController::class , 'getRates']);



// Wallet , Subscription , Ads
Route::middleware(['auth:sanctum' , 'check.provider'])->group(function () {

    // Wallet
    Route::get('/provider/wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::post('provider/wallet/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');

    // subscribe
    Route::get('provider/subscribe/{planId}' ,[WalletController::class, 'payForSubscription']);

    // Ads
    Route::post('provider/create/ad' ,[WalletController::class, 'payForAdvertisement']);
    Route::get('provider/ads' ,[\App\Http\Controllers\AdsController::class, 'index']);

    // Subscription
    Route::get('/provider/plans', [\App\Http\Controllers\SubscriptionController::class, 'index']);
    Route::get('/provider/details', [\App\Http\Controllers\SubscriptionController::class, 'details']);

});


// Notifications
Route::middleware('auth:sanctum')
    ->get('/notifications' , [\App\Http\Controllers\NotificationController::class , 'getNotifications']);

Route::middleware('auth:sanctum')
    ->get('/client/profile' , [\App\Http\Controllers\NotificationController::class , 'clientProfile']);









// ----------------------------------------  Admin  -------------------------------------------------

// Services
Route::middleware('auth:sanctum')
    ->post('admin/addService' , [\App\Http\Controllers\ServicesController::class , 'createService']);
Route::middleware('auth:sanctum')
    ->delete('admin/delete/service/{id}' , [\App\Http\Controllers\ServicesController::class , 'deleteService']);


Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

    // Service routes
    Route::controller(ServicesController::class)->group(function () {
        Route::post('/addService', 'addService');
        Route::get('/getAllServices', 'getAllServices');
        Route::delete('/deleteService/{id}', 'deleteService');
        Route::post('/searchService', 'searchService');

    });

    // Location routes
    Route::controller(LocationController::class)->group(function () {
        Route::post('/addRegion', 'addRegion');
        Route::delete('/deleteRegion/{id}', 'deleteRegion');
        Route::post('/addCity', 'addCity');
        Route::delete('/deleteCity/{id}', 'deleteCity');
    });

    // Provider routes
    Route::controller(ProviderController::class)->group(function () {
        Route::post('/searchProvider', 'searchProvider');
        Route::delete('/deleteProvider/{id}', 'deleteProvider'); // delete the provider user and its profile
    });
});
