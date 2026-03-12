<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOffering extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'offering_slot_id',
        'origin_type',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function offeringSlot(): BelongsTo
    {
        return $this->belongsTo(OfferingSlot::class, 'offering_slot_id');
    }

    public function isPropria(): bool
    {
        return $this->origin_type === 'PROPRIA';
    }
}
