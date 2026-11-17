<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;


use App\Repositories\WhatsAppRepositoryInterface;
use App\Repositories\WhatsAppRepository;

use App\Repositories\QuizNotificationRepositoryInterface;
use App\Repositories\QuizNotificationRepository;

use App\Repositories\admin\QuizRepositoryInterface;
use App\Repositories\admin\QuizRepository;

use App\Repositories\superadmin\SurveyRepositoryInterface;
use App\Repositories\superadmin\SurveyRepository;

use App\Repositories\superadmin\AdRepositoryInterface;
use App\Repositories\superadmin\AdRepository;

//API
use App\Interfaces\api\AuthRepositoryInterface;
use App\Repositories\api\AuthRepository;


use App\Interfaces\api\DashboardRepositoryInterface;
use App\Repositories\api\DashboardRepository;

//Common 
use App\Interfaces\ActionTrailRepositoryInterface;
use App\Repositories\ActionTrailRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            WhatsAppRepositoryInterface::class,
            WhatsAppRepository::class
        );

        $this->app->bind(
            QuizNotificationRepositoryInterface::class,
            QuizNotificationRepository::class
        );

        $this->app->bind(
            QuizRepositoryInterface::class,
            QuizRepository::class
        );

        $this->app->bind(
            SurveyRepositoryInterface::class,
            SurveyRepository::class
        );

        $this->app->bind(
            AdRepositoryInterface::class,
            AdRepository::class
        );

        //API
        $this->app->bind(
            AuthRepositoryInterface::class,
            AuthRepository::class
            );
        $this->app->bind(
            DashboardRepositoryInterface::class,
            DashboardRepository::class
            );

        //Common
        $this->app->bind(
            ActionTrailRepositoryInterface::class,
            ActionTrailRepository::class
        );

        //Android API
        $this->app->bind(
            \App\Interfaces\androidapi\AuthRepositoryInterface::class,
            \App\Repositories\androidapi\AuthRepository::class
        );
        $this->app->bind(
            \App\Interfaces\androidapi\HomeRepositoryInterface::class,
            \App\Repositories\androidapi\HomeRepository::class
        );
        $this->app->bind(
            \App\Interfaces\androidapi\QuizRepositoryInterface::class,
            \App\Repositories\androidapi\QuizRepository::class
        );
        $this->app->bind(
            \App\Interfaces\androidapi\QuizWinnerRepositoryInterface::class,
            \App\Repositories\androidapi\QuizWinnerRepository::class
        );

        //FSR App Web
        $this->app->bind(
            \App\Interfaces\fsrapp\HomeRepositoryInterface::class,
            \App\Repositories\fsrapp\HomeRepository::class
        );

        //Referral Rewards
        $this->app->bind(
            \App\Repositories\ProcessReferralRewardsRepositoryInterface::class,
            \App\Repositories\ProcessReferralRewardsRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
