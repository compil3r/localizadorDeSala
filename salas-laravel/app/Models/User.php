<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'coordinator_id',
        'active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Laravel Auth usa este atributo para verificar a senha.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'COORDINATOR';
    }
}
