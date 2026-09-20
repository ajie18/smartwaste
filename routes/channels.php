<?php

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Authorizes the private channels App\Events\RealtimeEvent broadcasts on.
| Echo hits /broadcasting/auth (registered by withBroadcasting() in
| bootstrap/app.php) with the Sanctum bearer token attached — see
| public/js/driver/echo-bridge.js for the client side of this.
*/

// A driver only ever needs their own channel — used for
// assignment-status-changed, report-submitted, profile-updated, and
// incoming chat-message pings.
Broadcast::channel('driver.{driverId}', function (User $user, int $driverId) {
    return $user->role === 'driver' && (int) $user->id === $driverId;
});

// Mirror of the above for residents — currently just used for incoming
// chat-message pings when they're not on the chat panel.
Broadcast::channel('resident.{residentId}', function (User $user, int $residentId) {
    return $user->role === 'resident' && (int) $user->id === $residentId;
});

// A resident/driver chat thread — only its two participants may listen.
Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    $conversation = ChatConversation::find($conversationId);
    return $conversation && $conversation->includesUser($user);
});

// Dispatcher-facing channel for a specific driver's live location / new
// quick-reports. No dedicated dispatcher role exists yet, so for now this
// authorizes the driver themself (their own dashboard can hear its own
// echoes on a second tab) and any admin.
Broadcast::channel('dispatcher.{driverId}', function (User $user, int $driverId) {
    return $user->role === 'admin' || ((int) $user->id === $driverId && $user->role === 'driver');
});

// Public-ish "reports" channel: new-resident-report, report-status-changed,
// new-announcement, truck-location, assignment-public-status. Anyone
// authenticated can listen — it carries nothing sensitive per-user.
Broadcast::channel('reports', function (User $user) {
    return true;
});
