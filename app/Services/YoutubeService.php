<?php

namespace App\Services;

use App\Enums\VideoStatus;
use App\Models\Video;
use Exception;
use Google\Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\text;

class YoutubeService
{
    private Client $client;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(Storage::path('google-credentials.json'));
        $client->setDeveloperKey(config('services.google.api_key')); // Add this line

        $client->addScope(\Google_Service_YouTube::YOUTUBE_FORCE_SSL);

        $client->addScope(\Google_Service_YouTube::YOUTUBE_FORCE_SSL);

        // Load previously stored access token
        if (Storage::exists('google-access-token.json')) {
            $accessToken = json_decode(Storage::get('google-access-token.json'), true);
            $client->setAccessToken($accessToken);
        }

        // If token is expired, refresh it
        if ($client->isAccessTokenExpired() && Storage::exists('google-refresh-token.json')) {
            $client->fetchAccessTokenWithRefreshToken(Storage::get('google-refresh-token.json'));
            Storage::put('google-access-token.json', json_encode($client->getAccessToken()));
        }

        // if tokens are not present, prompt the user to authenticate.
        //@todo: Implement this


        $this->client = $client;

    }

    public function uploadVideo(Video $video): \Google\Service\YouTube\Video
    {


        // Prepare the video and metadata
        $videoPath = Storage::path($video->filename);
        $description = 'Description of your video';

// Create a new YouTube service instance
        $youtube = new Google_Service_YouTube($this->client);


// Create a snippet object with metadata
        $snippet = new Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($video->title);
        $snippet->setDescription($video->description);
        $snippet->setCategoryId($video->category);


        // Set video status (public, unlisted, private)
        $status = new Google_Service_YouTube_VideoStatus();
        $status->privacyStatus = 'private'; // Change to 'public' or 'unlisted' as needed
        $status->setMadeForKids(false);
        $status->setSelfDeclaredMadeForKids(false);

        // Create a content rating object

// Create the YouTube video object
        $youtubeVideo = new Google_Service_YouTube_Video();
        $youtubeVideo->setSnippet($snippet);
        $youtubeVideo->setStatus($status);


// Create an upload request
        $response = $youtube->videos->insert('snippet,status', $youtubeVideo, array(
            'data' => file_get_contents($videoPath),
            'mimeType' => 'video/*',
        ));

        $video->youtube_id = $response['id'];
        $video->status = VideoStatus::Uploaded;
        $video->save();

        return $response;
    }

    public function updateVideo(Video $video)
    {
        // Create a new YouTube service instance
        $youtube = new Google_Service_YouTube($this->client);
        try {
            // Get current video details
            $youtubeVideo = $youtube->videos->listVideos('snippet', ['id' => $video->youtube_id]);

            if (empty($youtubeVideo->items)) {
                throw new Exception('Video not found');
            }

            $videoSnippet = $youtubeVideo->items[0]->getSnippet();


            // Combine existing description with timestamps
            $newDescription = $video->description;

            // Create update request
            $updateVideo = new \Google_Service_YouTube_Video();
            $updateVideo->setId($video->youtube_id);  // Add video ID

            $videoSnippet->setDescription($newDescription);
            $updateVideo->setSnippet($videoSnippet);

            $youtube->videos->update('snippet', $updateVideo);

        } catch (Exception $e) {
            logger()->error('Failed to update YouTube video:', [
                'error' => $e->getMessage(),
                'videoId' => $video->youtube_id,
            ]);
            throw $e;
        }

    }
}
