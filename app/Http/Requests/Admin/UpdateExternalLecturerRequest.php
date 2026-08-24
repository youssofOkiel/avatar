<?php

namespace App\Http\Requests\Admin;

use App\Models\ExternalLecturer;

class UpdateExternalLecturerRequest extends StoreExternalLecturerRequest
{
    protected function currentExternalLecturerId(): ?int
    {
        $lecturer = $this->route('external_lecturer');

        return $lecturer instanceof ExternalLecturer ? $lecturer->id : null;
    }
}
