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
        Schema::table('quiz_question', function (Blueprint $table) {
            $table->unsignedInteger('tenant_id')->nullable()->after('id');
        }); 

        Schema::table('whats_app_template_settings', function (Blueprint $table) {
            $table->unsignedInteger('tenant_id')->nullable()->after('id');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_question', function (Blueprint $table) {
          $table->dropColumn('tenant_id');          
        });

        Schema::table('whats_app_template_settings', function (Blueprint $table) {
          $table->dropColumn('tenant_id');          
        });
    }
};
