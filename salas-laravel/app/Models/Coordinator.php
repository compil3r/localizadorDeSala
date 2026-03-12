<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coordinator extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'coordinator_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'coordinator_id');
    }
}
