<?php

namespace Database\Factories;

use App\Models\EducationLevelGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationLevelGroup>
 */
class EducationLevelGroupFactory extends Factory
{
    protected $model = EducationLevelGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'مجموعة '.$this->faker->unique()->numerify('####'),
        ];
    }
}
