<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = [
        'resident_id',
        'driver_id',
        'route_assignment_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function resident()
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function routeAssignment()
    {
        return $this->belongsTo(RouteAssignment::class, 'route_assignment_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function otherParticipantFor(User $user): ?User
    {
        if ($user->id === $this->resident_id) {
            return $this->driver;
        }
        if ($user->id === $this->driver_id) {
            return $this->resident;
        }
        return null;
    }

    public function includesUser(User $user): bool
    {
        return $user->id === $this->resident_id || $user->id === $this->driver_id;
    }
}
