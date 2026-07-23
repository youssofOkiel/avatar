<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ClassSession;
use App\Models\EducationLevel;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->create([
            'name' => 'المشرف',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $first = EducationLevel::query()->create(['name' => 'الصف الأول الثانوي']);
        $second = EducationLevel::query()->create(['name' => 'الصف الثاني الثانوي']);
        $third = EducationLevel::query()->create(['name' => 'الصف الثالث الثانوي']);

        $math = Subject::query()->create(['name' => 'الرياضيات']);
        $math->educationLevels()->attach([$first->id, $second->id, $third->id]);

        $physics = Subject::query()->create(['name' => 'الفيزياء']);
        $physics->educationLevels()->attach([$second->id, $third->id]);

        $chemistry = Subject::query()->create(['name' => 'الكيمياء']);
        $chemistry->educationLevels()->attach([$second->id, $third->id]);

        $arabic = Subject::query()->create(['name' => 'اللغة العربية']);
        $arabic->educationLevels()->attach([$first->id, $second->id, $third->id]);

        $teacher = Teacher::query()->create([
            'name' => 'أحمد محمود',
            'bio' => 'مدرس رياضيات وفيزياء بخبرة عشر سنوات.',
            'is_active' => true,
        ]);
        $teacher->teacherSubjects()->createMany([
            ['education_level_id' => $first->id, 'subject_id' => $math->id],
            ['education_level_id' => $second->id, 'subject_id' => $math->id],
            ['education_level_id' => $second->id, 'subject_id' => $physics->id],
        ]);
        $teacher->schedules()->createMany([
            ['education_level_id' => $first->id, 'subject_id' => $math->id, 'day_of_week' => 0, 'starts_at' => '16:00', 'ends_at' => '17:30'],
            ['education_level_id' => $second->id, 'subject_id' => $math->id, 'day_of_week' => 3, 'starts_at' => '16:00', 'ends_at' => '17:30'],
            ['education_level_id' => $second->id, 'subject_id' => $physics->id, 'day_of_week' => 1, 'starts_at' => '18:00', 'ends_at' => '19:30'],
        ]);

        $teacher2 = Teacher::query()->create([
            'name' => 'سارة علي',
            'bio' => 'مدرسة لغة عربية.',
            'is_active' => true,
        ]);
        $teacher2->teacherSubjects()->create([
            'education_level_id' => $first->id, 'subject_id' => $arabic->id,
        ]);
        $teacher2->schedules()->create([
            'education_level_id' => $first->id, 'subject_id' => $arabic->id, 'day_of_week' => 2, 'starts_at' => '17:00', 'ends_at' => '18:30',
        ]);

        $student1 = Student::query()->create(['name' => 'محمد إبراهيم', 'phone' => '01000000001']);
        $student2 = Student::query()->create(['name' => 'ليلى حسن', 'phone' => '01000000002']);

        $exceptionalSession = ClassSession::query()->create([
            'teacher_id' => $teacher->id,
            'subject_id' => $math->id,
            'starts_at' => now()->addDays(2)->setTime(19, 0),
            'ends_at' => now()->addDays(2)->setTime(20, 30),
        ]);
        $exceptionalSession->students()->attach([$student1->id, $student2->id]);

        $mathScheduleFirst = $teacher->schedules()
            ->where('education_level_id', $first->id)
            ->where('subject_id', $math->id)
            ->pluck('id');
        $arabicSchedule = $teacher2->schedules()->pluck('id');

        $reservation1 = Reservation::query()->create([
            'student_id' => $student1->id,
            'education_level_id' => $first->id,
            'subject_id' => $math->id,
            'teacher_id' => $teacher->id,
        ]);
        $reservation1->teacherSchedules()->sync($mathScheduleFirst);

        $reservation2 = Reservation::query()->create([
            'student_id' => $student2->id,
            'education_level_id' => $first->id,
            'subject_id' => $arabic->id,
            'teacher_id' => $teacher2->id,
        ]);
        $reservation2->teacherSchedules()->sync($arabicSchedule);
    }
}
