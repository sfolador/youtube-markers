<?php

namespace App\Console\Commands;

use App\Enums\VideoStatus;
use App\Jobs\ProcessVideoMarkersJob;
use App\Models\Video;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Exception;
use Google\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateTextsCommand extends Command
{
    protected $signature = 'video:generate-texts {video_id}';

    protected $description = 'Command description';

    protected Video $video;

    protected string $model ='claude-3-5-sonnet-latest';//'claude-3-5-haiku-latest'; //'claude-3-5-sonnet-latest';

    public function handle()
    {
        $videoId = $this->argument('video_id');
        $this->video = Video::find($videoId);

        try {

            $videoFilename = $this->video->filename;
            $ext = pathinfo($videoFilename, PATHINFO_EXTENSION);
            $srtFilename = str_replace($ext, 'srt', $videoFilename);

            // Read and parse SRT file
            $srtContent = Storage::get($srtFilename);
            if (!$srtContent) {
                throw new Exception("SRT file not found at path: {$srtFilename}");
            }

            $this->info("Generating chapters");
            // Get arguments from Claude
            $arguments = $this->getArgumentsFromClaude($srtContent);


            try{

                $this->info("Generating description");
                $description = $this->generateDescription($srtContent);
            }catch (Exception $e){
                logger()->error('Video marker processing failed:', [
                    'error' => $e->getMessage(),
                    'videoId' => $this->video->youtube_id,
                    'srtPath' => $srtFilename
                ]);

                throw $e;
            }


            logger()->info('Arguments and description generated:', [
                'arguments' => $arguments,
                'description' => $description
            ]);




            $timestampText = "";
            foreach ($arguments as $argument) {
                if (!is_array($argument)){
                    $argument = (array) $argument;
                }
                $timestampText .= "{$argument['timestamp']} - {$argument['title']}\n";
            }

            $this->video->description = $description . "\n\n" .  $timestampText;
            $this->video->status = VideoStatus::DescriptionReady;
            $this->video->save();

            $this->info("Description and chapters saved");

        } catch (Exception $e) {
            logger()->error('Video marker processing failed:', [
                'error' => $e->getMessage(),
                'videoId' => $this->video->youtube_id,
                'srtPath' => $srtFilename
            ]);

            throw $e;
        }
    }

    /**
     * Call Claude API to analyze SRT content
     */
    protected function getArgumentsFromClaude(string $srtContent): array
    {
        $userMessage = new UserMessage(content: "From this SRT file, detect the main arguments and their timestamps. Detect maximum 10 arguments and propose only a reasonable number of them. Return them in a JSON format with title and timestamp. Here's the SRT content:\n\n{$srtContent}");
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


        $response =  Prism::structured()
            ->using(Provider::Anthropic, $this->model)
            ->withMessages([$userMessage,$assistantMessage])
            ->withSchema($schema)
            ->withSystemPrompt("You are an expert at analyzing video transcripts and identifying main arguments.
             Always return your response in valid JSON format with an array of objects containing 'title' and 'timestamp' keys.
             The title MUST be in the srt language.
             The JSON MUST be in this form:
             [
                {
                    \"title\": \"Argument 1\",
                    \"timestamp\": \"00:00\"
                },
                 {
                    \"title\": \"Argument 2\",
                    \"timestamp\": \"00:30\"
                }
             ]")
            ->generate();



        return json_decode(Str::trim($response->text));

    }

    protected function generateDescription(string $srtContent): string
    {
        $response =  Prism::text()
            ->using(Provider::Anthropic, $this->model)
            ->withPrompt("From this SRT file, generate a description for the video. The description must be in the same language as the SRT file. Here's the SRT content:\n\n{$srtContent}")
            ->withSystemPrompt("You are an expert at analyzing video transcripts and identifying main arguments. Do not talk in third person, use the I form. Be concise and clear and friendly.
             Always return your response in the same language as the SRT file. The response must be in Markdown compatible with YouTube. Just return the description without anything else. ")
            ->withMaxTokens(300)
            ->generate();


        return Str::trim($response->text);
    }




}
