<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QuizSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QuizSettingController extends Controller
{
    public function index()
    {
        $setting = QuizSetting::first();
        return view('superadmin.quiz-settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'redeem_request_start_time' => 'required|date_format:H:i',
            'redeem_request_end_time'   => 'required|date_format:H:i|after:redeem_request_start_time',
            'profile_update_point'   => 'required|integer|min:0',
            'superadmin_que_limit'      => 'required|integer|min:0',
            'superadmin_quiz_limit'     => 'required|integer|min:0',
            'superadmin_referral_limit' => 'required|integer|min:0',
            'user_que_limit'            => 'nullable|integer|min:0',
            'user_quiz_limit'           => 'nullable|integer|min:0',
            'user_referral_limit'       => 'nullable|integer|min:0',
            'retailer_que_limit'        => 'nullable|integer|min:0',
            'retailer_quiz_limit'       => 'nullable|integer|min:0',
            'retailer_referral_limit'   => 'nullable|integer|min:0',
            'base_url'                  => 'nullable', // ✅ Naya Base URL Validation
            // ✅ NEW: Redeem Request Days Validation
                // ✅ NEW: Exact Database Structure Validation
            'redeem_request_days'       => 'nullable|array',
            'redeem_request_days.*.day' => 'required|string',
            'redeem_request_days.*.status' => 'required|in:on,off',
            'reminder_intervals'        => 'nullable|array',
            'reminder_intervals.*.value'=> 'nullable|numeric|min:1',
            'reminder_intervals.*.unit' => 'nullable|string|in:minute,second,min,sec',
        ]);

        if ($request->has('reminder_intervals')) {
            $rawIntervals = $request->input('reminder_intervals', []);
            $cleanIntervals = [];
            if (is_array($rawIntervals)) {
                foreach ($rawIntervals as $item) {
                    $val = isset($item['value']) ? (int) $item['value'] : 0;
                    $unit = isset($item['unit']) && in_array(strtolower($item['unit']), ['second', 'seconds', 'sec', 's']) ? 'second' : 'minute';
                    if ($val > 0) {
                        $cleanIntervals[] = [
                            'value' => $val,
                            'unit'  => $unit,
                        ];
                    }
                }
            }
            $data['reminder_intervals'] = $cleanIntervals;
        }

        $setting = QuizSetting::first();

        if (!$setting) {
            QuizSetting::create($data);
        } else {
            $setting->update($data);
        }

        AuditLog::log('Updated Quiz Settings', 'Settings');

        return back()->with('success', 'Quiz settings updated successfully.');
    }

    /**
     * 🔊 AJAX Sound Update Method (S3 with Safe Fallback)
     */
    public function updateSound(Request $request)
    {
        $request->validate([
            'field' => 'required|string|in:background_sound,one_min_before,question_appear_sound,start_quiz_sound,answer_appear_sound,quiz_end_sound,answer_sheet_appear_sound,leaderboard_appear_sound,counter_sound,say_ans_appear_sound,ans_select_sound',
            'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a|max:5120', // 5MB max
        ]);

        try {
            $setting = QuizSetting::first() ?? QuizSetting::create([]);
            $field = $request->field;
            $file = $request->file('audio_file');

            // ==========================================================
            // STORAGE DISK CONFIGURATION
            // Agar aapne abhi tak S3 configure NAHI kiya hai, toh ise wapas 'public' kar dein!
            // ==========================================================
            $disk = 's3'; // Testing ke liye: $disk = 'public';

            // 1. Purani file delete karo (SAFE CHECK ke sath)
            if (!empty($setting->{$field})) {
                try {
                    if (Storage::disk($disk)->exists($setting->{$field})) {
                        Storage::disk($disk)->delete($setting->{$field});
                        Log::info('Old sound file deleted', ['path' => $setting->{$field}, 'disk' => $disk]);
                    }
                } catch (\Exception $e) {
                    // Agar S3 connect nahi ho raha ya file local thi, toh ignore karo aur aage badho
                    Log::warning('Old file delete skip/failed (Safe): ' . $e->getMessage());
                }
            }

            // 2. Nayi file upload karo
            $fileName = $field . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            $uploaded = Storage::disk($disk)->putFileAs(
                'sounds',
                $file,
                $fileName,
                'public' // S3 ke liye 'public' visibility zaroori hai
            );

            if (!$uploaded) {
                throw new \Exception('Failed to upload file to storage. Check disk credentials.');
            }

            // 3. Database update
            $setting->update([$field => $uploaded]);
            \Illuminate\Support\Facades\Cache::forget('global_quiz_sound_urls');
            \Illuminate\Support\Facades\Cache::forget('global_quiz_sound_settings');

            // 4. New URL generate karo
            $newUrl = Storage::disk($disk)->url($uploaded);

            Log::info('Sound Upload Complete', [
                'field' => $field,
                'disk' => $disk,
                'path' => $uploaded,
                'url' => $newUrl,
            ]);

            AuditLog::log('Updated Sound Setting', 'Settings', $field);

            return response()->json([
                'success' => true,
                'message' => 'Sound updated successfully!',
                'url'     => $newUrl,
                'path'    => $uploaded,
            ]);

        } catch (\Throwable $e) {
            Log::error('Sound update failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sound: ' . $e->getMessage(),
            ], 500);
        }
    }
}