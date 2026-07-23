<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')],
            'name' => ['nullable', 'required_without_all:phone,student_id', 'string', 'max:120'],
            'phone' => ['nullable', 'required_without_all:name,student_id', 'string', 'max:30'],
            'education_level_id' => ['required', 'integer', Rule::exists('education_levels', 'id')],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')],
            'teacher_schedule_ids' => ['nullable', 'array'],
            'teacher_schedule_ids.*' => ['integer', Rule::exists('teacher_schedules', 'id')],
        ];
    }
}
