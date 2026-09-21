<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'routes';

    protected $fillable = [
        'zone_name',
        'description',
        'waypoints_json',
        'path_mode',
    ];

    public function assignments()
    {
        return $this->hasMany(RouteAssignment::class, 'route_id');
    }

    public function getParsedWaypointsAttribute(): array
    {
        $raw = $this->waypoints_json;
        if (empty($raw)) {
            return ['road' => [], 'pins' => [], 'mrf' => []];
        }

        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($data)) {
            return ['road' => [], 'pins' => [], 'mrf' => []];
        }

        if (array_is_list($data)) {
            $pins = [];
            foreach ($data as $i => $wp) {
                $pins[] = [
                    'lat'  => (float) ($wp['lat'] ?? 0),
                    'lng'  => (float) ($wp['lng'] ?? 0),
                    'name' => $wp['name'] ?? ('Stop ' . ($i + 1)),
                    'num'  => $wp['stopNumber'] ?? ($i + 1),
                ];
            }
            return ['road' => [], 'pins' => $pins, 'mrf' => []];
        }

        $road = array_map(fn($v) => [
            'lat' => (float) ($v['lat'] ?? 0),
            'lng' => (float) ($v['lng'] ?? 0),
        ], $data['road_path'] ?? $data['road'] ?? []);

        $pins = [];
        foreach (($data['pin_stops'] ?? $data['pins'] ?? []) as $i => $p) {
            $pins[] = [
                'lat'  => (float) ($p['lat'] ?? 0),
                'lng'  => (float) ($p['lng'] ?? 0),
                'name' => $p['name'] ?? ('Stop ' . ($i + 1)),
                'num'  => $p['stopNumber'] ?? ($i + 1),
            ];
        }

        $mrf = array_map(fn($m) => [
            'lat'  => (float) ($m['lat'] ?? 0),
            'lng'  => (float) ($m['lng'] ?? 0),
            'name' => $m['name'] ?? 'MRF Stop',
        ], $data['mrf_stops'] ?? $data['mrf'] ?? []);

        return ['road' => $road, 'pins' => $pins, 'mrf' => $mrf];
    }
}
