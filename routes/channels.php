<?php

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('driver.{driverId}', function (User $user, int $driverId) {
    return $user->role === 'driver' && (int) $user->id === $driverId;
});

Broadcast::channel('resident.{residentId}', function (User $user, int $residentId) {
    return $user->role === 'resident' && (int) $user->id === $residentId;
});

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    $conversation = ChatConversation::find($conversationId);
    return $conversation && $conversation->includesUser($user);
});

Broadcast::channel('dispatcher.{driverId}', function (User $user, int $driverId) {
    return $user->role === 'admin' || ((int) $user->id === $driverId && $user->role === 'driver');
});

Broadcast::channel('reports', function (User $user) {
    return true;
});
