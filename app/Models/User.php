<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // php artisan install:api (Sanctum) for token auth

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'email_verified',
        'latitude',
        'longitude',
        'location_updated_at',
        'last_login',
        'last_ip',
        'locked_at',
        'reset_pin',
        'reset_pin_expires',
        'reset_attempts',
        'password_updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_pin',
    ];

    protected $casts = [
        'email_verified'      => 'boolean',
        'location_updated_at' => 'datetime',
        'last_login'          => 'datetime',
        'locked_at'           => 'datetime',
        'reset_pin_expires'   => 'datetime',
        'password_updated_at' => 'datetime',
        'password'            => 'hashed',
    ];

    public function routeAssignmentsAsDriver()
    {
        return $this->hasMany(RouteAssignment::class, 'driver_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'resident_id');
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isResident(): bool
    {
        return $this->role === 'resident';
    }
}
