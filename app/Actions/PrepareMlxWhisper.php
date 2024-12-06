<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;

class PrepareMlxWhisper
{
    /**
     * @throws \Exception
     */
    public static function execute(string $pythonCommand = 'python3.9')
    {

       $result =   Process::run([
           $pythonCommand,
            '--version'
        ]);


        if ($result->failed()) {
            throw new \Exception('Python3.9 is not installed');
        }

        //create venv
        $result = Process::run([
            $pythonCommand,
            '-m',
            'venv',
            'mlx-whisper'
        ]);

        if ($result->failed()) {
            throw new \Exception('Failed to create Venv');
        }

        $result = Process::tty()->run([
            'bash',
            '-c',
            'source mlx-whisper/bin/activate && pip install mlx-whisper'
        ]);

        if ($result->failed()) {
            throw new \Exception('Failed to install mlx-whisper');
        }

    }
}
