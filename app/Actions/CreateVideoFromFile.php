<?php

namespace App\Actions;

use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Support\Facades\Process;

class CreateVideoFromFile
{
    /**
     * @throws \Exception
     */
    public static function execute(string $filename, string $title): Video
    {

        $video = new Video();
        $video->title = $title;
        $video->filename = $filename;
        $video->status = VideoStatus::New;
        $video->save();


        return $video;
    }
}
