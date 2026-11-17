<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['email' => 'support@chaloonline.in'],
            [
                'user_id' => 1,
                'name' => 'Super Admin',
                'slug' => 'fsr',
                'prefix' => 'fsr',
                'email' => 'support@chaloonline.in',
                'phone' => '8889710101',
                'logo' => 'logo/17823649736a3cbb2d7bc7a2.98832708.webp',
                'address' => '83A Narendra Tiwari Marg, Sudama Nager, Indore',
                'landmark' => 'Narendra Tiwari Marg',
                'pincode' => '452010',
                'city' => 'Indore',
                'state' => 'Madhya Pradesh',
                'website' => 'chaloonline.in',
                'theme' => 'webapp',
                'favicon' => 'favicon/17823650006a3cbb48981090.88203289.webp',
                'dark_logo' => 'dark_logo/17823650006a3cbb48ca8573.47216850.webp',
                'open_time' => '10:00:00',
                'close_time' => '10:00:00',
                'off_day' => 'sunday',
                'updated_by_id' => 1,
                'updated_by_name' => 'Sandesh Dua',
                'updated_by_role' => 1,            

                'status' => 'active',
                'store_status' => 'active',                
            ]
        );

        $role = Role::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => 'superadmin'],
            [
                'tenant_id' => $tenant->id,
                'slug' => 'superadmin',
                'name' => 'Super Admin',
                'description' => 'Full system access with all permissions',
            ]
        );
        

        $user = AdminUser::updateOrCreate(
            ['email' => 'support@chaloonline.in'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Super Admin',
                'email' => 'support@chaloonline.in',
                'mobile' => '8889710101',
                'password' => '123456', // auto-hashed by the User model's cast
                'role' => $role->slug,
                'role_id' => $role->id,
                'is_active' => 1,
                'can_login' => 1,
                'otp_enabled' => 0,
            ]
        );
        
        $role = Role::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => 'admin'],
            [
                'tenant_id' => $tenant->id,
                'slug' => 'admin',
                'name' => 'Admin',
                'description' => 'Admin Full system access with all permissions',
            ]
        );
       
        $tenant->update([
            'user_id' => $user->id,
        ]);

    }
}
