<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FicSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fic_course_id',
        'label',
        'session_date',
        'starts_at',
        'ends_at',
        'room',
        'docente',
        'sort_order',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(FicCourse::class, 'fic_course_id');
    }
}
