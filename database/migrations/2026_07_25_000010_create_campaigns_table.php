<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name');
            $table->json('channels')->nullable();
            $table->string('segment')->nullable();
            $table->enum('schedule_type', ['now', 'later'])->default('now');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('deep_link')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedTinyInteger('open_rate')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sent'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
