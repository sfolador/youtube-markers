<?php

namespace App\Enums;

enum VideoStatus: string
{
    case New = 'new';
    case AudioTranscribed = 'audio_transcribed';
    case Uploaded = 'uploaded';
    case AudioExtracted = 'audio_extracted';

    case DescriptionReady = 'description_ready';

    case YoutubeUpdated = 'youtube_updated';
}
