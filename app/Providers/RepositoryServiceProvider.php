<?php

namespace App\Providers;

use App\Repositories\ActivityRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\EventBudgetRepository;
use App\Repositories\EventParticipantRepository;
use App\Repositories\EventRepository;
use App\Repositories\FinancialAccountRepository;
use App\Repositories\GalleryRepository;
use App\Repositories\MerchandiseRepository;
use App\Repositories\OrganizationMemberRepository;
use App\Repositories\ParticipantRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SponsorRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepository::class,
            UserRepository::class
        );
        $this->app->bind(
            ParticipantRepository::class,
            ParticipantRepository::class
        );
        $this->app->bind(
            CategoryRepository::class,
            CategoryRepository::class
        );
        $this->app->bind(
            EventRepository::class,
            EventRepository::class
        );
        $this->app->bind(
            EventParticipantRepository::class,
            EventParticipantRepository::class
        );
        $this->app->bind(
            MerchandiseRepository::class,
            MerchandiseRepository::class
        );
        $this->app->bind(
            PaymentRepository::class,
            PaymentRepository::class
        );
        $this->app->bind(
            SponsorRepository::class,
            SponsorRepository::class
        );
        $this->app->bind(
            GalleryRepository::class,
            GalleryRepository::class
        );
        $this->app->bind(
            OrganizationMemberRepository::class,
            OrganizationMemberRepository::class
        );
        $this->app->bind(
            AttendanceRepository::class,
            AttendanceRepository::class
        );
        $this->app->bind(
            FinancialAccountRepository::class,
            FinancialAccountRepository::class
        );
        $this->app->bind(
            ActivityRepository::class,
            ActivityRepository::class
        );
        $this->app->bind(
            EventBudgetRepository::class,
            EventBudgetRepository::class
        );
    }
}
