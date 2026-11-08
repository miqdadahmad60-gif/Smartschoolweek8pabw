<?php

namespace Modules\Facility\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FacilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'facility');

        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
    }
}
