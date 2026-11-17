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
        Schema::create('announcement_history', function (Blueprint $table) {
            $table->id();
              $table->unsignedBigInteger('tenant_id')->nullable();

                $table->unsignedBigInteger('announcement_id');

                $table->unsignedBigInteger('user_id')->nullable();

                $table->enum('status', ['accept', 'reject'])->default('accept');
                 $table->timestamp('read_at')->useCurrent();
                $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_history');
    }
};
