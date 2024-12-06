<?php

namespace App\Console\Commands;

use App\Actions\CreateVideoFromFile;
use Illuminate\Console\Command;

class CreateVideoCommand extends Command
{
    protected $signature = 'video:create {filename} {youtube_id}';

    protected $description = 'Command description';

    public function handle(): void
    {
        CreateVideoFromFile::execute($this->argument('filename'), $this->argument('youtube_id'));
        $this->info('Video created successfully');
    }
}
