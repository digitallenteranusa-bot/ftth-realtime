<?php
namespace App\Events;

use App\Models\Alarm;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlarmTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alarm $alarm) {}

    public function broadcastOn(): array
    {
        return [new Channel('network-monitoring')];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->alarm->id,
            'severity' => $this->alarm->severity,
            'title' => $this->alarm->title,
            'description' => $this->alarm->description,
            'device_type' => $this->alarm->device_type,
            'device_id' => $this->alarm->device_id,
        ];
    }
}
