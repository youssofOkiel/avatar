<?php

namespace App\Models;

use Database\Factories\EducationLevelGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class EducationLevelGroup extends Model
{
    /** @use HasFactory<EducationLevelGroupFactory> */
    use HasFactory;

    /**
     * @return HasMany<EducationLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(EducationLevel::class)->orderBy('id');
    }
}
