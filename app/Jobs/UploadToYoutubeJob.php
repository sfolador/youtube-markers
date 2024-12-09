<?php

namespace App\Jobs;

use App\Events\VideoUploadedEvent;
use App\Facades\Youtube;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadToYoutubeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Video $video)
    {
    }

    public function handle(): void
    {
        Youtube::uploadVideo($this->video);

        VideoUploadedEvent::dispatch($this->video);

    }
}
