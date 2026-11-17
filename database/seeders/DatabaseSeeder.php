<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);
        $this->call([StateSeeder::class,
        ElectricityZoneSeeder::class,
        SimNetworkSeeder::class
        ]);
    }
}
