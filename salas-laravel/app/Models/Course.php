<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'code', 'coordinator_id'];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class, 'course_id');
    }
}
