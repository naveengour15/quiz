<?php

namespace Database\Seeders;

use App\Models\SimNetwork;
use Illuminate\Database\Seeder;

class SimNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $networks = [
            [
                'name' => 'Airtel',
                'code' => 'AIRTEL',
            ],
            [
                'name' => 'Jio',
                'code' => 'JIO',
            ],
            [
                'name' => 'Vi',
                'code' => 'VI',
            ],
            [
                'name' => 'BSNL',
                'code' => 'BSNL',
            ],
            [
                'name' => 'MTNL',
                'code' => 'MTNL',
            ],
        ];

        foreach ($networks as $network) {
            SimNetwork::updateOrCreate(
                [
                    'code' => $network['code'],
                ],
                [
                    'name' => $network['name'],
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('SIM networks seeded successfully.');
    }
}