<?php

namespace Database\Factories;

use App\Models\ExternalLecturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalLecturer>
 */
class ExternalLecturerFactory extends Factory
{
    protected $model = ExternalLecturer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
