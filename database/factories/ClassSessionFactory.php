<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    protected $model = ClassSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays($this->faker->numberBetween(1, 14))->setTime(16, 0);

        return [
            'teacher_id' => Teacher::factory(),
            'subject_id' => Subject::factory(),
            'room_id' => null,
            'type' => 'subject',
            'title' => null,
            'income' => $this->faker->randomFloat(2, 0, 500),
            'attendance_count' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
        ];
    }

    /**
     * A rental/external session unrelated to the center's subjects.
     */
    public function rental(): static
    {
        return $this->state(fn (): array => [
            'teacher_id' => null,
            'subject_id' => null,
            'type' => 'rental',
            'title' => $this->faker->sentence(3),
            'attendance_count' => $this->faker->numberBetween(5, 40),
        ]);
    }
}
