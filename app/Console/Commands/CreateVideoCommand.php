<?php

namespace App\Console\Commands;

use App\Actions\CreateVideoFromFile;
use App\Enums\VideoCategory;
use App\Events\VideoCreatedEvent;
use App\Jobs\ProcessVideoMarkersJob;
use App\Jobs\UploadVideoJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use function Laravel\Prompts\text;

class CreateVideoCommand extends Command
{
    protected $signature = 'video:create {filename}';

    protected $description = 'Command description';

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->info("Hi! Let's create a new video!");
        $title = text('Enter the title of the video');
        $video = CreateVideoFromFile::execute($this->argument('filename'),$title);

        VideoCreatedEvent::dispatch($video);


    }
}
