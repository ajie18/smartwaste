<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $fillable = [
        'plate_number',
        'model',
        'latitude',
        'longitude',
        'location_updated_at',
    ];

    protected $casts = [
        'location_updated_at' => 'datetime',
    ];

    public function assignments()
    {
        return $this->hasMany(RouteAssignment::class);
    }
}
