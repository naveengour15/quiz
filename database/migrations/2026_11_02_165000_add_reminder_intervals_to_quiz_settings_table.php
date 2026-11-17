<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('quiz_settings', 'reminder_intervals')) {
            Schema::table('quiz_settings', function (Blueprint $table) {
                $table->json('reminder_intervals')
                    ->nullable()
                    ->after('redeem_request_days');
            });
        }

        $defaultIntervals = [
            ['value' => 15, 'unit' => 'minute'],
            ['value' => 10, 'unit' => 'minute'],
            ['value' => 5,  'unit' => 'minute'],
            ['value' => 3,  'unit' => 'minute'],
            ['value' => 1,  'unit' => 'minute'],
            ['value' => 30, 'unit' => 'second'],
        ];

        DB::table('quiz_settings')
            ->whereNull('reminder_intervals')
            ->update([
                'reminder_intervals' => json_encode($defaultIntervals),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('quiz_settings', 'reminder_intervals')) {
            Schema::table('quiz_settings', function (Blueprint $table) {
                $table->dropColumn('reminder_intervals');
            });
        }
    }
};

