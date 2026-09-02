<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'ends_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $endsAt = $this->input('ends_at');

            if ($endsAt === null || $endsAt === '') {
                return;
            }

            $session = $this->route('classSession');

            if (! $session instanceof ClassSession) {
                return;
            }

            if ($endsAt <= $session->starts_at->format('H:i')) {
                $validator->errors()->add('ends_at', 'وقت النهاية يجب أن يكون بعد وقت البداية.');
            }
        });
    }
}
