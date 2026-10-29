<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteAssignment extends Model
{
    protected $fillable = [
        'driver_id',
        'truck_id',
        'route_id',
        'assigned_date',
        'pickup_type',
        'status',
        'progress_log',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'progress_log' => 'array',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function tripLogs()
    {
        return $this->hasMany(TripLog::class, 'assignment_id');
    }
}
