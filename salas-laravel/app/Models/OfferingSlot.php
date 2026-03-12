<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferingSlot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'period_id',
        'discipline_id',
        'teacher_id',
        'turno',
        'dia_semana',
        'room',
        'observation',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function courseOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class, 'offering_slot_id');
    }
}
