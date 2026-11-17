<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QuizSetting extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('global_quiz_sound_urls');
            \Illuminate\Support\Facades\Cache::forget('global_quiz_sound_settings');
            \Illuminate\Support\Facades\Cache::forget('fsr_profile_update_point_1');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('global_quiz_sound_urls');
            \Illuminate\Support\Facades\Cache::forget('global_quiz_sound_settings');
            \Illuminate\Support\Facades\Cache::forget('fsr_profile_update_point_1');
        });
    }

    protected $fillable = [
        'superadmin_quiz_limit',
        'superadmin_que_limit',
        'superadmin_referral_limit',
        'user_que_limit',
        'user_quiz_limit',
        'user_referral_limit',
        'retailer_que_limit',
        'retailer_quiz_limit',
        'retailer_referral_limit',
        'background_sound',
        'question_appear_sound',
        'start_quiz_sound',
        'answer_appear_sound',
        'quiz_end_sound',
        'answer_sheet_appear_sound',
        'leaderboard_appear_sound',
        'counter_sound',
        'say_ans_appear_sound',
        'one_min_before',
        'profile_update_point',
        'redeem_request_start_time',
        'redeem_request_end_time',
        'base_url',       
        'ans_select_sound',     
        'redeem_request_days',     
        'reminder_intervals',     
    ];

    public function getSoundUrls(): array
    {
        $disk = 's3';
        $getUrl = function ($path) use ($disk) {
            if (empty($path)) return null;
            if (filter_var($path, FILTER_VALIDATE_URL)) return $path;
            try {
                return Storage::disk($disk)->url($path);
            } catch (\Throwable $e) {
                try {
                    return Storage::url($path);
                } catch (\Throwable $e2) {
                    return asset('storage/' . $path);
                }
            }
        };

        return [
            'background_sound'          => $getUrl($this->background_sound),
            'one_min_before'            => $getUrl($this->one_min_before),
            'question_appear_sound'     => $getUrl($this->question_appear_sound),
            'start_quiz_sound'          => $getUrl($this->start_quiz_sound),
            'say_ans_appear_sound'      => $getUrl($this->say_ans_appear_sound),
            'ans_select_sound'          => $getUrl($this->ans_select_sound ?: $this->say_ans_appear_sound),
            'ans_select'                => $getUrl($this->ans_select_sound ?: $this->say_ans_appear_sound),
            'answer_appear_sound'       => $getUrl($this->answer_appear_sound),
            'quiz_end_sound'            => $getUrl($this->quiz_end_sound),
            'answer_sheet_appear_sound' => $getUrl($this->answer_sheet_appear_sound),
            'leaderboard_appear_sound'  => $getUrl($this->leaderboard_appear_sound),
            'counter_sound'             => $getUrl($this->counter_sound),
        ];
    }

    public function getReminderIntervals(): array
    {
        $intervals = $this->reminder_intervals;
        if (!empty($intervals) && is_array($intervals)) {
            $has10s = false;
            foreach ($intervals as $s) {
                if ((int) ($s['value'] ?? 0) === 10 && in_array(strtolower((string) ($s['unit'] ?? '')), ['second', 'seconds', 'sec', 's'])) {
                    $has10s = true;
                    break;
                }
            }
            // if (!$has10s) {
            //     $intervals[] = ['value' => 10, 'unit' => 'second'];
            // }
            return $intervals;
        }

        return [
            ['value' => 15, 'unit' => 'minute'],
            ['value' => 10, 'unit' => 'minute'],
            ['value' => 5,  'unit' => 'minute'],
            ['value' => 3,  'unit' => 'minute'],
            ['value' => 1,  'unit' => 'minute'],
            ['value' => 30, 'unit' => 'second'],
            ['value' => 10, 'unit' => 'second'],
        ];
    }

    protected function casts(): array
    {
        return [
            'redeem_request_days' => 'array',
            'reminder_intervals'  => 'array',
        ];
    }
}