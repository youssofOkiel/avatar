<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ChecksRoomScheduleConflicts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExternalLecturerRequest extends FormRequest
{
    use ChecksRoomScheduleConflicts;

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
            'schedules' => ['nullable', 'array'],
            'schedules.*.topic' => ['required', 'string', 'max:255'],
            'schedules.*.room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.starts_at' => ['required', 'date_format:H:i'],
            'schedules.*.ends_at' => ['required', 'date_format:H:i'],
            'schedules.*.income' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, array<string, mixed>> $schedules */
            $schedules = $this->input('schedules', []);

            foreach ($schedules as $index => $schedule) {
                $start = $schedule['starts_at'] ?? null;
                $end = $schedule['ends_at'] ?? null;

                if ($start === null || $end === null) {
                    continue;
                }

                if ($end <= $start) {
                    $validator->errors()->add("schedules.{$index}.ends_at", 'وقت النهاية يجب أن يكون بعد وقت البداية.');

                    continue;
                }

                if ($this->hasRoomConflict(
                    $schedules,
                    $index,
                    excludeTeacherId: null,
                    excludeExternalLecturerId: $this->currentExternalLecturerId(),
                )) {
                    $validator->errors()->add(
                        "schedules.{$index}.room_id",
                        'القاعة محجوزة في نفس الفترة الزمنية (معلم أو محاضر خارجي).'
                    );
                }
            }
        });
    }

    protected function currentExternalLecturerId(): ?int
    {
        return null;
    }
}
