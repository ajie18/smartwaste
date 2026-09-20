<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrfLocation extends Model
{
    protected $table = 'mrf_locations';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'created_by',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
