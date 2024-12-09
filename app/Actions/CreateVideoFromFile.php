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
    public static function execute($filename): Video
    {

        $video = new Video();
        $video->title = $filename;
        $video->filename = $filename;
        $video->status = VideoStatus::New;
        $video->save();


        return $video;
    }
}
