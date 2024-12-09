<?php

namespace App\Events;

use App\Models\Video;
use Illuminate\Foundation\Events\Dispatchable;

class AudioTranscribedEvent
{
    use Dispatchable;

    public function __construct(public Video $video)
    {
    }
}
