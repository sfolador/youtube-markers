<?php

namespace App\Console\Commands;

use App\Actions\CreateVideoFromFile;
use App\Enums\VideoCategory;
use App\Events\VideoCreatedEvent;
use App\Jobs\ProcessVideoMarkersJob;
use App\Jobs\UploadVideoJob;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use function Laravel\Prompts\text;

class OllamaCommand extends Command
{
    protected $signature = 'ollama';

    protected $description = 'Command description';

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->info("Hi! Let's create a new video!");
        $value = Benchmark::value(function () {
            $userMessage = new UserMessage(content: view('prompts.srt-user-message')->with('srtContent', \Storage::get('video.srt')));
            $assistantMessage = new AssistantMessage(content: "here is the JSON:");

            $schema = new ObjectSchema(
                name: 'chapter',
                description: 'A structured chapter',
                properties: array(
                    new StringSchema('title', 'The chapter title'),
                    new StringSchema('timestamp', 'the timestamp of the chapter, like 00:10'),
                ),
                requiredFields: ['title', 'timestamp']
            );


            $response = Prism::text()
                ->using(Provider::Ollama, 'llama3.3:latest')
                ->withMessages([$userMessage, $assistantMessage])
                ->withSystemPrompt(view('prompts.chapters'))
                ->withClientOptions(['timeout' => 120])
                ->generate();

            return $response;
        });

        $this->info($value[0]->text);
        $this->info($value[1]);



    }
}
