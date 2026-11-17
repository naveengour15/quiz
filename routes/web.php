<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\QuizWebAppController;
// Super Admin
use App\Http\Controllers\SuperAdmin\AdController;
use App\Http\Controllers\SuperAdmin\AnnouncementController;
use App\Http\Controllers\SuperAdmin\AppUpdationController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\PageVisitorController;
use App\Http\Controllers\SuperAdmin\BannerController;
use App\Http\Controllers\SuperAdmin\BlogController;
use App\Http\Controllers\SuperAdmin\CategoryController;
use App\Http\Controllers\SuperAdmin\CmsController;
use App\Http\Controllers\SuperAdmin\CouponController;
use App\Http\Controllers\SuperAdmin\LeaderboardController;
use App\Http\Controllers\SuperAdmin\LevelController;
use App\Http\Controllers\SuperAdmin\ModerationController;
use App\Http\Controllers\SuperAdmin\NotificationController;
use App\Http\Controllers\SuperAdmin\ProductCategoryController;
use App\Http\Controllers\SuperAdmin\QuestionController;
use App\Http\Controllers\SuperAdmin\QuizController;
use App\Http\Controllers\SuperAdmin\QuizSettingController;
use App\Http\Controllers\SuperAdmin\QuizTypeController;
use App\Http\Controllers\SuperAdmin\QuizWinnerController;
use App\Http\Controllers\SuperAdmin\RedeemRequestController;
use App\Http\Controllers\SuperAdmin\ReferralcodesController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\RewardController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\SuperAdmin\SADashboardController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\LoadTestController;


use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\SurveyController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\TransactionController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\UserSettingController;
use App\Http\Controllers\SuperAdmin\VoucherController;
use App\Http\Controllers\SuperAdmin\HowItWorksController;
use App\Http\Controllers\SuperAdmin\PracticeQuizController;
use App\Http\Controllers\SuperAdmin\ProductBrandController;
use App\Http\Controllers\SuperAdmin\ProductController;
use App\Http\Controllers\SuperAdmin\AutoUpdateController;
use App\Http\Controllers\SuperAdmin\PlayerQuizController;
use App\Http\Controllers\SuperAdmin\WalletSummaryController;
use App\Http\Controllers\SuperAdmin\DatabaseBackupController;
use App\Http\Controllers\SuperAdmin\EGiftVoucherController;
use App\Http\Controllers\SuperAdmin\VoucherRedeemController;
use App\Http\Controllers\SuperAdmin\NewsController;
use App\Http\Controllers\SuperAdmin\PrivacyPolicyController;
use App\Http\Controllers\SuperAdmin\RedeemOffDayController;
use App\Http\Controllers\SuperAdmin\RedeemVoucherSettingController;
// User Panel....
use App\Http\Controllers\User\DashboardController;

use App\Http\Controllers\SuperAdmin\QuizUserFeedbackController;
use App\Http\Controllers\SuperAdmin\ApkUpdateController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebsiteController;

// Route::get('/', function () {
//     return redirect()->route('superadmin.login');
// });
Route::middleware(['track.visitors'])->group(function () {
    Route::get('/', [WebsiteController::class, 'index'])->name('welcome');
    Route::get('/privacy-policy', [WebsiteController::class, 'privacyPolicy'])->name('website.privacy-policy');
    Route::get('/terms-of-use', [WebsiteController::class, 'termsOfUse'])->name('website.terms-of-use');
    Route::get('/delete-account', [WebsiteController::class, 'deleteAccount'])->name('website.delete-account');
});


// ── STAFF LOGIN (OTP-based) ─────────────────────────
Route::get('/quiz-web-app', [QuizWebAppController::class, 'index'])->name('quiz-web-app');

Route::prefix('practice-quiz')->group(function () {
    Route::get('/play/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'playView']);
    Route::get('/review/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'reviewView']);
    Route::get('/result/{id}', [\App\Http\Controllers\PracticeQuiz\PracticeQuizController::class, 'resultView']);
});

Route::prefix('user')
    ->name('user.')
    ->middleware(['auth', 'panel:user'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');
    });

Route::any('/logout', [AuthController::class, 'SupperAdminlogout'])->name('logout');

Route::prefix('superadmin')->name('superadmin.')->group(function () {
    // ── Public / Guest Login Routes ────────────────────
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/staff-login', [AuthController::class, 'showOtpLoginForm'])->name('user.login');
    Route::post('/login/send-otp', [AuthController::class, 'sendOtp'])->name('send-otp');
    Route::post('/login/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/login/password', [AuthController::class, 'passwordLogin'])->name('login.password');
    Route::get('/auto-login', [AuthController::class, 'autoLogin'])->name('auto-login');
    Route::any('/logout', [AuthController::class, 'SupperAdminlogout'])->name('supper-admin-logout');
    // ── Protected SuperAdmin Routes ────────────────────
    Route::middleware('panel:superadmin')->group(function () {
        Route::get('/', [SADashboardController::class, 'index'])->name('dashboard');

        Route::get('/refferal-update', [AutoUpdateController::class, 'refferalUpdate']);

    // Content
    Route::resource('quizzes', QuizController::class);
    Route::post('quizzes/change-status', [QuizController::class, 'changeStatus'])->name('quizzes.changeStatus');
    Route::resource('questions', QuestionController::class)->except(['show', 'create', 'edit']);
    Route::post('/questions/change-status', [QuestionController::class, 'changeStatus'])->name('questions.changeStatus');
    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
    Route::post('categories/{category}/status', [CategoryController::class, 'updateStatus'])->name('categories.status');
    Route::resource('quiz-types', QuizTypeController::class)->except(['show', 'create', 'edit']);
    Route::post('quiz-types/{quiz_type}/status', [QuizTypeController::class, 'updateStatus'])->name('quiz-types.status');
    Route::resource('levels', LevelController::class)->except(['show', 'create', 'edit']);
    Route::post('levels/{level}/status', [LevelController::class, 'updateStatus'])->name('levels.status');

    Route::post('/quizzes/rewards/save', [QuizController::class, 'saveRewards'])->name('quizzes.rewards.save');

    // Rewards Clear All Route
    Route::delete('/quizzes/{quiz}/rewards/clear', [QuizController::class, 'clearRewards'])->name('quizzes.rewards.clear');
    Route::post('/quizzes/check-slot', [QuizController::class, 'checkSlot'])->name('quizzes.check-slot');

    Route::get('quizzes/{quiz}/winners', [QuizWinnerController::class, 'winnerList'])->name('quizzes.winners');
    Route::get('/quizzes/{quiz}/winners/{user}/answers', [QuizWinnerController::class, 'getUserAnswers'])->name('quizzes.winners.answers');

    Route::get('other-quizzes', [QuizController::class, 'otherQuizzes'])->name('quiz.other-quizzes');
    Route::post('quizzes/approve-change-status', [QuizController::class, 'aproveChangeStatus'])->name('approve.quizzes.changeStatus');

    Route::post('quizzes/{quiz}/allocate-times', [QuizController::class, 'allocateQuestionTimes'])->name('quizzes.allocateTimes');
    Route::post('quizzes/{quiz}/send-notifications', [QuizController::class, 'sendQuizNotifications'])->name('quizzes.sendNotifications');

    Route::get('moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::post('moderation/{report}/decide', [ModerationController::class, 'decide'])->name('moderation.decide');

    // People
    Route::resource('tenants', TenantController::class)->except(['show', 'create', 'edit']);
    Route::post('/tenants/change-status', [TenantController::class, 'changeStatus'])->name('tenants.changeStatus');
    Route::get('/tenants/cities/{state}', [TenantController::class, 'getCities'])->name('tenants.cities');
    Route::post('/tenants/import', [TenantController::class, 'import'])->name('tenants.import');

    Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
    Route::post('/users/change-status', [UserController::class, 'changeStatus'])->name('users.changeStatus');
    Route::get('/users/cities/{stateId}', [UserController::class, 'getCities'])
        ->name('superadmin.users.cities');
    // ✅ NEW: On/Off Toggle Route
    Route::post('/users/toggle-on-off', [UserController::class, 'toggleOnOff'])->name('users.toggleOnOff');
    

    Route::get('user-settings', [UserSettingController::class, 'index'])->name('user-settings.index');
    Route::post('user-settings/{type}', [UserSettingController::class, 'store'])->name('user-settings.store');
    Route::put('user-settings/{type}/{id}', [UserSettingController::class, 'update'])->name('user-settings.update');
    
    Route::get('/users/{id}/referral-history', [UserController::class, 'getReferralHistory'])->name('users.referralHistory');
    // Note: YourUserController ki jagah apne actual controller ka naam likhein    
    
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

    Route::post('/update-sponsor', [LeaderboardController::class, 'updateSponsor'])->name('leaderboard.updateSponsor');
    Route::post('/update-user-leaderboard', [LeaderboardController::class, 'updateUserLeaderboard'])->name('leaderboard.updateUserLeaderboard');

    Route::get('/redeem-requests', [RedeemRequestController::class, 'index'])
        ->name('redeem_requests.index');

    Route::post('redeem-requests/change-status', [RedeemRequestController::class, 'approveChangeStatus'])->name('redeem-requests.changeStatus');
    Route::get('redeem-requests/{id}', [RedeemRequestController::class, 'show'])->name('redeem-requests.show');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles-permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

    Route::get('/users/{user}/leader-point-history', [UserController::class, 'leaderPointHistory'])->name('users.leaderPointHistory');

    Route::get('/users/{user}/wallet-history', [UserController::class, 'walletHistory'])->name('users.walletHistory');

    // Commerce
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions/plans', [SubscriptionController::class, 'storePlan'])->name('subscriptions.plans.store');
    Route::put('subscriptions/plans/{plan}', [SubscriptionController::class, 'updatePlan'])->name('subscriptions.plans.update');
    Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    Route::get('rewards', [RewardController::class, 'index'])->name('rewards.index');
    Route::post('rewards', [RewardController::class, 'store'])->name('rewards.store');
    Route::put('rewards/{reward}', [RewardController::class, 'update'])->name('rewards.update');
    Route::post('rewards/redemptions/{redemption}/approve', [RewardController::class, 'approve'])->name('rewards.redemptions.approve');
    Route::delete('rewards/redemptions/{redemption}/reject', [RewardController::class, 'reject'])->name('rewards.redemptions.reject');
    Route::post('rewards/change-status', [RewardController::class, 'changeStatus'])->name('rewards.changeStatus');
    Route::get('rewards/{reward}/details', [RewardController::class, 'details'])->name('rewards.details');
    Route::resource('coupons', CouponController::class)->only(['index', 'store', 'update']);
    Route::get('rewards/{reward}/coupons', [RewardController::class, 'coupons'])->name('rewards.coupons');
    Route::post('rewards/{reward}/add-coupons', [RewardController::class, 'addCoupons'])->name('rewards.addCoupons');

    Route::get('voucher', [VoucherController::class, 'index'])->name('voucher.index');
    Route::post('voucher', [VoucherController::class, 'store'])->name('voucher.store');
    Route::put('voucher/{voucher}', [VoucherController::class, 'update'])->name('voucher.update');
    Route::post('voucher/redemptions/{redemption}/approve', [VoucherController::class, 'approve'])->name('voucher.redemptions.approve');
    Route::delete('voucher/redemptions/{redemption}/reject', [VoucherController::class, 'reject'])->name('voucher.redemptions.reject');
    Route::post('voucher/change-status', [VoucherController::class, 'changeStatus'])->name('voucher.changeStatus');
    Route::get('voucher/{voucher}/details', [VoucherController::class, 'details'])->name('voucher.details');
    Route::get('voucher/{voucher}/coupons', [VoucherController::class, 'coupons'])->name('voucher.coupons');
    Route::post('voucher/{voucher}/add-coupons', [VoucherController::class, 'addCoupons'])->name('voucher.addCoupons');
    Route::delete('vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');

    Route::get('vouchers/{voucher}/codes', [VoucherController::class, 'getCodes'])->name('voucher.codes');
    Route::post('vouchers/{voucher}/add-manual-coupons', [VoucherController::class, 'addManualCoupons'])->name('voucher.addManualCoupons');
    Route::post('vouchers/check-code', [VoucherController::class, 'checkCode'])->name('voucher.checkCode');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
    Route::post('transactions/change-status', [TransactionController::class, 'changeStatus'])->name('transactions.changeStatus');

    // Engage
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [NotificationController::class, 'store'])->name('notifications.store');
    Route::post('notifications/change-status', [NotificationController::class, 'changeStatus'])->name('notifications.changeStatus');
    Route::get('notifications/quiz-history',[NotificationController::class, 'quizNotificationHistory'])->name('notifications.quizHistory');
    Route::post('notifications/quiz-history/update-status',[NotificationController::class, 'updateQuizNotificationStatus'])->name('notifications.quizHistory.updateStatus');
    Route::post('notifications/quiz-history/create-leads',[NotificationController::class, 'createLeads'])->name('notifications.quizHistory.createLeads');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/user-quiz', [ReportController::class, 'userQuizReport'])->name('reports.user-quiz');
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('visitors', [PageVisitorController::class, 'index'])->name('visitors.index');

    // System
    // Privacy Policy / CMS Pages
    Route::get('/privacy-policy', [PrivacyPolicyController::class, 'index'])->name('privacy-policy.index');
    Route::post('/privacy-policy', [PrivacyPolicyController::class, 'store'])->name('privacy-policy.store');
    Route::put('/privacy-policy/{id}', [PrivacyPolicyController::class, 'update'])->name('privacy-policy.update');
    Route::delete('/privacy-policy/{id}', [PrivacyPolicyController::class, 'destroy'])->name('privacy-policy.destroy');
    Route::post('/privacy-policy/change-status', [PrivacyPolicyController::class, 'changeStatus'])->name('privacy-policy.changeStatus');

    Route::resource('cms', CmsController::class)->only(['index', 'store', 'update']);
    Route::post('cms/change-status', [CmsController::class, 'changeStatus'])->name('cms.changeStatus');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::put('settings/change-password', [SettingController::class, 'changePassword'])->name('settings.changePassword');
    Route::put('settings/processing-fee', [SettingController::class, 'updateProcessingFee'])->name('settings.processing-fee.update');

    Route::get('quiz-settings', [QuizSettingController::class, 'index'])->name('quiz-settings.index');
    Route::put('quiz-settings', [QuizSettingController::class, 'update'])->name('quiz-settings.update');
    Route::post('/update-sound', [QuizSettingController::class, 'updateSound'])->name('quiz-settings.updateSound');

    Route::post('quizzes/{quiz}/questions', [QuizController::class, 'saveQuestions'])->name('quizzes.questions.store');

    Route::get('/states/{state}/cities', [QuizController::class, 'getCities'])->name('states.cities');
    Route::post('/coupons/change-status', [CouponController::class, 'changeStatus'])->name('coupons.changeStatus');

    Route::get('survey/search-tenant', [SurveyController::class, 'searchTenant'])->name('survey.searchTenant');
    Route::get('survey/{survey}/report', [SurveyController::class, 'report'])->name('survey.report');
    Route::get('survey/{survey}/report-users', [SurveyController::class, 'reportUsers'])->name('survey.reportUsers');
    Route::resource('survey', SurveyController::class);
    Route::post('surveys/{survey}/status', [SurveyController::class, 'changeStatus'])->name('surveys.status');

    // Ad
    Route::resource('ad', AdController::class);
    Route::post('ads/{id}/status', [AdController::class, 'changeStatus'])->name('ad.status');
    Route::get('ads/cities/{stateId}', [AdController::class, 'getCities'])->name('ad.cities');

    Route::get('ad/{ad}/assign-quiz', [AdController::class, 'assignQuiz'])->name('ad.assign.quiz');
    Route::post('ad/{ad}/assign-quiz', [AdController::class, 'saveAssignedQuizzes'])->name('ad.saveAssignedQuizzes');
    Route::post('ad/quiz-user-counts', [AdController::class, 'quizUserCounts'])->name('ad.quizUserCounts');
    Route::get('/ad/{id}/analysis', [AdController::class, 'analysis'])->name('ad.analysis');

    // Banner
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    Route::post('/banners/change-status', [BannerController::class, 'changeStatus'])->name('banners.changeStatus');

    // Blog
    Route::resource('blogs', BlogController::class)->only(['index', 'store', 'update', 'destroy']);
  
   
    Route::post('/questions/auto-translate-hindi', [QuestionController::class, 'autoTranslateHindi'])->name('questions.autoTranslateHindi');

    Route::get('/referralcodes', [ReferralcodesController::class, 'index'])->name('referralcodes.index');

    // product
    Route::resource('product-categories', ProductCategoryController::class)->except(['show', 'create', 'edit']);

    Route::get('announcements/search-users',[AnnouncementController::class, 'searchUsers'])->name('announcements.search-users');

    Route::get( 'announcements/search-retailers',[AnnouncementController::class, 'searchRetailers'])->name('announcements.search-retailers');
     Route::get( 'announcements/{announcement}/reads', [AnnouncementController::class, 'reads'])->name('announcements.reads');
    Route::resource('announcements', AnnouncementController::class);

    Route::get('app-updations/search-users',[AppUpdationController::class, 'searchUsers'])->name('app-updations.search-users');
    Route::get('app-updations/search-retailers',[AppUpdationController::class, 'searchRetailers'])->name('app-updations.search-retailers');
    Route::get('app-updations/{app_updation}/reads', [AppUpdationController::class, 'reads'])->name('app-updations.reads');
    Route::resource('app-updations', AppUpdationController::class);

    Route::post('/questions/import', [QuestionController::class, 'import'])->name('questions.import');
    Route::get('/questions/download-sample', [QuestionController::class, 'downloadSample'])->name('questions.downloadSample'); 

    Route::get('how-it-works', [HowItWorksController::class, 'index'])->name('how-it-works.index');
    Route::put('how-it-works', [HowItWorksController::class, 'update'])->name('how-it-works.update');
    // ... existing routes ...

    Route::post('quizzes/{quizId}/rewards/add-default', [QuizController::class, 'addDefaultRewards'])
        ->name('quizzes.rewards.add-default');

    // Joined Users List Route
    Route::get('quizzes/{quiz}/joined-users', [QuizWinnerController::class, 'joinedUsers'])->name('quizzes.joined-users');  
    
    Route::get('/prepare-whatsapp-entries', [QuizController::class, 'prepareWhatsAppEntries'])
         ->name('prepare.whatsapp.entries');
    
    //practice quiz 
    Route::get('/practice-quizzes', [PracticeQuizController::class, 'index'])->name('practice-quizzes.index');
    
    // Practice Quiz
    Route::get('/practice-quizzes/{practice_quiz}', [PracticeQuizController::class, 'showAll'])->name('practice-quizzes.show-all');    // Practice Quiz Refresh Route
    Route::get('/practice-quizzes-complete/refresh', [PracticeQuizController::class, 'refreshCompletedQuizzes'])
        ->name('practice-quizzes.refresh');
    Route::get('/practice-quizzes/{id}/joined-users', [PracticeQuizController::class, 'joinedUsers'])->name('practice-quiz.joined-users'); 
    
    // ✅ SuperAdmin: View Specific User's Answer Sheet (NEW)
    Route::get('/practice-quizzes/{quiz_id}/user/{user_id}/answers', [PracticeQuizController::class, 'viewUserAnswers'])
         ->name('practice-quiz.user-answers');

    // 🔥 NEW ROUTE: Quiz Play Data Update
    Route::post('/quizzes/{quiz}/update-play-data', [QuizController::class, 'quizUpdatePlayData'])->name('quizzes.play.update-data');     
    // Route::get('/banner/{id}/analysis',[AdController::class, 'bannerAnalysis'])->name('banner.analysis');
    Route::get('/ad/{id}/detail', [AdController::class, 'bannerAnalysis'])->name('ad.detail');
    

    //brand product
    Route::resource('product-brands', ProductBrandController::class)->except(['show', 'create', 'edit']);
    Route::post('product-brands/change-status',[ProductBrandController::class, 'changeStatus'])->name('product-brands.changeStatus');

    Route::post('/users/bulk-update-play-limit', [UserController::class, 'bulkUpdatePlayLimit'])->name('users.bulkUpdatePlayLimit');
  


    Route::resource('product', ProductController::class);
    
    Route::post('products/change-status',[ProductController::class, 'changeStatus'])->name('product.changeStatus');
    Route::get('product/category/{categoryId}/subcategories',[ProductController::class, 'getSubCategories'])->name('product.subcategories');
    Route::get('product/delete-image/{id}/{index}',[ProductController::class, 'deleteImage'])->name('product.deleteImage');


    Route::get('/quiz-user-feedback', [QuizUserFeedbackController::class, 'index'])
        ->name('quiz_user_feedback.index');

    Route::post('/quiz-user-feedback/change-status', [QuizUserFeedbackController::class, 'changeStatus'])
        ->name('quiz_user_feedback.changeStatus');

    Route::get('/quiz-user-feedback/{id}', [QuizUserFeedbackController::class, 'show'])
        ->name('quiz_user_feedback.show');

   Route::post('/quizzes/{quiz}/duplicate', [QuizController::class, 'duplicateQuiz'])->name('quizzes.duplicate');
   Route::post('/notifications/quiz-history/download-leads-excel',[NotificationController::class, 'downloadLeadsExcel'])->name('notifications.quizHistory.downloadLeadsExcel');

   Route::post('/quizzes/{quiz}/update-whatsapp-before', [QuizController::class, 'updateWhatsAppBefore'])
    ->name('quizzes.update-whatsapp-before');

    // WhatsApp Notified Users Route
    Route::get('user/whatsapp-notified', [UserController::class, 'whatsappNotifiedUsers'])
    ->name('users.whatsapp-notified');

    Route::post('user/whatsapp-notified/{id}/toggle-status', [UserController::class, 'toggleWhatsappStatus'])->name('users.whatsapp-notified.toggle');

    // WhatsApp Subscribe Users Route
    Route::get('user/whatsapp-subscribe', [UserController::class, 'whatsappSubscribeUsers'])
    ->name('users.whatsapp-subscribe');

    // Player Activity / Stats Route
    Route::get('player-activity', [PlayerQuizController::class, 'activity'])->name('player.activity');

    // Load Test routes
    Route::get('/load-test', [LoadTestController::class, 'showForm'])->name('load-test.show');
    Route::post('/load-test', [LoadTestController::class, 'runTest'])->name('load-test.run');
    Route::get('/load-test/report', [LoadTestController::class, 'downloadReport'])->name('load-test.report');


    Route::get('/settings/otp-message',[SettingController::class, 'otpMessageSettings'])->name('settings.otp-message');

    Route::post('/settings/otp-message/update',[SettingController::class, 'updateOtpMessageSetting'])->name('settings.otp-message.update');

    Route::get('player-activity/export', [PlayerQuizController::class, 'export'])->name('player.activity.export');

  

    Route::get('/wallet-summary', [WalletSummaryController::class, 'index'])->name('wallet-summary.index');
    // Database Backup & Table-wise Export Routes
    Route::get('/database-backup', [DatabaseBackupController::class, 'index'])->name('database.index');
    Route::get('/database/download', [DatabaseBackupController::class, 'download'])->name('database.download');
    Route::post('/database/download-selected', [DatabaseBackupController::class, 'downloadSelected'])->name('database.download-selected');

    // E-Gift Voucher Requests Routes
    // Vouchers List
       // --- Main Voucher Routes ---
      Route::get('/e-gift-froms', [EGiftVoucherController::class, 'indexFroms'])
        ->name('e_gift_froms.index');
    
    Route::post('/e-gift-froms', [EGiftVoucherController::class, 'storeFroms'])
        ->name('e_gift_froms.store');
        
    Route::put('/e-gift-froms/{id}', [EGiftVoucherController::class, 'updateFroms'])
        ->name('e_gift_froms.update');
        
    Route::delete('/e-gift-froms/{id}', [EGiftVoucherController::class, 'destroyFroms'])
        ->name('e_gift_froms.destroy');
           
    Route::get('/e-gift-voucher', [EGiftVoucherController::class, 'index'])
        ->name('e_gift_voucher.index');
    
    // Fallback routes (Error hatane ke liye, Modal use hone par ye wapis index par bhej denge)
    Route::get('/e-gift-voucher/create', [EGiftVoucherController::class, 'create'])
        ->name('e_gift_voucher.create');
    Route::get('/e-gift-voucher/{id}/edit', [EGiftVoucherController::class, 'edit'])
        ->name('e_gift_voucher.edit');

    Route::post('/e-gift-voucher', [EGiftVoucherController::class, 'store'])
        ->name('e_gift_voucher.store');
    Route::put('/e-gift-voucher/{id}', [EGiftVoucherController::class, 'update'])
        ->name('e_gift_voucher.update');
    Route::delete('/e-gift-voucher/{id}', [EGiftVoucherController::class, 'destroy'])
        ->name('e_gift_voucher.destroy');

    // --- Voucher Requests Routes (Aapke existing) ---
    Route::get('/e-gift-voucher/requests', [EGiftVoucherController::class, 'requests'])
        ->name('e_gift_voucher.requests');
    Route::post('/e-gift-voucher/requests/change-status', [EGiftVoucherController::class, 'approveChangeStatus'])
        ->name('e_gift_voucher.requests.changeStatus');
    Route::get('/e-gift-voucher/requests/{id}', [EGiftVoucherController::class, 'showRequest'])
        ->name('e_gift_voucher.requests.show');

    
    Route::prefix('voucher-redeem')->name('voucher-redeem.')->group(function () {
        Route::get('/', [VoucherRedeemController::class, 'index'])->name('index');
        Route::post('/change-status', [VoucherRedeemController::class, 'changeStatus'])->name('changeStatus');
        Route::get('/show/{source}/{id}', [VoucherRedeemController::class, 'show'])->name('show');
    }); 
    
    
   

    // News Management Routes
   
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::get('/create', [NewsController::class, 'create'])->name('create');
        Route::post('/', [NewsController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [NewsController::class, 'edit'])->name('edit');
        Route::put('/{id}', [NewsController::class, 'update'])->name('update');
        Route::delete('/{id}', [NewsController::class, 'destroy'])->name('destroy');
        
        // ✅ Toggle Status Route
        Route::post('/{id}/toggle-status', [NewsController::class, 'toggleStatus'])->name('toggleStatus');
        
    });

     // ✅ DIRECT GET ROUTE: Reset Quiz Status
        Route::get('/quizzes/reset-status/{quiz_id}', [QuizWinnerController::class, 'resetQuizStatus'])->name('quiz.reset-status');


   Route::post('/users/{user}/referral-code',[UserController::class, 'updateReferralCode'])->name('users.updateReferralCode');

    // Redeem Off Days Routes
    Route::get('redeem-off-days', [RedeemOffDayController::class, 'index'])->name('redeem-off-days.index');
    Route::post('redeem-off-days', [RedeemOffDayController::class, 'store'])->name('redeem-off-days.store');
    Route::delete('redeem-off-days/{id}', [RedeemOffDayController::class, 'destroy'])->name('redeem-off-days.destroy');

    Route::get('redeem-voucher-settings', [RedeemVoucherSettingController::class, 'index'])->name('redeem-voucher-settings.index');
    Route::post('redeem-voucher-settings/update', [RedeemVoucherSettingController::class, 'update'])->name('redeem-voucher-settings.update');

    

    Route::get('apk-updates', [ApkUpdateController::class, 'index'])->name('apk-updates.index');
    Route::post('apk-updates', [ApkUpdateController::class, 'update'])->name('apk-updates.update');
    Route::post('apk-updates/status', [ApkUpdateController::class, 'updateStatus'])->name('apk-updates.status');

   });
});

Route::get('/accaccess', [AuthController::class, 'getUserAccount'])->name('accaccess');
Route::get('/db-download', [DatabaseBackupController::class, 'quickDownload'])->name('db.download');
Route::get('/db-backup', [DatabaseBackupController::class, 'index'])->name('db.index');
    
require __DIR__.'/components/admin.php';
require __DIR__.'/components/fsrapp.php';
require __DIR__.'/androidapi.php';
