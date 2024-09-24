<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    // Client Register
    public function registerClient(Request $request) : JsonResponse
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('client');
        $permissions = Role::findByName('client')->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);
        $user->load('roles', 'permissions');
        $user['token'] = $user->createToken('MyApp')->plainTextToken;

        return response()->json([
            'data' => $user,
            'message' => 'Client created successfully',
        ], 200);

    }
    public function loginClient(Request $request) : JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $user['token'] = $user->createToken('MyApp')->plainTextToken;

            return response()->json([
                'data' => $user,
                'message' => 'Login successful',
            ], 200);
        }

        return response()->json([
            'message' => 'The provided credentials do not match our records.',
        ], 401);
    }


    // Provider Register
    public function registerProvider(Request $request) :JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('provider');
        $permissions = Role::findByName('provider')->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);
        $user->load('roles', 'permissions');
        $user['token'] = $user->createToken('MyApp')->plainTextToken;

        return response()->json([
            'data' => $user,
            'message' => 'User created successfully',
        ], 200);
    }
    public function loginProvider(Request $request) : JsonResponse
    {
        // Attempt to log in the user with the provided credentials
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the device_token is provided in the request
            if ($request->has('device_token')) {
                // Update the user's device_token
                $user->device_token = $request->device_token;
                $user->save();
            }

            // Create a new API token for the user
            $user['token'] = $user->createToken('MyApp')->plainTextToken;

            // Return a successful response with the user data
            return response()->json([
                'data' => $user,
                'message' => 'Login successful',
            ], 200);
        }

        // Return an error response if the credentials do not match
        return response()->json([
            'message' => 'The provided credentials do not match our records.',
        ], 401);
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
