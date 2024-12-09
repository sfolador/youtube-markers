<?php

namespace App\Events;

use App\Models\Video;
use Illuminate\Foundation\Events\Dispatchable;

class ChaptersDescriptionGeneratedEvent
{
    use Dispatchable;

    public function __construct(public Video $video)
        {

        }
}
