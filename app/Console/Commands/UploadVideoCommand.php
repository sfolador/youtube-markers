<?php

namespace App\Console\Commands;

use App\Actions\CreateVideoFromFile;
use Google\Client;
use Google\Service\YouTube\VideoContentDetails;
use Google_Service_YouTube;
use Google_Service_YouTube_ContentRating;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class UploadVideoCommand extends Command
{
    protected $signature = 'video:upload {filename}';

    protected $description = 'Command description';

    public function handle(): void
    {
        $filename = $this->argument('filename');
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
        if ($client->isAccessTokenExpired() &&  Storage::exists('google-refresh-token.json')) {
            $client->fetchAccessTokenWithRefreshToken(Storage::get('google-refresh-token.json'));
            Storage::put('google-access-token.json', json_encode($client->getAccessToken()));
        }

        $title = text('Enter the title of the video');


        // Prepare the video and metadata
        $videoPath = Storage::path($filename);
        $description = 'Description of your video';
        $category = 22; // Example category ID (Entertainment)
        $tags = ['tag1', 'tag2'];

// Create a new YouTube service instance
        $youtube = new Google_Service_YouTube($client);

        $categoryResponse = ($youtube->videoCategories->listVideoCategories('snippet', ['regionCode' => 'US']));

        $items = $categoryResponse->getItems();

        $items = collect($items)->map(function($item){
            $iClass = new \stdClass();
            $iClass->id = $item->id;
            $iClass->title = $item->getSnippet()->getTitle();
            return $iClass;
        });

        $category = select('Select a category', $items->pluck('title', 'id')->toArray());

// Create a snippet object with metadata
        $snippet = new Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($title);
        $snippet->setDescription($description);
        $snippet->setCategoryId($category);



        // Set video status (public, unlisted, private)
        $status = new Google_Service_YouTube_VideoStatus();
        $status->privacyStatus = 'private'; // Change to 'public' or 'unlisted' as needed
        $status->setMadeForKids(false);
        $status->setSelfDeclaredMadeForKids(false);

        // Create a content rating object

// Create the YouTube video object
        $video = new Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);




// Create an upload request
        $response = $youtube->videos->insert('snippet,status', $video, array(
            'data' => file_get_contents($videoPath),
            'mimeType' => 'video/*',
        ));


        // Output the uploaded video ID
        if (isset($response['id'])) {
            echo "Video uploaded successfully. Video ID: " . $response['id'];

            //create video
            $video = CreateVideoFromFile::execute($filename, $response['id']);

            Artisan::call('video:extract-audio ' . $video->id);
            Artisan::call('audio:transcribe ' . $video->id);
            Artisan::call('video:generate-texts ' . $video->id);
            Artisan::call('youtube:update ' . $video->id);

        } else {
            echo "Failed to upload video.";
        }

    }
}
