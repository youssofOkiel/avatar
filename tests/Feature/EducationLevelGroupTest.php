<?php

use App\Models\EducationLevel;
use App\Models\EducationLevelGroup;
use App\Support\GroupedEducationLevels;

test('education levels are grouped by seeded groups', function () {
    $secondary = EducationLevelGroup::factory()->create(['name' => 'المرحلة الثانوية']);
    $preparatory = EducationLevelGroup::factory()->create(['name' => 'المرحلة الإعدادية']);

    EducationLevel::factory()->create([
        'name' => 'الصف الأول الثانوي',
        'education_level_group_id' => $secondary->id,
    ]);
    EducationLevel::factory()->create([
        'name' => 'الصف الاول الاعدادي',
        'education_level_group_id' => $preparatory->id,
    ]);

    $groups = GroupedEducationLevels::grouped();

    expect($groups)->toHaveCount(2);
    expect($groups[0]['name'])->toBe('المرحلة الثانوية');
    expect($groups[1]['name'])->toBe('المرحلة الإعدادية');
    expect($groups[0]['levels'])->toHaveCount(1);
    expect($groups[1]['levels'])->toHaveCount(1);
});
