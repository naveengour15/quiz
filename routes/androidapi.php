<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\androidapi\AuthController as AndroidAuthController;
use App\Http\Controllers\androidapi\HomeController as AndroidHomeController;
use App\Http\Controllers\androidapi\AllQuizListController as AndroidAllQuizListController;
use App\Http\Controllers\androidapi\LeaderboardController as AndroidLeaderboardController;
use App\Http\Controllers\androidapi\ProfileController as AndroidProfileController;
use App\Http\Controllers\androidapi\QuizesController as AndroidQuizesController;
use App\Http\Controllers\androidapi\WalletController as AndroidWalletController;
use App\Http\Controllers\androidapi\ReferralController as AndroidReferralController;
use App\Http\Controllers\androidapi\HistoryController as AndroidHistoryController;
use App\Http\Controllers\androidapi\SurveyController as AndroidSurveyController;
use App\Http\Controllers\androidapi\PracticeQuizController as AndroidPracticeQuizController;
use App\Http\Controllers\androidapi\FeedbackController as AndroidFeedbackController;

Route::prefix('androidapi')->name('androidapi.')->group(function () {

    // 🟢 Public Auth & Onboarding APIs (No auth token required)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/send-otp', [AndroidAuthController::class, 'sendOtp'])->name('send-otp');
        Route::post('/verify-otp', [AndroidAuthController::class, 'verifyOtp'])->name('verify-otp');
        Route::post('/resend-otp', [AndroidAuthController::class, 'resendOtp'])->name('resend-otp');
        Route::post('/signup', [AndroidAuthController::class, 'signup'])->name('signup');
    });

    // 🟢 Public CMS Pages (Privacy Policy, Terms & Conditions, Delete Account, etc.)
    Route::get('cms/{slug}', [\App\Http\Controllers\Api\CmsController::class, 'pageBySlug'])->name('cms.page');

    // 🟢 Public App Health & Update APIs
    Route::get('ping', fn() => response()->json(['status' => 'ok', 'timestamp' => time()]))->name('ping');
    Route::get('check-update', [AndroidHomeController::class, 'checkAppUpdate'])->name('check-update');
    Route::post('device-token/register', [\App\Http\Controllers\Api\DeviceTokenController::class, 'register'])->name('device-token.register.public');

    // 🔒 ALL PROTECTED DATA & ACTION APIS (AUTH TOKEN REQUIRED)
    Route::middleware('auth.token')->group(function () {

        // Auth User Operations
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/complete-profile', [AndroidAuthController::class, 'completeProfile'])->name('complete-profile');
            Route::get('/profile', [AndroidAuthController::class, 'getProfile'])->name('profile');
            Route::post('/logout', [AndroidAuthController::class, 'logout'])->name('logout');
        });

        /*
        |--------------------------------------------------------------------------
        | Direct aliases used by the fsrapp browser views
        |--------------------------------------------------------------------------
        */
        Route::prefix('fsrapp')->group(function () {
            Route::get('home/last-quiz-status', [AndroidHomeController::class, 'lastQuizStatus']);
            Route::post('announcement/{id}/close', [AndroidHomeController::class, 'closeAnnouncement']);
            Route::post('app-updation/{id}/close', [AndroidHomeController::class, 'closeAppUpdation']);
            Route::get('quiz/get-winners/{id}', [AndroidQuizesController::class, 'winners']);
            Route::get('quizes/api/questions/{id}', [AndroidQuizesController::class, 'getQuestions']);
            Route::get('quizes/api/status/{id}', [AndroidQuizesController::class, 'status']);
            Route::post('quizes/api/submit-answer/{id}', [AndroidQuizesController::class, 'submitAnswer']);
            Route::get('quizes/api/sounds', [AndroidQuizesController::class, 'getSounds']);
            Route::get('quizes/api/answer-sheet/{id}', [AndroidQuizesController::class, 'getAnswerSheet']);
            Route::post('quizes/api/reconcile-answers/{id}', [AndroidQuizesController::class, 'reconcileAnswers']);
            Route::get('quizes/api/leaderboard/{id}', [AndroidQuizesController::class, 'getQuizLeaderboard']);
            Route::post('quizes/api/use-lifeline/{id}', [AndroidQuizesController::class, 'useLifeline']);
            Route::match(['get', 'post'], 'quizes/api/clear-session/{id}', [AndroidQuizesController::class, 'clearSession']);
            Route::match(['get', 'post'], 'quizes/api/process-result/{id}', [AndroidQuizesController::class, 'processResult']);
            Route::get('practice-quiz/{id}/start', [AndroidPracticeQuizController::class, 'start']);
            Route::post('practice-quiz/get-question/{id}', [AndroidPracticeQuizController::class, 'question']);
            Route::post('practice-quiz/submit-answer/{id}', [AndroidPracticeQuizController::class, 'submit']);
            Route::post('practice-quiz/use-lifeline/{id}', [AndroidPracticeQuizController::class, 'lifeline']);
            Route::get('practice-quiz/my-result/{id}', [AndroidPracticeQuizController::class, 'result']);
            Route::get('practice-quiz/answer-sheet/{id}', [AndroidPracticeQuizController::class, 'answerSheet']);
        });

        Route::get('leaderboard', [AndroidLeaderboardController::class, 'index']);
        Route::get('leaderboard/my-history', [AndroidLeaderboardController::class, 'myLeaderboardHistory']);
        Route::get('my-leaderboard-history', [AndroidLeaderboardController::class, 'myLeaderboardHistory']);
        Route::get('history', [AndroidHistoryController::class, 'getHistory']);
        Route::get('history/quiz/{id}/leaderboard', [AndroidHistoryController::class, 'getQuizLeaderboard']);
        Route::get('referral', [AndroidReferralController::class, 'getReferralData']);
        Route::get('referrals', [AndroidReferralController::class, 'getReferralData']);
        Route::get('survey', [AndroidSurveyController::class, 'getSurveys']);
        Route::get('survey/{id}/play', [AndroidSurveyController::class, 'playSurvey']);
        Route::post('survey/{id}/submit', [AndroidSurveyController::class, 'submitSurvey']);
        Route::get('wallet/options', [AndroidWalletController::class, 'getRedeemOptions']);
        Route::get('redeem/options', [AndroidWalletController::class, 'getRedeemOptions']);

        Route::get('categories', [AndroidHomeController::class, 'categories']);
        Route::get('home', [AndroidHomeController::class, 'index']);
        Route::get('home/banners', [AndroidHomeController::class, 'banners']);
        Route::match(['get', 'post'], 'banner/click/{banner}/{type}', [AndroidHomeController::class, 'bannerClick']);
        Route::match(['get', 'post'], 'banner/view/{banner}/{type}', [AndroidHomeController::class, 'bannerView']);
        Route::get('user/dashboard', [AndroidHomeController::class, 'dashboard']);
        Route::get('quiz/list', [AndroidAllQuizListController::class, 'list']);
        Route::get('quiz/upcoming', [AndroidAllQuizListController::class, 'upcoming']);
        Route::get('quiz/practice', [AndroidAllQuizListController::class, 'practice']);

        Route::get('sounds', [AndroidQuizesController::class, 'getSounds']);
        Route::get('quizes/api/sounds', [AndroidQuizesController::class, 'getSounds']);
        Route::get('quizes/api/questions/{id}', [AndroidQuizesController::class, 'getQuestions']);
        Route::get('quizes/api/status/{id}', [AndroidQuizesController::class, 'status']);
        Route::post('quizes/api/submit-answer/{id}', [AndroidQuizesController::class, 'submitAnswer']);

        Route::post('device-token/register', [\App\Http\Controllers\Api\DeviceTokenController::class, 'register']);
        Route::post('device-token/deactivate', [\App\Http\Controllers\Api\DeviceTokenController::class, 'deactivate']);
        Route::get('quizes/api/answer-sheet/{id}', [AndroidQuizesController::class, 'getAnswerSheet']);
        Route::get('quizes/api/leaderboard/{id}', [AndroidQuizesController::class, 'getQuizLeaderboard']);
        Route::post('quizes/api/use-lifeline/{id}', [AndroidQuizesController::class, 'useLifeline']);
        Route::match(['get', 'post'], 'quizes/api/clear-session/{id}', [AndroidQuizesController::class, 'clearSession']);
        Route::match(['get', 'post'], 'quizes/api/reconcile-answers/{id}', [AndroidQuizesController::class, 'reconcileAnswers']);
        Route::match(['get', 'post'], 'quizes/api/process-result/{id}', [AndroidQuizesController::class, 'processResult']);

        Route::get('quiz/next-quiz', [AndroidQuizesController::class, 'nextQuiz']);
        Route::get('quiz/{id}', [AndroidQuizesController::class, 'getDetails']);
        Route::post('quiz/{id}/join', [AndroidQuizesController::class, 'joinQuiz']);
        Route::post('quiz/{id}/notify', [AndroidQuizesController::class, 'toggleNotification']);
        Route::get('quiz/{id}/start', [AndroidQuizesController::class, 'start']);
        Route::post('quiz/{id}/get-question', [AndroidQuizesController::class, 'getQuestion']);
        Route::post('quiz/{id}/get-question-options', [AndroidQuizesController::class, 'getQuestionOptions']);
        Route::post('quiz/{id}/submit-answer', [AndroidQuizesController::class, 'submitAnswer']);
        Route::get('quiz/{id}/winners', [AndroidQuizesController::class, 'winners']);
        Route::post('quiz/{id}/leave', [AndroidQuizesController::class, 'leave']);
        Route::get('quiz/{id}/my-result', [AndroidQuizesController::class, 'myResult']);
        Route::get('quiz/{id}/answer-sheet', [AndroidQuizesController::class, 'getAnswerSheet']);
        Route::post('quiz/{id}/reconcile-answers', [AndroidQuizesController::class, 'reconcileAnswers']);
        Route::get('quiz/{id}/status', [AndroidQuizesController::class, 'status']);
        Route::post('quiz/{id}/use-lifeline', [AndroidQuizesController::class, 'useLifeline']);
        
        Route::prefix('practice-quiz')->group(function () {
            Route::get('list', [AndroidPracticeQuizController::class, 'list']);
            Route::get('{id}/detail', [AndroidPracticeQuizController::class, 'detail']);
            Route::match(['get', 'post'], '{id}/start', [AndroidPracticeQuizController::class, 'start']);
            Route::post('{id}/get-question', [AndroidPracticeQuizController::class, 'question']);
            Route::post('{id}/submit-answer', [AndroidPracticeQuizController::class, 'submit']);
            Route::post('{id}/use-lifeline', [AndroidPracticeQuizController::class, 'lifeline']);
            Route::get('{id}/my-result', [AndroidPracticeQuizController::class, 'result']);
            Route::get('{id}/answer-sheet', [AndroidPracticeQuizController::class, 'answerSheet']);
        });

        Route::get('user/wallet', [AndroidWalletController::class, 'balance']);
        Route::get('user/wallet-history', [AndroidWalletController::class, 'history']);
        Route::get('user/rewards', [AndroidWalletController::class, 'rewards']);
        Route::get('user/redemptions', [AndroidWalletController::class, 'redemptions']);
        Route::post('user/redeem', [AndroidWalletController::class, 'redeemReward']);
        Route::get('user/referral-code', [AndroidReferralController::class, 'code']);
        Route::get('user/referrals', [AndroidReferralController::class, 'referrals']);
        Route::post('user/apply-referral', [AndroidReferralController::class, 'apply']);
        Route::get('user/profile', [AndroidProfileController::class, 'getProfile']);
        Route::post('user/update-profile', [AndroidProfileController::class, 'updateProfile']);
        Route::delete('user/delete-account', [AndroidProfileController::class, 'deleteAccount']);
        Route::get('user/quiz-history', [AndroidHistoryController::class, 'getHistory']);
        Route::get('user/complete-profile', [AndroidAuthController::class, 'getProfile']);
        Route::get('user/feedback', [AndroidFeedbackController::class, 'getFeedbackHistory']);
        Route::post('user/feedback', [AndroidFeedbackController::class, 'submitFeedback']);

        // 🟢 Module 2: Home & Dashboard APIs
        Route::get('/home', [AndroidHomeController::class, 'index'])->name('home');
        Route::get('/home/last-quiz-status', [AndroidHomeController::class, 'lastQuizStatus'])->name('home.last-quiz-status');
        Route::post('/home/quiz-times', [AndroidHomeController::class, 'getQuizTimes'])->name('home.quiz-times');
        Route::post('/home/whatsapp-subscribe', [AndroidHomeController::class, 'subscribeWhatsappChannel'])->name('home.whatsapp-subscribe');
        Route::post('/home/close-announcement/{id}', [AndroidHomeController::class, 'closeAnnouncement'])->name('home.close-announcement');
        Route::post('/home/close-app-updation/{id}', [AndroidHomeController::class, 'closeAppUpdation'])->name('home.close-app-updation');

        // 🟢 Module 3: Quizzes Catalog & Search APIs
        Route::get('/quizzes', [AndroidAllQuizListController::class, 'index'])->name('quizzes.index');

        // 🟢 Module 4: Global Leaderboard APIs
        Route::get('/leaderboard', [AndroidLeaderboardController::class, 'index'])->name('leaderboard.index');
        Route::get('/history', [AndroidHistoryController::class, 'getHistory'])->name('history.alias');
        Route::get('/history/quiz/{id}/leaderboard', [AndroidHistoryController::class, 'getQuizLeaderboard'])->name('history.quiz-leaderboard.alias');
        Route::get('/survey', [AndroidSurveyController::class, 'getSurveys'])->name('survey.alias');
        Route::get('/survey/{id}/play', [AndroidSurveyController::class, 'playSurvey'])->name('survey.play.alias');
        Route::post('/survey/{id}/submit', [AndroidSurveyController::class, 'submitSurvey'])->name('survey.submit.alias');

        // 🟢 Module 5: User Profile & Settings APIs
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [AndroidProfileController::class, 'getProfile'])->name('index');
            Route::post('/update', [AndroidProfileController::class, 'updateProfile'])->name('update');
            Route::post('/update-language', [AndroidProfileController::class, 'updateLanguage'])->name('update-language');
            Route::get('/states', [AndroidProfileController::class, 'getStates'])->name('states');
            Route::get('/cities', [AndroidProfileController::class, 'getCities'])->name('cities');
            Route::get('/options', [AndroidProfileController::class, 'getProfileOptions'])->name('options');
        });

        // 🟢 Module 6: Quiz Gameplay & KBC Flow APIs
        Route::prefix('quizzes')->name('quizzes.')->group(function () {
            Route::get('/next-quiz', [AndroidQuizesController::class, 'nextQuiz'])->name('next-quiz');
            Route::get('/sounds', [AndroidQuizesController::class, 'getSounds'])->name('sounds');
            Route::get('/{id}/details', [AndroidQuizesController::class, 'getDetails'])->name('details');
            Route::post('/{id}/join', [AndroidQuizesController::class, 'joinQuiz'])->name('join');
            Route::post('/{id}/notify', [AndroidQuizesController::class, 'toggleNotification'])->name('notify');
            Route::post('/{id}/use-lifeline', [AndroidQuizesController::class, 'useLifeline'])->name('use-lifeline');
            Route::get('/{id}/waiting', [AndroidQuizesController::class, 'getWaitingStatus'])->name('waiting');
            Route::get('/{id}/questions', [AndroidQuizesController::class, 'getQuestions'])->name('questions');
            Route::post('/{id}/submit-answer', [AndroidQuizesController::class, 'submitAnswer'])->name('submit-answer');
            Route::get('/{id}/leaderboard', [AndroidQuizesController::class, 'getQuizLeaderboard'])->name('leaderboard');
            Route::get('/{id}/answer-sheet', [AndroidQuizesController::class, 'getAnswerSheet'])->name('answer-sheet');
            Route::match(['get', 'post'], '/{id}/process-result', [AndroidQuizesController::class, 'processResult'])->name('process-result');
        });

        // 🟢 Module 7: Wallet & Recharge / Redeem APIs
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [AndroidWalletController::class, 'getWallet'])->name('index');
            Route::get('/redeem-data', [AndroidWalletController::class, 'getRedeemData'])->name('redeem-data');
            Route::post('/submit-redeem', [AndroidWalletController::class, 'submitRedeem'])->name('submit-redeem');
            Route::post('/request-voucher', [AndroidWalletController::class, 'requestVoucher'])->name('request-voucher');
        });

        Route::get('/wallet', [AndroidWalletController::class, 'getWallet']);
        Route::get('/wallet/redeem-data', [AndroidWalletController::class, 'getRedeemData']);
        Route::post('/wallet/submit-redeem', [AndroidWalletController::class, 'submitRedeem']);
        Route::post('/wallet/request-voucher', [AndroidWalletController::class, 'requestVoucher']);
        Route::post('/vouchers/request', [AndroidWalletController::class, 'requestVoucher']);

        // 🟢 Module 8: Refer & Earn APIs
        Route::get('/referral', [AndroidReferralController::class, 'getReferralData'])->name('referral.index');

        // 🟢 Module 9: Quiz Play History APIs
        Route::prefix('history')->name('history.')->group(function () {
            Route::get('/', [AndroidHistoryController::class, 'getHistory'])->name('index');
            Route::get('/quiz/{id}/leaderboard', [AndroidHistoryController::class, 'getQuizLeaderboard'])->name('quiz-leaderboard');
        });

        // 🟢 Module 10: Survey & Offerwall APIs
        Route::prefix('survey')->name('survey.')->group(function () {
            Route::get('/', [AndroidSurveyController::class, 'getSurveys'])->name('index');
            Route::get('/{id}/play', [AndroidSurveyController::class, 'playSurvey'])->name('play');
            Route::post('/{id}/submit', [AndroidSurveyController::class, 'submitSurvey'])->name('submit');
        });

    });

});
