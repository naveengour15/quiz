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
        Schema::table('users', function (Blueprint $table) {
            $table->string('alternative_mobile')->nullable()->after('role_id');
            $table->boolean('verify_mobile')->default(false)->after('alternative_mobile');
            $table->boolean('verify_email')->default(false)->after('verify_mobile');
            $table->string('state')->nullable()->after('verify_email');
            $table->string('city')->nullable()->after('state');
            $table->text('address')->nullable()->after('city');
            $table->decimal('lat', 10, 8)->nullable()->after('address');
            $table->decimal('long', 11, 8)->nullable()->after('lat');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('long');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('favorite_color')->nullable()->after('date_of_birth');
            $table->text('hobbies')->nullable()->after('favorite_color');
            $table->text('games')->nullable()->after('hobbies');
            $table->text('interest')->nullable()->after('games');
            $table->string('profile_photo')->nullable()->after('interest');
            $table->integer('quiz_leave')->default(0)->after('profile_photo');
            $table->integer('quiz_rank')->default(0)->after('quiz_leave');
            $table->decimal('wallet_balance', 12, 2)->default(0)->after('quiz_rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'alternative_mobile',
                'verify_mobile',
                'verify_email',
                'state',
                'city',
                'address',
                'lat',
                'long',
                'gender',
                'date_of_birth',
                'favorite_color',
                'hobbies',
                'games',
                'interest',
                'profile_photo',
                'quiz_leave',
                'quiz_rank',
                'wallet_balance',
            ]);
        });
    }
};