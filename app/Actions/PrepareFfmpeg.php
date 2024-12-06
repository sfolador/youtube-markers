<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class PrepareFfmpeg
{
    /**
     * @throws \Exception
     */
    public static function execute()
    {

        $result = Process::run([
            'bash',
            '-c',
            'which ffmpeg',
        ]);


        if (blank($result->output())) {


            $result = Process::run([
                'brew install ffmpeg',
            ]);


            if ($result->failed()) {
                throw new \Exception('Error installing ffmpeg');
            }
        }


    }
}
