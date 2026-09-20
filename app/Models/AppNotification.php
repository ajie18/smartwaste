<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Named AppNotification (not Notification) to avoid clashing with
 * Illuminate\Notifications\Notification. Maps to the `notifications` table
 * used by the old Node driverController.js / residentController.js.
 */
class AppNotification extends Model
{
    protected $table = 'notifications';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_type',
        'message',
        'type',
        'is_read',
        'related_id',
        'created_at',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
