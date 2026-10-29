<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'password' => 'hashed',
            'reset_pin_expires' => 'datetime',
            'last_login' => 'datetime',
            'locked_at' => 'datetime',
            'location_updated_at' => 'datetime',
            'password_updated_at' => 'datetime',
        ];
    }

    // Relationships
    public function routeAssignments()
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
}
