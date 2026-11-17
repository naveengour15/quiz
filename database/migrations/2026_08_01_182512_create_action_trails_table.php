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
        Schema::create('action_trails', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('tenant_id')->nullable();

            $table->string('table_name');

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('action')->nullable();
            // create, update, delete, approve, reject etc.

            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unsignedBigInteger('updated_by_role_id')->nullable();

            $table->longText('old_details')->nullable();

            $table->longText('details')->nullable();
            // json or text details

            $table->string('status')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('table_name');
            $table->index('reference_id');
            $table->index('updated_by');

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_trails');
    }
};
