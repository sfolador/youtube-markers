<?php

namespace App\Services;

use App\Actions\PrepareFfmpeg;
use App\Actions\PrepareMlxWhisper;
use App\Enums\VideoStatus;
use App\Events\AudioExtractedEvent;
use App\Events\AudioTranscribedEvent;
use App\Events\ChaptersDescriptionGeneratedEvent;
use App\Events\VideoUpdatedEvent;
use App\Facades\Youtube;
use App\Models\Video;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoService
{
    private string $model = 'claude-3-5-sonnet-latest';

    const DEFAULT_AUDIO_EXTENSION = 'wav';

    /**
     * @throws \Exception
     */
    public function extractAudio($video): Video
    {

        PrepareFfmpeg::execute();


        $videoFile = \Storage::path($video->filename);
        $audioFile = \Storage::path($this->generateAudioFilename($video->filename));

        $result = Process::run("ffmpeg -i $videoFile  -acodec pcm_s16le -ac 1 -ar 16000 $audioFile");

        if ($result->failed()) {
            throw new \Exception($result->errorOutput());
        }

        $video->status = VideoStatus::AudioExtracted;
        $video->save();

        AudioExtractedEvent::dispatch($video);

        return $video;
    }

    /**
     * @throws \Exception
     */
    public function transcribeAudio(Video $video): Video
    {
        PrepareMlxWhisper::execute();


        $audioFilename = \Storage::path($this->generateAudioFilename($video->filename));

        $mlxCommand = "mlx_whisper $audioFilename --model \"mlx-community/whisper-medium-mlx\" --output-format srt --output-dir " . storage_path('app/private');

        $result = Process::tty()->run([
            '/bin/bash',
            '-c',
            'source mlx-whisper/bin/activate && ' . $mlxCommand
        ]);


        if ($result->failed()) {
            throw new \Exception($result->errorOutput());
        }


        $srtFilename = $this->generateSubtitlesFilename($video->filename);

        // Read and parse SRT file
        $srtContent = $this->getContentsOfSubtitleFile($srtFilename);

        $video->addTranscription($srtContent);

        AudioTranscribedEvent::dispatch($video);
        return $video;
    }


    public function generateVideoTexts(Video $video): Video
    {

        try {

            $srtFilename = $this->generateSubtitlesFilename($video->filename);

            // Read and parse SRT file
            $srtContent = $this->getContentsOfSubtitleFile($srtFilename);

            // Get arguments from Claude
            $chapters = $this->getChapters($srtContent);


            try {

                $description = $this->generateDescription($srtContent);
            } catch (Exception $e) {
                logger()->error('Video marker processing failed:', [
                    'error' => $e->getMessage(),
                    'videoId' => $video->youtube_id,
                    'srtPath' => $srtFilename
                ]);

                throw $e;
            }


            logger()->info('Arguments and description generated:', [
                'arguments' => $chapters,
                'description' => $description
            ]);


            $timestampText = "";
            foreach ($chapters as $chapter) {
                if (!is_array($chapter)) {
                    $chapter = (array)$chapter;
                }
                $timestampText .= "{$chapter['timestamp']} - {$chapter['title']}\n";
            }

            $video->addDescription($description . "\n\n" . $timestampText);


        } catch (Exception $e) {
            logger()->error('Video marker processing failed:', [
                'error' => $e->getMessage(),
                'videoId' => $video->youtube_id,
                'srtPath' => $srtFilename
            ]);

            throw $e;
        }


        ChaptersDescriptionGeneratedEvent::dispatch($video);

        return $video;
    }

    /**
     * Call Claude API to analyze SRT content
     * @throws PrismException
     */
    protected function getChapters(string $srtContent): array
    {
        $userMessage = new UserMessage(content: view('prompts.srt-user-message')->with('srtContent', $srtContent));
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


        $response = Prism::structured()
            ->using(Provider::Anthropic, $this->model)
            ->withMessages([$userMessage, $assistantMessage])
            ->withSchema($schema)
            ->withSystemPrompt(view('prompts.chapters'))
            ->generate();


        return json_decode(Str::trim($response->text));

    }

    /**
     * @throws PrismException
     */
    protected function generateDescription(string $srtContent): string
    {
        $response = Prism::text()
            ->using(Provider::Anthropic, $this->model)
            ->withPrompt(view('prompts.srt-user')->with('srtContent', $srtContent))
            ->withSystemPrompt(view('prompts.srt-system'))
            ->withMaxTokens(300)
            ->generate();


        return Str::trim($response->text);
    }

    public function updateVideo($video)
    {
        // Update video
        // Dispatch VideoUpdatedEvent

        Youtube::updateVideo($video);

        $video->status = VideoStatus::YoutubeUpdated;
        $video->save();

        VideoUpdatedEvent::dispatch($video);
    }

    protected function getFileExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    protected function generateAudioFilename(string $videoFilename): string
    {

        $ext = $this->getFileExtension($videoFilename);
        return str_replace($ext, self::DEFAULT_AUDIO_EXTENSION, $videoFilename);
    }

    protected function generateSubtitlesFilename(string $audioFilename): string
    {
        $ext = $this->getFileExtension($audioFilename);
        return str_replace($ext, 'srt', $audioFilename);
    }

    /**
     * @throws Exception
     */
    protected function getContentsOfSubtitleFile(string $filename): string
    {
        if (!Storage::exists($filename)) {
            throw new Exception("SRT file not found at path: {$filename}");
        }
        return Storage::get($filename);
    }
}
