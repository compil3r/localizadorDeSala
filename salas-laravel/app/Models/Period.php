<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function offeringSlots(): HasMany
    {
        return $this->hasMany(OfferingSlot::class, 'period_id');
    }
}
