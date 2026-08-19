<?php

namespace App\Providers;

use App\Services\CacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        app(CacheService::class)->warm();

        Gate::define('admin_full_access', fn ($user) => $user->role === 'admin_full_access');

        Gate::define('admin_laman', fn ($user) => in_array($user->role, ['admin_full_access', 'admin_laman']));

        Gate::define('admin_member', fn ($user) => in_array($user->role, ['admin_full_access', 'admin_member']));

        Gate::define('admin_bnh', fn ($user) => $user->role === 'admin_bnh');

        Gate::define('organizer', fn ($user) => in_array($user->role, ['admin_full_access', 'organizer']));

        Gate::define('bendahara', fn ($user) => in_array($user->role, ['admin_full_access', 'bendahara']));

        Gate::define('sponsor', fn ($user) => in_array($user->role, ['admin_full_access', 'sponsor']));

        Gate::define('merchandise', fn ($user) => in_array($user->role, ['admin_full_access', 'admin_laman', 'merchandise']));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = ($request->input('username') ?? '').'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
