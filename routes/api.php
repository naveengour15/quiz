<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthUserController;

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizAdminController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\SurveyController;

use App\Http\Controllers\Api\RedeemRequestController;
use App\Http\Controllers\Api\ReferralCodeController;

use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ApiQuizUserController;


Route::middleware('static.token')->group(function () {
    Route::get('/users', [ApiQuizUserController::class, 'users']);
    Route::get('/quiz/latest-upcoming', [ApiQuizUserController::class, 'latestUpcomingQuiz']);
    Route::get('/latest-quiz', [ApiQuizUserController::class, 'latestUpcomingQuiz']);
});
Route::post('/referral-code/generate', [ReferralCodeController::class, 'generate']);

// =================================================================
// Device Tokens / Firebase Push Notification Routes
// =================================================================
Route::post('/device-token/register', [\App\Http\Controllers\Api\DeviceTokenController::class, 'register']);
Route::post('/device-token/deactivate', [\App\Http\Controllers\Api\DeviceTokenController::class, 'deactivate']);
Route::match(['get', 'post'], '/send-firebase-notifications/{quizId?}', function ($quizId = null) {
    if ($quizId) {
        Artisan::call('quiz:send-firebase-notifications', ['quiz_id' => (int) $quizId]);
    } else {
        Artisan::call('quiz:send-firebase-notifications');
    }
    return response()->json([
        'success' => true,
        'message' => Artisan::output(),
    ]);
});



// =================================================================
// 1. Cron Job
// =================================================================
Route::get('/quiz-result', function () {
    Artisan::call('quiz:result');

    return response()->json([
        'success' => true,
        'message' => Artisan::output(),
    ]);
});

Route::get('/referral-process-rewards', function () {
    Artisan::call('referral:process-rewards');

    return response()->json([
        'success' => true,
        'message' => Artisan::output(),
    ]);
});

Route::match(['get', 'post'], '/proquiz-leaderboard/{quizId?}', function ($quizId = null) {
    if ($quizId) {
        \App\Jobs\ProcessQuizResultJob::dispatchSync((int) $quizId);
        $message = "Quiz result job processed synchronously for quiz #{$quizId}";
    } else {
        Artisan::call('quiz:leaderboard');
        $message = Artisan::output();
    }

    return response()->json([
        'success' => true,
        'message' => $message,
    ]);
});
Route::match(['get', 'post'], '/quiz-leaderboard/{quizId?}', function ($quizId = null) {
    if ($quizId) {
        \App\Jobs\ProcessQuizResultJob::dispatchSync((int) $quizId);
        $message = "Quiz result job processed synchronously for quiz #{$quizId}";
    } else {
        Artisan::call('quiz:leaderboard');
        $message = Artisan::output();
    }

    return response()->json([
        'success' => true,
        'message' => $message,
    ]);
});

Route::match(['get', 'post'], '/leaderboard-user-update/{tenantId?}', function ($tenantId = null) {
    if ($tenantId) {
        Artisan::call('leaderboard:user-update', ['tenant_id' => (int) $tenantId]);
    } else {
        Artisan::call('leaderboard:user-update');
    }
    return response()->json([
        'success' => true,
        'message' => Artisan::output(),
    ]);
});

// =================================================================
// 1. Log Out
// =================================================================

// Route::post('/logout', [AuthUserController::class, 'logout']);
// =================================================================
// 1. AUTH (Mobile + OTP)
// =================================================================
// Route::prefix('auth')->group(function () {
//     Route::post('send-otp', [AuthController::class, 'sendOtp']);
//     Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
//     Route::post('resend-otp', [AuthController::class, 'resendOtp']);
//     Route::post('signup', [AuthController::class, 'signup']);

//     Route::middleware('auth.token')->post('logout', [AuthController::class, 'logout']);
// });


// =================================================================
// 2. MASTER / LOOKUP (public)
// =================================================================
Route::get('countries', [LookupController::class, 'countries']);
Route::get('states', [LookupController::class, 'states']);
Route::get('cities', [LookupController::class, 'cities']);
Route::get('categories', [LookupController::class, 'categories']);
Route::get('categories/{category_id}/subcategories', [LookupController::class, 'subcategories']);
Route::get('subcategories', [LookupController::class, 'subcategories']);
Route::get('subcategories/{category_id}', [LookupController::class, 'subcategories']);
Route::get('levels', [LookupController::class, 'levels']);
Route::get('quiz-types', [LookupController::class, 'quizTypes']);

// =================================================================
// 3. USER PROFILE (auth required)
// =================================================================
Route::middleware('auth.token')->prefix('user')->group(function () {
    
    Route::get('dashboard', [DashboardController::class, 'dashboard']);
    Route::get('profile', [ProfileController::class, 'getProfile']);
    Route::post('complete-profile', [ProfileController::class, 'updateProfile']);
    Route::post('update-profile', [ProfileController::class, 'updateProfile']);
    Route::post('update-alt-mobile', [ProfileController::class, 'updateAlternativeMobile']);
  
    Route::get('send-otp-for-verify-email', [ProfileController::class, 'sendOtpVerifyEmail']);
    Route::post('verify-email', [ProfileController::class, 'verifyEmail']);
    Route::delete('delete-account', [ProfileController::class, 'deleteAccount']);

    Route::get('quiz-history', [QuizController::class, 'myHistory']);
    Route::get('survey-history', [SurveyController::class, 'myHistory']);

    Route::get('rewards', [RewardController::class, 'catalog']);
    Route::post('redeem', [RewardController::class, 'redeem']);
    Route::get('redemptions', [RewardController::class, 'myRedemptions']);
    Route::get('wallet', [RewardController::class, 'walletBalance']);
    Route::get('wallet-history', [RewardController::class, 'walletHistory']);

    Route::get('referral-code', [ReferralController::class, 'myCode']);
    Route::post('apply-referral', [ReferralController::class, 'applyCode']);
    Route::get('referrals', [ReferralController::class, 'myReferrals']);

    Route::get('subscription', [PlanController::class, 'mySubscription']);
    Route::get('billing-history', [PlanController::class, 'billingHistory']);
    Route::post('subscription/cancel', [PlanController::class, 'cancelSubscription']);

    Route::prefix('quiz')->group(function () {
        Route::post('create', [QuizAdminController::class, 'create']);
        Route::post('update/{quiz_id}', [QuizAdminController::class, 'update']);
        Route::delete('delete/{quiz_id}', [QuizAdminController::class, 'delete']);
        Route::get('list', [QuizAdminController::class, 'list']);
        Route::get('{quiz_id}', [QuizAdminController::class, 'detail']);
        Route::post('{quiz_id}/questions', [QuizAdminController::class, 'setQuestions']);
        Route::delete('{quiz_id}/questions/{question_id}', [QuizAdminController::class, 'removeQuestion']);
        Route::post('{quiz_id}/rank-rewards', [QuizAdminController::class, 'setRankRewards']);
        Route::post('{quiz_id}/filters', [QuizAdminController::class, 'setFilters']);
        Route::post('{quiz_id}/publish', [QuizAdminController::class, 'publish']);
        Route::post('{quiz_id}/approve', [QuizAdminController::class, 'approve']);
        Route::get('{quiz_id}/status', [QuizAdminController::class, 'stats']);
    });

    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

});

// =================================================================
// 4. QUIZ — USER SIDE (auth required)
// =================================================================
// Route::middleware('auth.token')->prefix('quiz')->group(function () {
//     Route::get('list', [QuizController::class, 'list']);
//     Route::get('upcoming', [QuizController::class, 'upcoming']);
//     Route::get('practice', [QuizController::class, 'practice']);
//     Route::get('{quiz_id}', [QuizController::class, 'detail']);
//     Route::post('{quiz_id}/join', [QuizController::class, 'join']);
//     Route::get('{quiz_id}/start', [QuizController::class, 'start']);
//     Route::post('{quiz_id}/get-question', [QuizController::class, 'getQuestion']);
//     Route::post('{quiz_id}/get-question-options', [QuizController::class, 'getQuestionOptions']);
//     Route::post('{quiz_id}/submit-answer', [QuizController::class, 'submitAnswer']);
//     Route::post('{quiz_id}/submit', [QuizController::class, 'submitBulk']);
//     Route::get('{quiz_id}/my-result', [QuizController::class, 'myResult']);
//     Route::get('{quiz_id}/winners', [QuizController::class, 'winners']);
//     Route::post('{quiz_id}/leave', [QuizController::class, 'leave']);
//     Route::get('{quiz_id}/answer-sheet', [QuizController::class, 'answerSheet']);
//     Route::post('{quiz_id}/use-lifeline', [QuizController::class, 'useLifeline']);
//     Route::post('{quiz_id}/process-result', [QuizController::class, 'processResult']);
// });

// =================================================================
// PRACTICE QUIZ — USER SIDE (auth required)
// =================================================================
Route::middleware('auth.token')->prefix('practice-quiz')->group(function () {
    Route::get('list', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'list']);
    Route::get('{quiz_id}/detail', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'detail']);
    Route::match(['get', 'post'], '{quiz_id}/start', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'start']);
    Route::post('{quiz_id}/get-question', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'getQuestion']);
    Route::post('{quiz_id}/use-lifeline', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'useLifeline']);
    Route::post('{quiz_id}/submit-answer', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'submitAnswer']);
    Route::get('{quiz_id}/my-result', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'myResult']);
    Route::get('{quiz_id}/answer-sheet', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'answerSheet']);
});

Route::middleware('auth.token')->group(function () {
    Route::get('/home/banners', function() {
        $banners = \App\Models\Banner::where('status', 'active')->latest()->get();
        if ($banners->isEmpty()) {
            $banners = \App\Models\Banner::latest()->get();
        }
        return response()->json(['success' => true, 'data' => $banners]);
    });
});

// =================================================================
// 5. SURVEY — USER SIDE (auth required)
// =================================================================
Route::middleware('auth.token')->prefix('survey')->group(function () {
    Route::get('list', [SurveyController::class, 'surveyList']);
    Route::get('{survey_id}/start', [SurveyController::class, 'start']);
    Route::post('{survey_id}/submit-answer', [SurveyController::class, 'submitAnswer']);
    Route::post('{survey_id}/submit', [SurveyController::class, 'submitBulk']);
    Route::get('{survey_id}/my-result', [SurveyController::class, 'myResult']);
});

// =================================================================
// 6. PLANS (public list) + PAYMENT
// =================================================================
Route::get('plans', [PlanController::class, 'list']);
Route::middleware('auth.token')->post('plan/apply-coupon', [PlanController::class, 'applyCoupon']);
Route::middleware('auth.token')->post('plan/{plan_id}/pay', [PlanController::class, 'initiatePayment']);
Route::post('plan/payment-callback', [PlanController::class, 'paymentCallback']); // gateway webhook, no user auth

// =================================================================
// 7. WEB ENQUIRY (public multi-step form)
// =================================================================
Route::prefix('enquiry')->group(function () {
    Route::post('submit-step', [CmsController::class, 'submitEnquiryStep']);
    Route::post('verify-otp', [CmsController::class, 'verifyEnquiryOtp']);
});

// =================================================================
// 8. CMS (public)
// =================================================================
Route::get('cms/{slug}', [CmsController::class, 'pageBySlug']);

// =================================================================
// 9. REPORT CONTENT (auth required)
// =================================================================
Route::middleware('auth.token')->post('report', [CmsController::class, 'reportContent']);

// =================================================================
// 10. ADMIN — Auth
// =================================================================
Route::post('admin/auth/login', [AdminController::class, 'login']);

// =================================================================
// 11. ADMIN — protected routes (admin.token middleware)
// =================================================================
Route::middleware('admin.token')->prefix('admin')->group(function () {

    // Quiz management
    Route::prefix('quiz')->group(function () {
        Route::post('create', [QuizAdminController::class, 'create']);
        Route::post('update/{quiz_id}', [QuizAdminController::class, 'update']);
        Route::delete('delete/{quiz_id}', [QuizAdminController::class, 'delete']);
        Route::get('list', [QuizAdminController::class, 'list']);
        Route::get('{quiz_id}', [QuizAdminController::class, 'detail']);
        Route::post('{quiz_id}/questions', [QuizAdminController::class, 'setQuestions']);
        Route::delete('{quiz_id}/questions/{question_id}', [QuizAdminController::class, 'removeQuestion']);
        Route::post('{quiz_id}/rank-rewards', [QuizAdminController::class, 'setRankRewards']);
        Route::post('{quiz_id}/filters', [QuizAdminController::class, 'setFilters']);
        Route::post('{quiz_id}/publish', [QuizAdminController::class, 'publish']);
        Route::post('{quiz_id}/approve', [QuizAdminController::class, 'approve']);
        Route::get('{quiz_id}/status', [QuizAdminController::class, 'stats']);
    });

    // Question bank
    Route::prefix('question')->group(function () {
        Route::get('list', [QuestionController::class, 'list']);
        Route::post('create', [QuestionController::class, 'create']);
        Route::post('update/{id}', [QuestionController::class, 'update']);
        Route::delete('delete/{id}', [QuestionController::class, 'delete']);
        Route::post('{id}/moderate', [QuestionController::class, 'moderate']);
    });

    // Survey management
    Route::prefix('survey')->group(function () {
        Route::post('question/create', [SurveyController::class, 'createQuestion']);
        Route::post('question/update/{id}', [SurveyController::class, 'updateQuestion']);
        Route::delete('question/delete/{id}', [SurveyController::class, 'deleteQuestion']);
        Route::get('{survey_id}/questions', [SurveyController::class, 'questionsList']);
        Route::post('{survey_id}/reward-setup', [SurveyController::class, 'setRewardRule']);
    });

    // Redemption approval
    Route::post('redemption/{id}/action', [RewardController::class, 'actionOnRedemption']);

    // Referral settlement
    Route::post('referral/{id}/settle', [ReferralController::class, 'settle']);

    // Coupons
    Route::post('coupon/create', [PlanController::class, 'createCoupon']);

    // Admin/Role management
    Route::get('users/list', [AdminController::class, 'list']);
    Route::post('users/create', [AdminController::class, 'create']);
    Route::get('roles', [AdminController::class, 'rolesList']);
    Route::post('roles/create', [AdminController::class, 'createRole']);
    Route::get('audit-logs', [AdminController::class, 'auditLogs']);

    // CMS / Campaign / Enquiry / Moderation
    Route::post('cms/create', [CmsController::class, 'createOrUpdatePage']);
    Route::post('campaign/create', [CmsController::class, 'createCampaign']);
    Route::post('campaign/{id}/send', [CmsController::class, 'sendCampaign']);
    Route::get('enquiries', [CmsController::class, 'enquiryList']);
    Route::get('moderation-reports', [CmsController::class, 'moderationList']);
    Route::post('moderation-reports/{id}/action', [CmsController::class, 'moderationAction']);
    Route::get('whatsapp-templates', [CmsController::class, 'whatsappTemplates']);
    Route::post('whatsapp-templates', [CmsController::class, 'saveWhatsappTemplate']);
    Route::get('settings', [CmsController::class, 'settingsList']);
    Route::post('settings', [CmsController::class, 'saveSetting']);
});

// =================================================================
// 12. SUPER ADMIN — Tenant management
// =================================================================
Route::middleware('admin.token')->prefix('superadmin')->group(function () {
    Route::get('tenants', [AdminController::class, 'tenantsList']);
    Route::post('tenants/{id}/status', [AdminController::class, 'updateTenantStatus']);
});


// 13. User redeem-requests
Route::middleware('auth.token')->prefix('redeem-requests')->group(function () {

    Route::get('/list', [RedeemRequestController::class, 'index']);
    
    Route::post('/create', [RedeemRequestController::class, 'store']);
});