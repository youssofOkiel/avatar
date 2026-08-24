<?php

namespace Database\Factories;

use App\Models\ExternalLecturer;
use App\Models\ExternalLecturerSchedule;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalLecturerSchedule>
 */
class ExternalLecturerScheduleFactory extends Factory
{
    protected $model = ExternalLecturerSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_lecturer_id' => ExternalLecturer::factory(),
            'topic' => $this->faker->sentence(3),
            'room_id' => Room::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'starts_at' => '16:00',
            'ends_at' => '17:30',
            'income' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
