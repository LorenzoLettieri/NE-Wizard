<?php

namespace App\Providers;

use App\Models\Work;
use App\Observers\WorkObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        Work::observe(WorkObserver::class);

        RateLimiter::for('adminer', function (Request $request) {
            $identifier = optional($request->user())->getAuthIdentifier() ?? 'guest';

            return Limit::perMinute(120)->by($identifier.'|'.$request->ip());
        });
    }
}
