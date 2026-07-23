<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'selections' => ['nullable', 'array'],
            'selections.*.education_level_id' => ['required', 'integer', Rule::exists('education_levels', 'id')],
            'selections.*.subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'schedules' => ['nullable', 'array'],
            'schedules.*.education_level_id' => ['required', 'integer', Rule::exists('education_levels', 'id')],
            'schedules.*.subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.starts_at' => ['required', 'date_format:H:i'],
            'schedules.*.ends_at' => ['required', 'date_format:H:i'],
        ];
    }
}
