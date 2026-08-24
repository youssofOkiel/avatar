<?php

namespace App\Models;

use Database\Factories\ExternalLecturerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name'])]
class ExternalLecturer extends Model
{
    /** @use HasFactory<ExternalLecturerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return HasMany<ExternalLecturerSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ExternalLecturerSchedule::class);
    }
}
