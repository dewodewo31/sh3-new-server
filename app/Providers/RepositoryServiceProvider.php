<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\UserRepository::class,
            \App\Repositories\UserRepository::class
        );
        $this->app->bind(
            \App\Repositories\ParticipantRepository::class,
            \App\Repositories\ParticipantRepository::class
        );
        $this->app->bind(
            \App\Repositories\CategoryRepository::class,
            \App\Repositories\CategoryRepository::class
        );
        $this->app->bind(
            \App\Repositories\EventRepository::class,
            \App\Repositories\EventRepository::class
        );
        $this->app->bind(
            \App\Repositories\EventParticipantRepository::class,
            \App\Repositories\EventParticipantRepository::class
        );
        $this->app->bind(
            \App\Repositories\MerchandiseRepository::class,
            \App\Repositories\MerchandiseRepository::class
        );
        $this->app->bind(
            \App\Repositories\PaymentRepository::class,
            \App\Repositories\PaymentRepository::class
        );
        $this->app->bind(
            \App\Repositories\SponsorRepository::class,
            \App\Repositories\SponsorRepository::class
        );
        $this->app->bind(
            \App\Repositories\GalleryRepository::class,
            \App\Repositories\GalleryRepository::class
        );
        $this->app->bind(
            \App\Repositories\OrganizationMemberRepository::class,
            \App\Repositories\OrganizationMemberRepository::class
        );
        $this->app->bind(
            \App\Repositories\AttendanceRepository::class,
            \App\Repositories\AttendanceRepository::class
        );
    }
}
