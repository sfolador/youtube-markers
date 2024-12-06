<?php

namespace App\Jobs;

use App\Models\Video;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Exception;
use Google\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessVideoMarkersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected string $video;
    protected int $maxRetries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $videoFilename = $this->video->filename;
            $ext = pathinfo($videoFilename, PATHINFO_EXTENSION);
            $srtFilename = str_replace($ext, 'srt', $videoFilename);

            // Read and parse SRT file
            $srtContent = Storage::get($srtFilename);
            if (!$srtContent) {
                throw new Exception("SRT file not found at path: {$srtFilename}");
            }

            // Get arguments from Claude
            $arguments = $this->getArgumentsFromClaude($srtContent);

           try{
               $description = $this->generateDescription($srtContent);
           }catch (Exception $e){
                logger()->error('Video marker processing failed:', [
                     'error' => $e->getMessage(),
                     'videoId' => $this->video->youtube_id,
                     'srtPath' => $srtFilename
                ]);

                throw $e;
           }


            // Set YouTube markers
            $this->setYouTubeMarkers($arguments,$description);

            // Clean up
            //  Storage::delete($this->srtPath);

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

        $response =  Prism::text()
            ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
            ->withMessages([$userMessage,$assistantMessage])
            ->withSystemPrompt("You are an expert at analyzing video transcripts and identifying main arguments.
             Always return your response in valid JSON format with an array of objects containing 'title' and 'timestamp' keys.
             The title must be in the srt language.
             The JSON must be in this form:
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
            ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
            ->withPrompt("From this SRT file, generate a description for the video. The description must be in the same language as the SRT file. Here's the SRT content:\n\n{$srtContent}")
            ->withSystemPrompt("You are an expert at analyzing video transcripts and identifying main arguments. Do not talk in third person, use the I form. Be concise and clear and friendly.
             Always return your response in the same language as the SRT file. The response must be in Markdown compatible with YouTube. Just return the description without anything else. ")
            ->withMaxTokens(300)
            ->generate();


        return Str::trim($response->text);
    }

    /**
     * Set markers in YouTube video using API
     * @throws \Google\Exception
     */
    protected function setYouTubeMarkers(array $arguments,string $description): void
    {
        $client = new Client();
        $client->setAuthConfig(Storage::path('google-credentials.json'));
        $client->setDeveloperKey(config('services.google.api_key')); // Add this line

        $client->addScope(\Google_Service_YouTube::YOUTUBE_FORCE_SSL);

        // Load previously stored access token
        if (Storage::exists('google-access-token.json')) {
            $accessToken = json_decode(Storage::get('google-access-token.json'), true);
            $client->setAccessToken($accessToken);
        }

        // If token is expired, refresh it
        if ($client->isAccessTokenExpired() &&  Storage::exists('google-refresh-token.json')) {
            $client->fetchAccessTokenWithRefreshToken(Storage::get('google-refresh-token.json'));
            Storage::put('google-access-token.json', json_encode($client->getAccessToken()));
        }

        $youtube = new \Google_Service_YouTube($client);


        try {
            // Get current video details
            $video = $youtube->videos->listVideos('snippet', ['id' => $this->videoId]);

            if (empty($video->items)) {
                throw new Exception('Video not found');
            }

            $videoSnippet = $video->items[0]->getSnippet();


            // Format timestamps for YouTube
            $timestampText = "";
            foreach ($arguments as $argument) {
                $timestampText .= "{$argument['timestamp']} - {$argument['title']}\n";
            }

            // Combine existing description with timestamps
            $newDescription = $description . "\n\n" .  $timestampText;

            // Create update request
            $updateVideo = new \Google_Service_YouTube_Video();
            $updateVideo->setId($this->videoId);  // Add video ID

            $videoSnippet->setDescription($newDescription);
            $updateVideo->setSnippet($videoSnippet);

            $youtube->videos->update('snippet', $updateVideo);

        } catch (Exception $e) {
            logger()->error('Failed to update YouTube video:', [
                'error' => $e->getMessage(),
                'videoId' => $this->videoId,
            ]);
            throw $e;
        }
    }

    /**
     * Convert SRT timestamp format (00:00:00,000) to seconds
     */
    protected function convertTimeToSeconds(string $timestamp): int
    {
        $parts = explode(':', str_replace(',', '.', $timestamp));
        return $parts[0] * 3600 + $parts[1] * 60 + (int)$parts[2];
    }
}
