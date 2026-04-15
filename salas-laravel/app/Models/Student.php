<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'matricula',
        'name',
        'course_id',
        'turno',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function remaining(): HasMany
    {
        return $this->hasMany(StudentCourseRemaining::class, 'student_id');
    }
}

