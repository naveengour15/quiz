<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\ADashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\QuizTypeController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\RedeemRequestController;
use App\Http\Controllers\Admin\QuizWinnerController;
use App\Http\Controllers\SuperAdmin\AutoUpdateController;

// Admin Panel Routes
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth:admin', 'panel:admin'])
    ->group(function () {

        Route::get('/', [ADashboardController::class, 'index'])->name('dashboard');
        Route::get('/refferal-update', [AutoUpdateController::class, 'refferalUpdate']);

       
        // Categories
        Route::post('categories/{category}/status', [CategoryController::class, 'updateStatus'])->name('categories.status');
        Route::resource('categories', CategoryController::class);

        // Quiz Types
        Route::post('quiz-types/{quizType}/status', [QuizTypeController::class, 'updateStatus'])->name('quiz-types.status');
        Route::resource('quiz-types', QuizTypeController::class);

        // Questions
         Route::get('/questions/download-sample', [QuestionController::class, 'downloadSample'])->name('questions.downloadSample'); 
        Route::post('questions/status', [QuestionController::class, 'changeStatus'])->name('questions.changeStatus');
        Route::resource('questions', QuestionController::class);
        Route::post('/questions/auto-translate-hindi', [QuestionController::class, 'autoTranslateHindi'])->name('questions.autoTranslateHindi');
        Route::post('/questions/import', [QuestionController::class, 'import'])->name('questions.import');
       

        // Quizzes
        Route::post('quizzes/status', [QuizController::class, 'changeStatus'])->name('quizzes.changeStatus');
        Route::post('quizzes/{quiz}/save-questions', [QuizController::class, 'saveQuestions'])->name('quizzes.questions.store');
        Route::post('quizzes/save-rewards', [QuizController::class, 'saveRewards'])->name('quizzes.rewards.save');
        Route::post('quizzes/check-slot', [QuizController::class, 'checkSlot'])->name('quizzes.check-slot');
        Route::resource('quizzes', QuizController::class);
        Route::post('quizzes/approve-change-status', [QuizController::class, 'aproveChangeStatus'])->name('approve.quizzes.changeStatus');
        Route::get('/states/{state}/cities', [QuizController::class, 'getCities'])->name('states.cities');
        Route::get('quizzes/{quiz}/winners', [QuizWinnerController::class, 'winnerList'])->name('quizzes.winners');
        Route::get('/quizzes/{quiz}/winners/{user}/answers', [QuizWinnerController::class, 'getUserAnswers'])->name('quizzes.winners.answers');
        Route::get('quizzes/{quiz}/joined-users', [QuizWinnerController::class, 'joinedUsers'])->name('quizzes.joined-users');  
        Route::post('/quizzes/{quiz}/update-play-data', [QuizController::class, 'quizUpdatePlayData'])->name('quizzes.play.update-data'); 
        Route::delete('/quizzes/{quiz}/rewards/clear', [QuizController::class, 'clearRewards'])->name('quizzes.rewards.clear'); 
        Route::get('other-quizzes', [QuizController::class, 'otherQuizzes'])->name('quiz.other-quizzes');
        Route::post('quizzes/{quiz}/allocate-times', [QuizController::class, 'allocateQuestionTimes'])->name('quizzes.allocateTimes');
        Route::post('quizzes/{quizId}/rewards/add-default', [QuizController::class, 'addDefaultRewards'])->name('quizzes.rewards.add-default');
        Route::get('/prepare-whatsapp-entries', [QuizController::class, 'prepareWhatsAppEntries'])->name('prepare.whatsapp.entries');
         
        
        // Surveys
        Route::post('survey/{survey}/status', [SurveyController::class, 'changeStatus'])->name('surveys.status');
        Route::get('survey/{survey}/report', [SurveyController::class, 'report'])->name('survey.report');
        Route::get('survey/{survey}/report-users', [SurveyController::class, 'reportUsers'])->name('survey.reportUsers');
        Route::get('survey/search-tenant', [SurveyController::class, 'searchTenant'])->name('survey.searchTenant');
        Route::resource('survey', SurveyController::class);

        // Users
        Route::post('users/status', [UserController::class, 'changeStatus'])->name('users.changeStatus');
        Route::get('users/cities/{stateId}', [UserController::class, 'getCities'])->name('users.cities');
        Route::resource('users', UserController::class);

        Route::get('/redeem-requests', [RedeemRequestController::class, 'index'])->name('redeem_requests.index');     
    
        Route::post('redeem-requests/change-status', [RedeemRequestController::class, 'approveChangeStatus'])->name('redeem-requests.changeStatus');
        Route::get('redeem-requests/{id}', [RedeemRequestController::class, 'show'])->name('redeem-requests.show');


        // Vouchers
        Route::post('voucher/status', [VoucherController::class, 'changeStatus'])->name('voucher.changeStatus');
        Route::get('voucher/{voucher}/details', [VoucherController::class, 'details'])->name('voucher.details');
        Route::get('voucher/{voucher}/coupons', [VoucherController::class, 'coupons'])->name('voucher.coupons');
        Route::post('voucher/{voucher}/add-coupons', [VoucherController::class, 'addCoupons'])->name('voucher.addCoupons');
        Route::post('voucher/redemption/{redemption}/approve', [VoucherController::class, 'approve'])->name('voucher.approve');
        Route::post('voucher/redemption/{redemption}/reject', [VoucherController::class, 'reject'])->name('voucher.reject');
        Route::resource('voucher', VoucherController::class);
});

// Admin Logout
Route::any('/admin-logout', [AuthController::class, 'adminLogout'])->name('admin-logout');
