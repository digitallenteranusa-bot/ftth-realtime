<?php

namespace App\Listeners;

use App\Events\AlarmTriggered;
use App\Models\User;
use App\Notifications\AlarmNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendAlarmNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(AlarmTriggered $event): void
    {
        $alarm = $event->alarm;

        if (! in_array($alarm->severity, ['critical', 'major'])) {
            return;
        }

        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new AlarmNotification($alarm));
    }
}
