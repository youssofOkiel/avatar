<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
            'name' => ['nullable', 'required_without:phone', 'string', 'max:120'],
            'phone' => [
                'nullable',
                'required_without:name',
                'string',
                'max:30',
                Rule::unique('students', 'phone')->ignore($this->route('student')),
            ],
        ];
    }
}
