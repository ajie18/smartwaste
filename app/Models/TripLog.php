<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripLog extends Model
{
    protected $table = 'trip_logs';
    public $timestamps = false;

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
        'logged_at'    => 'datetime',
        'completed_at' => 'datetime',
        'end_time'     => 'datetime',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'fuel_used'    => 'float',
    ];

    public function assignment()
    {
        return $this->belongsTo(RouteAssignment::class, 'assignment_id');
    }
}
