<?php

namespace Database\Seeders;

use Carbon\CarbonInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AvatarDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = now();
        $data = $this->loadData();

        $groupIds = collect($data['education_level_groups'])->pluck('id')->all();
        $missingGroups = collect($data['education_levels'])
            ->pluck('education_level_group_id')
            ->filter(fn (?int $id): bool => $id !== null && ! in_array($id, $groupIds, true))
            ->unique()
            ->values()
            ->all();

        if ($missingGroups !== []) {
            throw new RuntimeException(
                'education_levels reference unknown education_level_group_id values: '
                .implode(', ', $missingGroups),
            );
        }

        DB::table('rooms')->insert(
            array_map(fn (string $name): array => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ], $data['rooms'])
        );

        DB::table('education_level_groups')->insert(
            $this->withTimestamps($data['education_level_groups'], $now)
        );

        DB::table('education_levels')->insert(
            $this->withTimestamps($data['education_levels'], $now)
        );

        DB::table('subjects')->insert(
            $this->withTimestamps($data['subjects'], $now)
        );

        DB::table('subject_education_level')->insert(
            $this->withTimestamps($data['subject_education_level'], $now)
        );

        DB::table('teachers')->insert(
            $this->withTimestamps(array_map(fn (array $teacher): array => [
                'id' => $teacher['id'],
                'name' => $teacher['name'],
                'phone' => $teacher['phone'],
                'bio' => $teacher['bio'] ?? null,
                'is_active' => ($teacher['is_active'] ?? true) ? 1 : 0,
            ], $data['teachers']), $now)
        );

        // teacher_subject has no timestamp columns.
        DB::table('teacher_subject')->insert($data['teacher_subject']);

        DB::table('teacher_schedules')->insert(
            $this->withTimestamps(array_map(fn (array $schedule): array => [
                'teacher_id' => $schedule['teacher_id'],
                'education_level_id' => $schedule['education_level_id'],
                'subject_id' => $schedule['subject_id'],
                'room_id' => $schedule['room_id'] ?? null,
                'day_of_week' => $schedule['day_of_week'],
                'starts_at' => $schedule['starts_at'],
                'ends_at' => $schedule['ends_at'],
            ], $data['teacher_schedules']), $now)
        );
    }

    /**
     * Load the imported dataset from the JSON file under storage.
     *
     * @return array<string, array<int, array<string, mixed>>|array<int, string>>
     */
    private function loadData(): array
    {
        $path = storage_path('app/seed/avatar-data.json');

        if (! is_file($path)) {
            throw new RuntimeException("Seed data file not found at [{$path}].");
        }

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * Append identical created_at/updated_at timestamps to each row.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function withTimestamps(array $rows, CarbonInterface $now): array
    {
        return array_map(fn (array $row): array => [
            ...$row,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);
    }
}
