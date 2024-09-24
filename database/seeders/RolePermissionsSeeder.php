<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $clientRole = Role::create(['name' => 'client']);
        $providerRole = Role::create(['name' => 'provider']);

        // Define Permissions
        $adminPermissions = [ 'services.create' , 'services.delete',
                            'locations.add' , 'locations.delete' , 'providers.update' , 'providers.delete'];

        $clientPermissions = ['search' , 'rate' , 'appointments.create' , 'appointments.delete', 'appointments.view',
                                'emergency.create'];

        $providerPermissions = ['requests.accept' , 'requests.reject' , 'appointments.view'];


        // Create Permissions
        $allPermissions = array_unique(array_merge($adminPermissions, $providerPermissions, $clientPermissions));

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web'); // Also for API
        }

        // Assign Permissions to Roles
        $adminRole->syncPermissions($adminPermissions); // Admin gets all permissions
        $providerRole->syncPermissions($providerPermissions);
        $clientRole->syncPermissions($clientPermissions);

        // Create users and assign roles
        // Admin
        $adminUser = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
            'city_id'=> 1
        ]);
        $adminUser->assignRole($adminRole);

        // Provider
        $providerUser = User::factory()->create([
            'name' => 'provider',
            'email' => 'provider@provider.com',
            'password' => bcrypt('password'),
            'city_id'=> 1
        ]);
        $providerUser->assignRole($providerRole);

        // Client
        $clientUser = User::factory()->create([
            'name' => 'client',
            'email' => 'client@client.com',
            'password' => bcrypt('password'),
            'city_id'=> 1
        ]);
        $clientUser->assignRole($clientRole);

    }


}
