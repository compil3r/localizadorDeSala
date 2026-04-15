<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumMatrix extends Model
{
    public $timestamps = false;

    protected $table = 'curriculum_matrix';

    protected $fillable = [
        'course_id',
        'course_semester',
        'discipline_id',
        'is_optional',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class, 'discipline_id');
    }
}

