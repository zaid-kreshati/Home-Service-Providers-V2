<?php

namespace App\Services;

use App\Models\Profile_Provider;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthService
{

    public function register(array $data, string $roleName): JsonResponse
    {
        $user = $this->createUser($data);
        $this->assignRoleAndPermissions($user, $roleName);

        $user['token'] = $user->createToken('MyApp')->plainTextToken;


        if($roleName == 'provider')
        {
            Wallet::create([
                'provider_id' => $user['id'],
                'balance'=> 0,
            ]);

            Profile_Provider::create([
                'provider_id'=> $user['id'],
                'image_id'=>1,
                'service_id'=>1,
                'years_experience'=>0,
                'description'=> 'This is description',
                'phone'=> 000000000
            ]);
        }

        return response()->json([
            'data' => $user,
            'message' => ' created successfully',
        ], 200);
    }

    public function login(array $credentials): JsonResponse
    {
        // Extract device_token from credentials if it exists
        $deviceToken = $credentials['device_token'] ?? null;

        // Remove device_token from credentials array to use only for login attempt
        unset($credentials['device_token']);

        if ($this->attemptLogin($credentials)) {
            $user = Auth::user();
            // Check if the device_token is provided and save it to the user
            if ($deviceToken) {
                $user->device_token = $deviceToken;
                $user->save();
            }

            // Get the roles of the user
            $roles = $user->getRoleNames();

            // Create a new API token for the user
            $user['token'] = $user->createToken('MyApp')->plainTextToken;

            return response()->json([
                'data' => [
                    'user' => $user,
                    'roles' => $roles,
                ],
                'message' => 'Login successful',
            ], 200);
        }

        return response()->json([
            'message' => 'The provided credentials do not match our records.',
        ], 401);
    }

    private function attemptLogin(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    private function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'device_token' => $data['device_token'] ?? null,
            'city_id' => $data['city_id'],
        ]);
    }

    private function assignRoleAndPermissions(User $user, string $roleName): void
    {
        $user->assignRole($roleName);
        $permissions = Role::findByName($roleName)->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);
        $user->load('roles', 'permissions');
    }

}
