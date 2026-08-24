<?php

namespace App\Support;

use App\Models\EducationLevelGroup;
use Illuminate\Support\Collection;

class GroupedEducationLevels
{
    /**
     * @return array<int, array{id: int, name: string, levels: array<int, array{id: int, name: string}>}>
     */
    public static function grouped(): array
    {
        return EducationLevelGroup::query()
            ->with(['levels' => fn ($query) => $query->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (EducationLevelGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'levels' => $group->levels->map(fn ($level): array => [
                    'id' => $level->id,
                    'name' => $level->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, levels: array<int, array{id: int, name: string, subjects: array<int, array{id: int, name: string}>}>}>
     */
    public static function groupedWithSubjects(): array
    {
        return EducationLevelGroup::query()
            ->with(['levels' => fn ($query) => $query->with('subjects:id,name')->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (EducationLevelGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'levels' => $group->levels->map(fn ($level): array => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'subjects' => $level->subjects->map(fn ($subject): array => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Flat list of levels for backwards-compatible consumers.
     *
     * @return Collection<int, array{id: int, name: string, education_level_group_id: int|null}>
     */
    public static function flat(): Collection
    {
        return collect(self::grouped())->flatMap(
            fn (array $group): array => array_map(
                fn (array $level): array => [
                    ...$level,
                    'education_level_group_id' => $group['id'],
                    'group_name' => $group['name'],
                ],
                $group['levels'],
            ),
        )->values();
    }
}
