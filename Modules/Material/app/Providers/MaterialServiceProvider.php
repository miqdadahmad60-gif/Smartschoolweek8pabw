<?php

namespace Modules\Material\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MaterialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'material');

        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
    }
}
