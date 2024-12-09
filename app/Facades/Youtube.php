<?php

namespace App\Facades;

use App\Models\Video;
use Illuminate\Support\Facades\Facade;


/**
// * @method static updateVideo(Video $video)
 * @see  \App\Services\YoutubeService
 */
class Youtube extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'youtube';
    }
}
