<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteAssignment extends Model
{
    protected $table = 'route_assignments';

    protected $fillable = [
        'driver_id',
        'truck_id',
        'route_id',
        'assigned_date',
        'status',
        'pickup_type',
        'progress_log',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class, 'truck_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function tripLogs()
    {
        return $this->hasMany(TripLog::class, 'assignment_id');
    }
}
