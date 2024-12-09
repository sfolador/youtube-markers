<?php

namespace App\Providers;

use App\Facades\Videos;
use App\Services\VideoService;
use App\Services\YoutubeService;
use Illuminate\Support\ServiceProvider;

class VideoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('videos', function ($app) {
            return new VideoService();
        });

        $this->app->singleton('youtube',function(){
            return new YoutubeService();
        });
    }

    public function boot(): void
    {
    }
}
