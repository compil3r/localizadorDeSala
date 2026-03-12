<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function offeringSlots(): HasMany
    {
        return $this->hasMany(OfferingSlot::class, 'teacher_id');
    }
}
