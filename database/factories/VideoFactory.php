<?php

namespace Database\Factories;

use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'filename' => $this->faker->word(),
            'transcription' => $this->faker->word(),
            'status' => $this->faker->randomElement(VideoStatus::cases()),
            'description' => $this->faker->text(),
            'youtube_id' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
