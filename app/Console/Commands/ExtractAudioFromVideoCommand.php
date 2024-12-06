<?php

namespace App\Console\Commands;

use App\Actions\PrepareFfmpeg;
use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class ExtractAudioFromVideoCommand extends Command
{
    protected $signature = 'video:extract-audio {video_id}';

    protected $description = 'Command description';

    protected Video $video;

    public function handle(): void
    {
        try {
            PrepareFfmpeg::execute();
        }catch (\Exception $exception){
            $this->error($exception->getMessage());
            return;
        }

        $this->video =  Video::find($this->argument('video_id'));
        $videoFilename =   $this->video->filename;
        $ext = pathinfo($videoFilename, PATHINFO_EXTENSION);

        $videoFile  = \Storage::path($videoFilename);
        $audioFile = \Storage::path(str_replace($ext, 'wav', $videoFilename));

        $result = Process::run("ffmpeg -i $videoFile  -acodec pcm_s16le -ac 1 -ar 16000 $audioFile");

        if ($result->failed()){
            $this->error('Failed to extract audio from video');
            $this->error($result->errorOutput());
            return;
        }


        $this->video->status = VideoStatus::AudioExtracted;
        $this->video->save();

        $this->info('Audio extracted successfully');
    }
}
