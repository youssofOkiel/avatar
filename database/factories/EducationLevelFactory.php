<?php

namespace Database\Factories;

use App\Models\EducationLevel;
use App\Models\EducationLevelGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationLevel>
 */
class EducationLevelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'مرحلة '.$this->faker->unique()->numerify('####'),
            'education_level_group_id' => EducationLevelGroup::factory(),
        ];
    }
}
