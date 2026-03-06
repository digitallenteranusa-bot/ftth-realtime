<?php
namespace App\Events;

use App\Models\Ont;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OntStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ont $ont, public string $oldStatus) {}

    public function broadcastOn(): array
    {
        return [new Channel('network-monitoring')];
    }

    public function broadcastWith(): array
    {
        return [
            'ont_id' => $this->ont->id,
            'serial_number' => $this->ont->serial_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->ont->status,
            'lat' => $this->ont->lat,
            'lng' => $this->ont->lng,
        ];
    }
}
