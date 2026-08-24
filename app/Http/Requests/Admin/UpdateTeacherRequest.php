<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesTeacherSchedules;
use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    use ValidatesTeacherSchedules;

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
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'selections' => ['nullable', 'array'],
            'selections.*.education_level_id' => ['required', 'integer', Rule::exists('education_levels', 'id')],
            'selections.*.subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'schedules' => ['nullable', 'array'],
            'schedules.*.education_level_id' => ['required', 'integer', Rule::exists('education_levels', 'id')],
            'schedules.*.subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'schedules.*.room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.starts_at' => ['required', 'date_format:H:i'],
            'schedules.*.ends_at' => ['required', 'date_format:H:i'],
        ];
    }

    protected function currentTeacherId(): ?int
    {
        $teacher = $this->route('teacher');

        return $teacher instanceof Teacher ? $teacher->id : null;
    }
}
