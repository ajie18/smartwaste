<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripLog extends Model
{
    protected $fillable = [
        'assignment_id',
        'latitude',
        'longitude',
        'fuel_used',
        'logged_at',
        'completed_at',
        'end_time',
        'status',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'completed_at' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(RouteAssignment::class, 'assignment_id');
    }
}
