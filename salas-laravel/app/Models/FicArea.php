<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FicArea extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'kiosk_after_graduation', 'sort_order'];

    protected $casts = [
        'kiosk_after_graduation' => 'boolean',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(FicCourse::class, 'fic_area_id');
    }
}
