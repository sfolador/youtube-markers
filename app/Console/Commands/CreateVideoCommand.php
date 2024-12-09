<?php

namespace App\Console\Commands;

use App\Actions\CreateVideoFromFile;
use App\Enums\VideoCategory;
use App\Events\VideoCreatedEvent;
use App\Jobs\ProcessVideoMarkersJob;
use App\Jobs\UploadVideoJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class CreateVideoCommand extends Command
{
    protected $signature = 'video:create {filename}';

    protected $description = 'Command description';

    public function handle(): void
    {
        $video = CreateVideoFromFile::execute($this->argument('filename'));

        VideoCreatedEvent::dispatch($video);


    }
}
