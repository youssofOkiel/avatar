<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('attendance_count') === '') {
            $this->merge(['attendance_count' => null]);
        }

        if ($this->input('income') === '') {
            $this->merge(['income' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'income' => ['nullable', 'numeric', 'min:0'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
