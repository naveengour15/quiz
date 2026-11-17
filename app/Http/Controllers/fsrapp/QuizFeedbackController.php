<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizFeedbackController extends Controller
{
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Please login to submit feedback.'
                ], 401);
            }

            $request->validate([
                'subject' => 'required|string|max:191',
                'message' => 'required|string|max:1000',
            ]);

            // Data ko quiz_user_feedback table mein insert karein
            DB::table('quiz_user_feedback')->insert([
                'tenant_id'    => session('tenant_id', 1), // Ya $user->tenant_id
                'user_id'      => $user->id,
                'subject'      => $request->subject,
                'message'      => $request->message,
                'status'       => 'pending', // Default status
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your feedback has been submitted successfully.'
            ]);

        } catch (\Throwable $e) {
            Log::error('Quiz Feedback Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    public function historyPage()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('fsrapp.login');
        }

        // Latest feedbacks upar dikhane ke liye 'desc' use kiya
        $feedbacks = DB::table('quiz_user_feedback')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50) 
            ->get();

        return view('fsrapp.feedback.history', compact('feedbacks'));
    }
}