<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->json('redeem_request_days')
                ->nullable()
                ->after('base_url');
        });

        // Set default days for existing records
        $defaultDays = [
            '1' => ['day' => 'Monday',    'status' => 'on'],
            '2' => ['day' => 'Tuesday',   'status' => 'on'],
            '3' => ['day' => 'Wednesday', 'status' => 'on'],
            '4' => ['day' => 'Thursday',  'status' => 'on'],
            '5' => ['day' => 'Friday',    'status' => 'on'],
            '6' => ['day' => 'Saturday',  'status' => 'on'],
            '7' => ['day' => 'Sunday',    'status' => 'on'],
        ];

        DB::table('quiz_settings')
            ->whereNull('redeem_request_days')
            ->update([
                'redeem_request_days' => json_encode($defaultDays),
            ]);
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn('redeem_request_days');
        });
    }
};
