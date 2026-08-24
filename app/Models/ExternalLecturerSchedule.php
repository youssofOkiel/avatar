<?php

namespace App\Models;

use Database\Factories\ExternalLecturerScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['external_lecturer_id', 'topic', 'room_id', 'day_of_week', 'starts_at', 'ends_at', 'income'])]
class ExternalLecturerSchedule extends Model
{
    /** @use HasFactory<ExternalLecturerScheduleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'income' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ExternalLecturer, $this>
     */
    public function externalLecturer(): BelongsTo
    {
        return $this->belongsTo(ExternalLecturer::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
