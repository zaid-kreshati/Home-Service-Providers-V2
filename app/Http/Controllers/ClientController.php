<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function registerClient(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'device_token' => 'nullable|string',
            'city_id'=> 'nullable|exists:cities,id'
        ]);

        return $this->authService->register($data, 'client');
    }

    public function loginClient(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        return $this->authService->login($credentials);
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();
        if(!is_null($user)){
            Auth::user()->currentAccessToken()->delete(); // specific device , Request token
            // Auth::logout(); logout from all devices
            $message = 'User logged out Successfully';
            $code = 200;
        }
        else
        {
            $message = 'Invalid Token';
            $code = 404;
        }

        return response()->json([
            'message' => $message,
            'code' => $code
        ]);
    }

}
