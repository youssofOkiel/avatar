<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassSessionRequest extends FormRequest
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
            'type' => ['required', Rule::in(['subject', 'rental'])],
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')],
            'income' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'teacher_id' => ['nullable', 'required_if:type,subject', 'integer', Rule::exists('teachers', 'id')],
            'subject_id' => ['nullable', 'required_if:type,subject', 'integer', Rule::exists('subjects', 'id')],
            'title' => ['nullable', 'required_if:type,rental', 'string', 'max:255'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
