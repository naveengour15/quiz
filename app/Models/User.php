<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'tenant_id',
    'name',
    'player_name',
    'email',
    'password',
    'mobile',
    'login_type',
    'login_method',
    'role','role_id',
    'alternative_mobile',
    'verify_mobile', 'verify_email',
    'state', 'city', 'address', 'lat', 'long',
    'gender',
    'date_of_birth',
    'favorite_color',
    'hobbies',
    'games',
    'interest',
    'profile_photo',
    'referral_code', 'referral_code_to', 'referral_user_id', 'user_referral_limit',
    'create_quiz_limit', 'available_quiz_limit', 'quiz_leave', 'quiz_rank',
    'wallet_balance',
    'manual_module_access',
    'department_access',
    'is_active',
    'can_login',
    'otp_enabled',
    'profile_status',
    'session_token',
    'last_login_at',
    'email_verified_at',
    'leader_board_point',
      'language',
      'on_off',
])]

#[Hidden([
    'password',
    'remember_token'
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, Notifiable;
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
   
            // JSON fields
            'favorite_color' => 'array',
            'hobbies' => 'array',
            'games' => 'array',
            'interest' => 'array',

        ];
    }

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasChanged('is_active') && (!$user->is_active || (int) $user->is_active === 0)) {
                QuizNotification::where('user_id', $user->id)->delete();
            }
        });
    }

    public function isAllowedToLogin(): bool
    {
        return $this->is_active && $this->can_login;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

       public function quizUsers()
    {
        return $this->hasMany(QuizUser::class, 'user_id', 'id');
    }

   
    public function quizAnswers()
    {
        return $this->hasMany(QuizQuestionUserAnswer::class, 'user_id', 'id');
    }

    public function quizzesPlayed()
    {
        return $this->hasManyThrough(Quiz::class, QuizUser::class, 'user_id', 'id', 'id', 'quiz_id');
    }

    public function referrer()
    {
        // referral_user_id foreign key hai jo us user ki ID point karta hai jisne refer kiya
        return $this->belongsTo(User::class, 'referral_user_id');
    }

    public function referralsAllowed()
    {
        return $this->hasMany(User::class, 'referral_user_id', 'id')
            ->where('referral_code_to', $this->referral_code);
    }

    public function practiceQuizUsers()
    {
        return $this->hasMany(PracticeQuizUser::class, 'user_id', 'id');
    }


    public function leaderboard()
    {
        return $this->hasOne(Leaderboard::class, 'user_id')
            ->where('tenant_id', getSuperAdminTenantId())
            ->where('yearly_session', now()->year)
            ->where('status', 'active');
    }
}
