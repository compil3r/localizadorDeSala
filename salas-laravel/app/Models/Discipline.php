<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'owning_course_id'];

    public function owningCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'owning_course_id');
    }

    public function offeringSlots(): HasMany
    {
        return $this->hasMany(OfferingSlot::class, 'discipline_id');
    }
}
