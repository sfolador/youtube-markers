<?php

namespace App\Console\Commands;

use App\Jobs\ProcessVideoMarkersJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class YoutubeMarkersCommand extends Command
{
    protected $signature = 'youtube:markers {video_id}';

    protected $description = 'Command description';

    public function handle()
    {
        $videoId = $this->argument('video_id');


        $job = new ProcessVideoMarkersJob('ScreenFlow.srt','Z5POwS7fimY');
        $job->handle();
    }
}
