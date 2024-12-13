<?php

namespace App\Listeners;

use App\Events\AudioExtractedEvent;
use App\Events\AudioTranscribedEvent;
use App\Events\ChaptersDescriptionGeneratedEvent;
use App\Events\VideoCreatedEvent;
use App\Events\VideoUploadedEvent;
use App\Jobs\ExtractAudioFromVideoJob;
use App\Jobs\GenerateVideoTextsJob;
use App\Jobs\TranscribeAudioJob;
use App\Jobs\UpdateVideoTextsOnYoutubeJob;
use App\Jobs\UploadToYoutubeJob;
use Illuminate\Events\Dispatcher;

class VideoListener
{
    public function __construct()
    {
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            VideoCreatedEvent::class => 'onVideoCreated',
            AudioExtractedEvent::class => 'onAudioExtracted',
            AudioTranscribedEvent::class => 'onAudioTranscribed',
            ChaptersDescriptionGeneratedEvent::class => 'onChaptersDescriptionGenerated',
            VideoUploadedEvent::class => 'onVideoUploaded'

        ];
    }


    public function onVideoCreated(VideoCreatedEvent $event): void
    {
        logger('Video created event received');
        //launch the audio extraction process
        dispatch(new ExtractAudioFromVideoJob($event->video));

    }

    public function onAudioExtracted(AudioExtractedEvent $event): void
    {
        logger('Audio extracted event received');
         dispatch(new TranscribeAudioJob($event->video));

    }

    public function onAudioTranscribed(AudioTranscribedEvent $event): void
    {
        logger('onAudioTranscribed');
        dispatch(new GenerateVideoTextsJob($event->video));

    }

    public function onVideoUploaded(VideoUploadedEvent $event)
    {
        dispatch(new UpdateVideoTextsOnYoutubeJob($event->video));
    }

    public function onChaptersDescriptionGenerated(ChaptersDescriptionGeneratedEvent $event): void
    {
        logger('onChaptersDescriptionGenerated');

        dispatch(new UploadToYoutubeJob($event->video));

    }
}
