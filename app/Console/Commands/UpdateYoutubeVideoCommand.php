<?php

namespace App\Console\Commands;

use App\Actions\CreateVideoFromFile;
use App\Enums\VideoStatus;
use App\Models\Video;
use Exception;
use Google\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UpdateYoutubeVideoCommand extends Command
{
    protected $signature = 'youtube:update {video_id}';

    protected $description = 'Command description';

    protected Video $video;

    public function handle(): void
    {
        $videoId = $this->argument('video_id');
        $this->video = Video::find($videoId);

        $this->info('Updating video: ' . $this->video->title);

        $this->info("Calling YouTube API");

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
            $video = $youtube->videos->listVideos('snippet', ['id' => $this->video->youtube_id]);

            if (empty($video->items)) {
                throw new Exception('Video not found');
            }

            $videoSnippet = $video->items[0]->getSnippet();



            // Combine existing description with timestamps
            $newDescription = $this->video->description;

            // Create update request
            $updateVideo = new \Google_Service_YouTube_Video();
            $updateVideo->setId($this->video->youtube_id);  // Add video ID

            $videoSnippet->setDescription($newDescription);
            $updateVideo->setSnippet($videoSnippet);

            $youtube->videos->update('snippet', $updateVideo);

        } catch (Exception $e) {
            logger()->error('Failed to update YouTube video:', [
                'error' => $e->getMessage(),
                'videoId' => $this->video->youtube_id,
            ]);
            throw $e;
        }

        $this->info("Description and chapters set on Youtube, check the video at https://youtube.com/watch?v={$this->video->youtube_id}");
        $this->video->status = VideoStatus::YoutubeUpdated;
        $this->video->save();

    }
}
