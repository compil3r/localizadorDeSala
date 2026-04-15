<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FicCourse extends Model
{
    public $timestamps = false;

    protected $fillable = ['fic_area_id', 'name', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(FicArea::class, 'fic_area_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(FicSession::class, 'fic_course_id')->orderBy('session_date')->orderBy('sort_order');
    }
}
