<?php

namespace App\Providers;

use App\Listeners\VideoListener;
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
        //

        \Event::subscribe(VideoListener::class);
    }
}
