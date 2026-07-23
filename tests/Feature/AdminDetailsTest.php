<?php

use App\Models\ClassSession;
use App\Models\EducationLevel;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin can add an existing student to a session', function () {
    $session = ClassSession::factory()->create();
    $student = Student::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.students.store', $session), [
            'student_id' => $student->id,
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    $this->assertDatabaseHas('class_session_student', [
        'class_session_id' => $session->id,
        'student_id' => $student->id,
    ]);
});

test('admin can add a new student by name to a session', function () {
    $session = ClassSession::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.students.store', $session), [
            'name' => 'سارة علي',
            'phone' => '01011112222',
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    $student = Student::query()->where('name', 'سارة علي')->first();
    expect($student)->not->toBeNull();
    $this->assertDatabaseHas('class_session_student', [
        'class_session_id' => $session->id,
        'student_id' => $student->id,
    ]);
});

test('adding a student to a session requires name, phone, or student', function () {
    $session = ClassSession::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.students.store', $session), [])
        ->assertSessionHasErrors(['name', 'phone']);
});

test('admin can remove a student from a session', function () {
    $session = ClassSession::factory()->create();
    $student = Student::factory()->create();
    $session->students()->attach($student->id);

    $this->actingAs($this->admin)
        ->delete(route('admin.sessions.students.destroy', [$session, $student]))
        ->assertRedirect(route('admin.sessions.show', $session));

    $this->assertDatabaseMissing('class_session_student', [
        'class_session_id' => $session->id,
        'student_id' => $student->id,
    ]);
});

test('session generation route no longer exists', function () {
    expect(fn () => route('admin.sessions.generate'))->toThrow(\Exception::class);
});

test('admin can view a teacher details page with teaching data', function () {
    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();
    $teacher->teacherSubjects()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
    ]);
    $teacher->schedules()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'day_of_week' => 0,
        'starts_at' => '16:00',
        'ends_at' => '17:30',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.teachers.show', $teacher))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/teachers/show')
            ->has('teacher')
            ->has('teaching', 1)
            ->where('teaching.0.subject.name', $subject->name));
});

test('teachers index includes reservations count', function () {
    $teacher = Teacher::factory()->create();
    Reservation::factory()->count(2)->create(['teacher_id' => $teacher->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.teachers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/teachers/index')
            ->where('teachers.data.0.reservations_count', 2));
});

test('subjects index includes distinct teachers count', function () {
    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $subject->educationLevels()->attach($level->id);

    $teacher = Teacher::factory()->create();
    $teacher->teacherSubjects()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.subjects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/subjects/index')
            ->where('subjects.data.0.teachers_count', 1));
});

test('subjects index can be filtered by education level', function () {
    $levelA = EducationLevel::factory()->create();
    $levelB = EducationLevel::factory()->create();
    $subjectA = Subject::factory()->create();
    $subjectB = Subject::factory()->create();
    $subjectA->educationLevels()->attach($levelA->id);
    $subjectB->educationLevels()->attach($levelB->id);

    $this->actingAs($this->admin)
        ->get(route('admin.subjects.index', ['level' => $levelA->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/subjects/index')
            ->has('subjects.data', 1)
            ->where('subjects.data.0.id', $subjectA->id));
});
