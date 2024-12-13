<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => VideoStatus::class
    ];

    public function addTranscription(string $transcription): self
    {
        $this->transcription = $transcription;
        $this->status = VideoStatus::AudioTranscribed;
        $this->save();

        return $this;
    }

    public function addDescription(string $description): self
    {
        $this->description = $description;
        $this->status = VideoStatus::DescriptionReady;
        $this->save();

        return $this;
    }
}
