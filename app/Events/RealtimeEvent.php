<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Generic broadcastable event replacing the old `io.to(room).emit(event,
 * payload)` / `io.emit(event, payload)` calls scattered across
 * driverController.js / residentController.js, now running over Laravel
 * Reverb. See routes/channels.php for who's authorized on which channel,
 * and public/js/driver/echo-bridge.js for the client-side listeners.
 *
 * Usage:
 *   broadcast(new RealtimeEvent('driver.'.$driverId, 'assignment-status-changed', [...]));
 *   broadcast(new RealtimeEvent('reports', 'new-resident-report', [...])); // shared channel
 */
class RealtimeEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public ?string $channelName,
        public string $eventName,
        public array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        $name = $this->channelName ?? 'reports';

        // Every channel this app uses (driver.{id}, dispatcher.{id}, and
        // the shared "reports" channel) requires an authenticated Sanctum
        // user — see routes/channels.php — so all of them are private.
        return [new PrivateChannel($name)];
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
