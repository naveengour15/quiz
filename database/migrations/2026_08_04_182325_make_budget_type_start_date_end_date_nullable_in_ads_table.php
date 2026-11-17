<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
              $table->enum('budget_type', ['daily', 'total'])
                  ->nullable()
                  ->change();

            $table->date('start_date')
                  ->nullable()
                  ->change();

            $table->date('end_date')
                  ->nullable()
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
             $table->enum('budget_type', ['daily', 'total'])
                  ->nullable(false)
                  ->change();

            $table->date('start_date')
                  ->nullable(false)
                  ->change();

            $table->date('end_date')
                  ->nullable(false)
                  ->change();
        });
    }
};
