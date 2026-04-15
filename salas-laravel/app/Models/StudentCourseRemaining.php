<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCourseRemaining extends Model
{
    public $timestamps = false;

    protected $table = 'student_course_remaining';

    protected $fillable = [
        'student_id',
        'course_semester',
        'discipline_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class, 'discipline_id');
    }
}

