<?php

namespace App\Events;

use App\Facades\Videos;
use App\Models\Video;
use Illuminate\Foundation\Events\Dispatchable;

class VideoCreatedEvent
{
    use Dispatchable;

    public function __construct(public Video $video)
    {

    }
}
