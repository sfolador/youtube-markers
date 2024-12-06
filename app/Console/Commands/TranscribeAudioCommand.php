<?php

namespace App\Console\Commands;

use App\Actions\PrepareMlxWhisper;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class TranscribeAudioCommand extends Command
{
    protected $signature = 'audio:transcribe {video_id}';

    protected $description = 'Command description';

    public function handle(): void
    {

        PrepareMlxWhisper::execute();

        $videoId = $this->argument('video_id');
        $videoFile = Video::find($videoId)->filename;

        //get the extension of the video file
        $ext = pathinfo($videoFile, PATHINFO_EXTENSION);

        //remove the extension and put wav
        $audioFile = \Storage::path(str_replace($ext, 'wav', $videoFile));
        $srtFile = \Storage::path(str_replace($ext, 'srt', $videoFile));

        $this->info('Transcribing audio...');


        $mlxCommand = "mlx_whisper $audioFile --model \"mlx-community/whisper-medium-mlx\" --output-format srt ";

        $result = Process::tty()->run([
            '/bin/bash',
            '-c',
            'source mlx-whisper/bin/activate && ' . $mlxCommand
        ]);


        if ($result->failed()){
            $this->error('Failed to extract audio from video');
            $this->error($result->errorOutput());
            return;
        }

        $this->info('SRT file ready, you can find it at: ' . $srtFile);
    }
}
