<?php

namespace Database\Seeders;

use App\Models\ElectricityZone;
use App\Models\State;
use Illuminate\Database\Seeder;

class ElectricityZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            'Andhra Pradesh' => [
                ['zone' => 'Southern Power Distribution', 'zone_code' => 'APSPDCL'],
                ['zone' => 'Eastern Power Distribution', 'zone_code' => 'APEPDCL'],
            ],

            'Arunachal Pradesh' => [
                ['zone' => 'Arunachal Pradesh Power', 'zone_code' => 'APDCL'],
            ],

            'Assam' => [
                ['zone' => 'Assam Power Distribution', 'zone_code' => 'APDCL'],
            ],

            'Bihar' => [
                ['zone' => 'North Bihar Power Distribution', 'zone_code' => 'NBPDCL'],
                ['zone' => 'South Bihar Power Distribution', 'zone_code' => 'SBPDCL'],
            ],

            'Chhattisgarh' => [
                ['zone' => 'Chhattisgarh State Power Distribution', 'zone_code' => 'CSPDCL'],
            ],

            'Goa' => [
                ['zone' => 'Electricity Department Goa', 'zone_code' => 'EDGOA'],
            ],

            'Gujarat' => [
                ['zone' => 'Dakshin Gujarat Vij Company', 'zone_code' => 'DGVCL'],
                ['zone' => 'Madhya Gujarat Vij Company', 'zone_code' => 'MGVCL'],
                ['zone' => 'Paschim Gujarat Vij Company', 'zone_code' => 'PGVCL'],
                ['zone' => 'Uttar Gujarat Vij Company', 'zone_code' => 'UGVCL'],
            ],

            'Haryana' => [
                ['zone' => 'Dakshin Haryana Bijli Vitran Nigam', 'zone_code' => 'DHBVN'],
                ['zone' => 'Uttar Haryana Bijli Vitran Nigam', 'zone_code' => 'UHBVN'],
            ],

            'Himachal Pradesh' => [
                ['zone' => 'Himachal Pradesh State Electricity Board', 'zone_code' => 'HPSEBL'],
            ],

            'Jharkhand' => [
                ['zone' => 'Jharkhand Bijli Vitran Nigam', 'zone_code' => 'JBVNL'],
            ],

            'Karnataka' => [
                ['zone' => 'Bangalore Electricity Supply Company', 'zone_code' => 'BESCOM'],
                ['zone' => 'Chamundeshwari Electricity Supply Corporation', 'zone_code' => 'CESC'],
                ['zone' => 'Gulbarga Electricity Supply Company', 'zone_code' => 'GESCOM'],
                ['zone' => 'Hubli Electricity Supply Company', 'zone_code' => 'HESCOM'],
                ['zone' => 'Mangalore Electricity Supply Company', 'zone_code' => 'MESCOM'],
            ],

            'Kerala' => [
                ['zone' => 'Kerala State Electricity Board', 'zone_code' => 'KSEB'],
            ],

            'Madhya Pradesh' => [
                ['zone' => 'Madhya Pradesh Madhya Kshetra Vidyut Vitran', 'zone_code' => 'MPMKVVCL'],
                ['zone' => 'Madhya Pradesh Poorv Kshetra Vidyut Vitran', 'zone_code' => 'MPPKVVCL'],
                ['zone' => 'Madhya Pradesh Paschim Kshetra Vidyut Vitran', 'zone_code' => 'MPPKVVCL'],
            ],

            'Maharashtra' => [
                ['zone' => 'Maharashtra State Electricity Distribution', 'zone_code' => 'MSEDCL'],
            ],

            'Manipur' => [
                ['zone' => 'Manipur State Power Distribution', 'zone_code' => 'MSPDCL'],
            ],

            'Meghalaya' => [
                ['zone' => 'Meghalaya Power Distribution Corporation', 'zone_code' => 'MePDCL'],
            ],

            'Mizoram' => [
                ['zone' => 'Power and Electricity Department Mizoram', 'zone_code' => 'PEDMZ'],
            ],

            'Nagaland' => [
                ['zone' => 'Department of Power Nagaland', 'zone_code' => 'DPN'],
            ],

            'Odisha' => [
                ['zone' => 'TP Central Odisha Distribution', 'zone_code' => 'TPCODL'],
                ['zone' => 'TP Northern Odisha Distribution', 'zone_code' => 'TPNODL'],
                ['zone' => 'TP Southern Odisha Distribution', 'zone_code' => 'TPSODL'],
                ['zone' => 'TP Western Odisha Distribution', 'zone_code' => 'TPWODL'],
            ],

            'Punjab' => [
                ['zone' => 'Punjab State Power Corporation', 'zone_code' => 'PSPCL'],
            ],

            'Rajasthan' => [
                ['zone' => 'Ajmer Vidyut Vitran Nigam', 'zone_code' => 'AVVNL'],
                ['zone' => 'Jaipur Vidyut Vitran Nigam', 'zone_code' => 'JVVNL'],
                ['zone' => 'Jodhpur Vidyut Vitran Nigam', 'zone_code' => 'JDVVNL'],
            ],

            'Sikkim' => [
                ['zone' => 'Power Department Sikkim', 'zone_code' => 'PDS'],
            ],

            'Tamil Nadu' => [
                ['zone' => 'Tamil Nadu Generation and Distribution Corporation', 'zone_code' => 'TANGEDCO'],
            ],

            'Telangana' => [
                ['zone' => 'Northern Power Distribution Company of Telangana', 'zone_code' => 'TSNPDCL'],
                ['zone' => 'Southern Power Distribution Company of Telangana', 'zone_code' => 'TSSPDCL'],
            ],

            'Tripura' => [
                ['zone' => 'Tripura State Electricity Corporation', 'zone_code' => 'TSECL'],
            ],

            'Uttar Pradesh' => [
                ['zone' => 'Dakshinanchal Vidyut Vitran Nigam', 'zone_code' => 'DVVNL'],
                ['zone' => 'Madhyanchal Vidyut Vitran Nigam', 'zone_code' => 'MVVNL'],
                ['zone' => 'Paschimanchal Vidyut Vitran Nigam', 'zone_code' => 'PVVNL'],
                ['zone' => 'Purvanchal Vidyut Vitran Nigam', 'zone_code' => 'PUVVNL'],
                ['zone' => 'KESCO Kanpur', 'zone_code' => 'KESCO'],
            ],

            'Uttarakhand' => [
                ['zone' => 'Uttarakhand Power Corporation', 'zone_code' => 'UPCL'],
            ],

            'West Bengal' => [
                ['zone' => 'West Bengal State Electricity Distribution', 'zone_code' => 'WBSEDCL'],
            ],

            'Delhi' => [
                ['zone' => 'BSES Rajdhani Power', 'zone_code' => 'BRPL'],
                ['zone' => 'BSES Yamuna Power', 'zone_code' => 'BYPL'],
                ['zone' => 'Tata Power Delhi Distribution', 'zone_code' => 'TPDDL'],
            ],
        ];

        foreach ($zones as $stateName => $stateZones) {
            $state = State::where('name', $stateName)->first();

            if (!$state) {
                $this->command?->warn("State not found: {$stateName}");
                continue;
            }

            foreach ($stateZones as $zone) {
                ElectricityZone::updateOrCreate(
                    [
                        'state_id' => $state->id,
                        'zone_code' => $zone['zone_code'],
                    ],
                    [
                        'zone' => $zone['zone'],
                        'status' => 'active',
                    ]
                );
            }
        }

        $this->command?->info('Electricity zones seeded successfully.');
    }
}