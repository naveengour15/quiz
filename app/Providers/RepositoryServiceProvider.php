<?php

namespace App\Providers;

use App\Interfaces\api\AdminRepositoryInterface;
use App\Interfaces\api\AuthRepositoryInterface;
use App\Interfaces\api\CmsRepositoryInterface;
use App\Interfaces\api\LookupRepositoryInterface;
use App\Interfaces\api\PlanRepositoryInterface;
use App\Interfaces\api\ProfileRepositoryInterface;
use App\Interfaces\api\QuestionRepositoryInterface;
use App\Interfaces\api\QuizAdminRepositoryInterface;
use App\Interfaces\api\QuizRepositoryInterface;
use App\Interfaces\api\QuizWinnerRepositoryInterface;
use App\Interfaces\api\ReferralRepositoryInterface;
use App\Interfaces\api\RewardRepositoryInterface;
use App\Interfaces\api\SurveyRepositoryInterface;
use App\Repositories\api\AdminRepository;
use App\Repositories\api\AuthRepository;
use App\Repositories\api\CmsRepository;
use App\Repositories\api\LookupRepository;
use App\Repositories\api\PlanRepository;
use App\Repositories\api\ProfileRepository;
use App\Repositories\api\QuestionRepository;
use App\Repositories\api\QuizAdminRepository;
use App\Repositories\api\QuizRepository;
use App\Repositories\api\QuizWinnerRepository;
use App\Repositories\api\ReferralRepository;
use App\Repositories\api\RewardRepository;
use App\Repositories\api\SurveyRepository;
use Illuminate\Support\ServiceProvider;

use App\Interfaces\api\RedeemRequestRepositoryInterface;
use App\Repositories\api\RedeemRequestRepository;


use App\Interfaces\api\LeaderboardUserUpdateRepositoryInterface;
use App\Repositories\api\LeaderboardUserUpdateRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register bindings.
     *
     * NOTE: Register this provider in config/app.php (providers array):
     *   App\Providers\RepositoryServiceProvider::class,
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->bind(LookupRepositoryInterface::class, LookupRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(QuizAdminRepositoryInterface::class, QuizAdminRepository::class);
        $this->app->bind(QuizRepositoryInterface::class, QuizRepository::class);
        $this->app->bind(QuizWinnerRepositoryInterface::class, QuizWinnerRepository::class);
        $this->app->bind(SurveyRepositoryInterface::class, SurveyRepository::class);
        $this->app->bind(RewardRepositoryInterface::class, RewardRepository::class);
        $this->app->bind(ReferralRepositoryInterface::class, ReferralRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(CmsRepositoryInterface::class, CmsRepository::class);
        $this->app->bind(\App\Repositories\PracticeQuiz\PracticeQuizRepositoryInterface::class, \App\Repositories\PracticeQuiz\PracticeQuizRepository::class);
        $this->app->bind(LeaderboardUserUpdateRepositoryInterface::class,LeaderboardUserUpdateRepository::class);
        $this->app->bind(\App\Interfaces\fsrapp\HomeRepositoryInterface::class, \App\Repositories\fsrapp\HomeRepository::class);
        $this->app->bind(\App\Interfaces\fsrapp\EGiftvoucherRepositoryInterface::class, \App\Repositories\fsrapp\EGiftvoucherRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
