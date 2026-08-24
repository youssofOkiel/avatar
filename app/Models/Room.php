<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * @return HasMany<TeacherSchedule, $this>
     */
    public function teacherSchedules(): HasMany
    {
        return $this->hasMany(TeacherSchedule::class);
    }

    /**
     * @return HasMany<ClassSession, $this>
     */
    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * @return HasMany<ExternalLecturerSchedule, $this>
     */
    public function externalLecturerSchedules(): HasMany
    {
        return $this->hasMany(ExternalLecturerSchedule::class);
    }
}
