<?php

namespace App\Facades;

use App\Models\Video;
use Illuminate\Support\Facades\Facade;

/**
 *
 * @see \App\Services\VideoService
 */
class Videos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'videos';
    }
}
