<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'zone_name',
        'description',
        'waypoints_json',
        'path_mode',
    ];

    protected $casts = [
        'waypoints_json' => 'array',
    ];

    public function assignments()
    {
        return $this->hasMany(RouteAssignment::class);
    }
}
