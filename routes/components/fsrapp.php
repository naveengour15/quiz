<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\fsrapp\AuthController;
use App\Http\Controllers\fsrapp\ProfileController;
use App\Http\Controllers\fsrapp\HomeController;
use App\Http\Controllers\fsrapp\QuizController;
use App\Http\Controllers\fsrapp\QuizesController;
use App\Http\Controllers\fsrapp\SurveyController;
use App\Http\Controllers\fsrapp\LeaderboardController;
use App\Http\Controllers\fsrapp\HistoryController;
use App\Http\Controllers\fsrapp\WalletController;
use App\Http\Controllers\fsrapp\ReferralController;
use App\Http\Controllers\fsrapp\AllQuizListController;
use App\Http\Controllers\fsrapp\QuizFeedbackController;
use App\Http\Controllers\fsrapp\FffQuizController;
use App\Http\Controllers\fsrapp\EGiftvoucherController;
use App\Services\QuizStartReminderService;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;

Route::prefix('fsrapp')->name('fsrapp.')->group(function () {
 
    Route::get('/send-whatsapp-notifications', function () {
        Artisan::call('quiz:send-whatsapp-notifications');

        return response()->json([
            'success' => true,
            'message' => Artisan::output(),
        ]);
    });

    Route::match(['get', 'post'], '/test/run-quiz-scheduler/{quizId?}', function ($quizId = null) {
        $service = app(QuizStartReminderService::class);

        try {
            $data = $service->run();
        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }

        return response()->json([
            'success' => true,
            'message' => $service->message($data),
            'data' => $data,
        ]);
    });

    Route::match(['get', 'post'], '/test/run-quiz-schedule/{quizId?}', function ($quizId = null) {
        return redirect()->to(url('/fsrapp/test/run-quiz-scheduler/' . ($quizId ?? '')));
    });

    // Route::get('/send-redeem-notifications', function () {
    //     Artisan::call('redeem:send-whatsapp-alerts');

    //     return response()->json([
    //         'success' => true,
    //         'message' => Artisan::output(),
    //     ]);
    // });
    Route::get('/send-redeem-notifications', function () {
        // 1. Pehle Redeem WhatsApp Alerts bhejein
        Artisan::call('redeem:send-whatsapp-alerts');
        $redeemOutput = Artisan::output(); 

        // 2. Phir Referral Rewards process karein
        Artisan::call('referral:process-rewards');
        $referralOutput = Artisan::output(); 

        // 3. Dono ka result alag-alag lines me return karein (JSON ke liye \n best hai)
        return response()->json([
            'success' => true,
            'message' => "✅ Dono tasks successfully complete ho gaye!\n\n" .
                        "📱 --- Redeem WhatsApp Alerts Output ---\n" .
                        $redeemOutput . "\n\n" .
                        "🤝 --- Referral Process Output ---\n" .
                        $referralOutput
        ]);
    });

    Route::get('/', function () {
        if (\Illuminate\Support\Facades\Auth::check() || (session('fsr_authenticated') && session('fsr_user_id'))) {
            return redirect()->route('fsrapp.home');
        }
        return redirect()->route('fsrapp.login');
    });

    Route::middleware(['fsr.locale', 'track.visitors'])->group(function () {
        // ── Public / Guest Routes ─────────────────────────────────
        Route::get('/login/{quiz_id?}', [AuthController::class, 'login'])->name('login');
        Route::match(['get', 'post'], '/send-otp', [AuthController::class, 'sendOtp'])->name('send-otp');

        Route::get('/otp', [AuthController::class, 'otp'])->name('otp');
        Route::match(['get', 'post'], '/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
        Route::match(['get', 'post'], '/resend-otp', [AuthController::class, 'resendOtp'])->name('resend-otp');

        Route::get('/complete-profile', [ProfileController::class, 'completeProfile'])->name('complete-profile');
        Route::post('/complete-profile', [ProfileController::class, 'saveProfile'])->name('save-profile');

        // Public Sound API Endpoints for login / pre-auth caching
        Route::get('/quizes/api/sounds', [QuizesController::class, 'getSounds'])->name('quizes.api.sounds');
        Route::get('/quiz/sound-settings', [QuizController::class, 'soundSettings'])->name('quiz.sound-settings');

        // CMS & Legal Pages
        Route::get('/privacy-policy', [ProfileController::class, 'privacyPolicy'])->name('privacy-policy');
        Route::get('/terms-conditions', [ProfileController::class, 'termsConditions'])->name('terms-conditions');

        // ── Authenticated Routes (Protected by fsr.auth) ─────────
        Route::middleware(['fsr.auth'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/home', [HomeController::class, 'index'])->name('home');
        Route::get('/how-fsrquiz-works', [HomeController::class, 'howFsrQuizWorks'])->name('how-fsrquiz-works');
        Route::get('/home/last-quiz-status', [HomeController::class, 'lastQuizStatus'])->name('home.last-quiz-status');
        Route::match(['get', 'post'], '/home/quiz-times', [HomeController::class, 'getQuizTimes'])->name('home.quiz-times');
        Route::post('/announcement/{id}/close', [HomeController::class, 'closeAnnouncement'])->name('announcement.close');
        Route::post('/app-updation/{id}/close', [HomeController::class, 'closeAppUpdation'])->name('app-updation.close');
        Route::post('/home/whatsapp-notify/toggle', [HomeController::class, 'toggleWhatsappNotify'])->name('home.whatsapp-notify.toggle');
        Route::post('/home/whatsapp-subscribe', [HomeController::class, 'subscribeWhatsappChannel'])->name('home.whatsapp-subscribe');

        // New All Quiz List route & folder architecture
        Route::get('/allquizlist', [AllQuizListController::class, 'index'])->name('allquizlist.index');
        
        Route::prefix('quiz')->name('quiz.')->group(function () {
            Route::get('/list', [AllQuizListController::class, 'index'])->name('list');
            Route::get('/waiting/{id}', [QuizController::class, 'waiting'])->name('waiting');
            Route::get('/play/{id}', [QuizController::class, 'play'])->name('play');
            // GET /start/{id} still serves play view for backward compatibility
            Route::get('/start/{id}', [QuizController::class, 'play'])->name('start');

            // POST to actually start the quiz and broadcast the start event
            Route::post('/start/{id}', [QuizController::class, 'startQuiz'])->name('start.post');

            // Status endpoint for late joiners to sync timer
            Route::get('/status/{id}', [QuizController::class, 'status'])->name('status');

            Route::get('/review/{id}', [QuizController::class, 'review'])->name('review');
            Route::get('/result/{id}', [QuizController::class, 'result'])->name('result');
            Route::get('/winners/{id}', [QuizController::class, 'winners'])->name('winners');
            Route::get('/get-winners/{id}', [QuizController::class, 'getWinners'])->name('get-winners');

            // Test routes for broadcasting (development only)
            Route::get('/test/{id}', [QuizController::class, 'testPage'])->name('test');
            Route::get('/test/broadcast/{id}', [QuizController::class, 'testBroadcast'])->name('test.broadcast');
        });

        Route::prefix('quizes')->name('quizes.')->group(function () {
            Route::get('/join/{id}', [QuizesController::class, 'joinView'])->name('join');
            Route::post('/join/{id}', [QuizesController::class, 'joinSubmit'])->name('join.post');
            Route::get('/waiting/{id}', [QuizesController::class, 'waitingView'])->name('waiting');
            Route::get('/start-counter/{id}', [QuizesController::class, 'startCounterView'])->name('start-counter');
            Route::get('/question/{id}', [QuizesController::class, 'questionView'])->name('question');
            Route::get('/review-counter/{id}', [QuizesController::class, 'reviewCounterView'])->name('review-counter');
            Route::get('/answer-sheet/{id}', [QuizesController::class, 'answerSheetView'])->name('answer-sheet');
            Route::get('/final-counter/{id}', [QuizesController::class, 'finalCounterView'])->name('final-counter');
            Route::get('/leaderboard/{id}', [QuizesController::class, 'leaderboardView'])->name('leaderboard');

            // API Session & Sound endpoints
            Route::get('/api/questions/{id}', [QuizesController::class, 'getQuestionsFromSession'])->name('api.questions');
            Route::get('/api/status/{id}', [QuizesController::class, 'getStatusFromSession'])->name('api.status');
            Route::post('/api/submit-answer/{id}', [QuizesController::class, 'submitAnswer'])->name('api.submit-answer');
            Route::match(['get', 'post'], '/api/reconcile-answers/{id}', [QuizesController::class, 'reconcileAnswers'])->name('api.reconcile-answers');
            Route::match(['get', 'post'], '/api/answer-sheet/{id}', [QuizesController::class, 'getAnswerSheetData'])->name('api.answer-sheet');
            Route::match(['get', 'post'], '/api/process-result/{id}', [QuizesController::class, 'processQuizResultAsync'])->name('api.process-result');
            Route::get('/api/leaderboard/{id}', [QuizesController::class, 'getLeaderboardData'])->name('api.leaderboard');
            Route::post('/api/use-lifeline/{id}', [QuizesController::class, 'recordLifelineUse'])->name('api.use-lifeline');
            Route::match(['get', 'post'], '/api/clear-session/{id}', [QuizesController::class, 'clearSessionApi'])->name('api.clear-session');
            Route::match(['get', 'post'], '/api/mark-out/{id}', [QuizesController::class, 'markUserOut'])->name('api.mark-out');
            Route::post('/notify/{id}', [QuizesController::class, 'toggleNotification'])->name('notify');
        });

        Route::prefix('fff')->name('fff.')->group(function () {
            Route::get('/waiting/{id}', [FffQuizController::class, 'waitingView'])->name('waiting');
            Route::get('/start-counter/{id}', [FffQuizController::class, 'startCounterView'])->name('start-counter');
            Route::get('/question/{id}', [FffQuizController::class, 'questionView'])->name('question');
            Route::get('/review-counter/{id}', [FffQuizController::class, 'reviewCounterView'])->name('review-counter');
            Route::get('/answer-sheet/{id}', [FffQuizController::class, 'answerSheetView'])->name('answer-sheet');
            Route::get('/final-counter/{id}', [FffQuizController::class, 'finalCounterView'])->name('final-counter');
            Route::get('/leaderboard/{id}', [FffQuizController::class, 'leaderboardView'])->name('leaderboard');

            // API Session & Sound endpoints
            Route::get('/api/questions/{id}', [FffQuizController::class, 'getQuestionsFromSession'])->name('api.questions');
            Route::post('/api/submit-answer/{id}', [FffQuizController::class, 'submitAnswer'])->name('api.submit-answer');
            Route::match(['get', 'post'], '/api/answer-sheet/{id}', [FffQuizController::class, 'getAnswerSheetData'])->name('api.answer-sheet');
            Route::match(['get', 'post'], '/api/reconcile-answers/{id}', [FffQuizController::class, 'reconcileAnswers'])->name('api.reconcile-answers');
            Route::match(['get', 'post'], '/api/process-result/{id}', [FffQuizController::class, 'processQuizResultAsync'])->name('api.process-result');
            Route::get('/api/leaderboard/{id}', [FffQuizController::class, 'getLeaderboardData'])->name('api.leaderboard');
            Route::match(['get', 'post'], '/api/mark-out/{id}', [FffQuizController::class, 'markUserOut'])->name('api.mark-out');
            Route::post('/api/clear-session/{id}', [FffQuizController::class, 'clearSessionApi'])->name('api.clear-session');
        });

        Route::prefix('practice-quiz')->name('practice-quiz.')->group(function () {
            Route::get('/', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'index'])->name('index');
            Route::get('/play/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'playView'])->name('play');
            Route::get('/review/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'reviewView'])->name('review');
            Route::get('/result/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'resultView'])->name('result');
            
            // Practice Quiz Action Endpoints
            Route::match(['get', 'post'], '/start/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'start'])->name('start');
            Route::post('/get-question/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'getQuestion'])->name('get-question');
            Route::post('/submit-answer/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'submitAnswer'])->name('submit-answer');
            Route::post('/use-lifeline/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'useLifeline'])->name('use-lifeline');
            Route::get('/my-result/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'myResult'])->name('my-result');
            Route::get('/answer-sheet/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'answerSheet'])->name('answer-sheet');
        });

        Route::prefix('survey')->name('survey.')->group(function () {
            Route::get('/', [SurveyController::class, 'index'])->name('index');
            Route::get('/{id}', [SurveyController::class, 'play'])->name('play');
            Route::post('/{id}/submit', [SurveyController::class, 'submit'])->name('submit');
        });

        Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
        Route::get('/history', [HistoryController::class, 'index'])->name('history');
        Route::get('/history/leaderboard/{id}', [HistoryController::class, 'leaderboard'])->name('history.leaderboard');
        Route::get('/history/answer-sheet/{id}', [HistoryController::class, 'answerSheet'])->name('history.answersheet');
        
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletController::class, 'index'])->name('index');
        });

        Route::prefix('redeem')->name('redeem.')->group(function () {
            Route::get('/', [WalletController::class, 'redeem'])->name('index');
            Route::post('/', [WalletController::class, 'submitRedeem'])->name('submit');
            Route::get('/history', [WalletController::class, 'redeemHistory'])->name('history');
            Route::get('/e-gift-voucher-history', [WalletController::class, 'egiftvoucherHistory'])->name('e-gift-voucher-history'); // Naya route
        });

        Route::prefix('vouchers')->name('vouchers.')->group(function () {
            Route::post('/request', [EGiftvoucherController::class, 'requestVoucher'])->name('request');
            Route::get('/my-vouchers', [EGiftvoucherController::class, 'getUserVouchers'])->name('my-vouchers');
        });

        Route::get('/referral', [ReferralController::class, 'index'])->name('referral');
        
        // Profile
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/verify-mobile', [ProfileController::class, 'verifyMobile'])->name('profile.verify-mobile');
        Route::post('/profile/send-email-otp', [ProfileController::class, 'sendEmailOtp'])->name('profile.send-email-otp');
        Route::post('/profile/verify-email', [ProfileController::class, 'verifyEmail'])->name('profile.verify-email');
        Route::get('/profile/cities', [ProfileController::class, 'getCities'])->name('profile.cities');
        Route::post('/profile/update-language', [ProfileController::class, 'updateLanguage'])->name('profile.update-language');
        Route::get('/delete-account', [ProfileController::class, 'deleteAccountView'])->name('delete-account');
        Route::post('/delete-account', [ProfileController::class, 'deleteAccount'])->name('profile.delete-account');
        Route::post('/banner/view/{banner}/{type}', [HomeController::class, 'bannerView'])->name('banner.view');
        Route::post('/banner/click/{banner}/{type}', [HomeController::class, 'bannerClick'])->name('banner.click');

   
        // 1. Feedback Submit karne ka route (POST)
        Route::post('/quiz/feedback', [QuizFeedbackController::class, 'store'])->name('quiz.feedback.store');

        // 2. Feedback History Page ka route (GET) - ✅ NEW
        Route::get('/feedback/history', [QuizFeedbackController::class, 'historyPage'])->name('feedback.history');

        // Leaderboard History ke liye naya route
        Route::get('/my-leaderboard-history', [LeaderboardController::class, 'myLeaderboardHistory'])->name('history.my-leaderboard');

        });
    });
});