<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrafficUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $mikrotikId, public array $trafficData) {}

    public function broadcastOn(): array
    {
        return [new Channel('traffic.' . $this->mikrotikId)];
    }

    public function broadcastWith(): array
    {
        return [
            'mikrotik_id' => $this->mikrotikId,
            'data' => $this->trafficData,
        ];
    }
}
