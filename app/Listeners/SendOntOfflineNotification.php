<?php

namespace App\Listeners;

use App\Events\OntStatusChanged;
use App\Models\User;
use App\Notifications\OntOfflineNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendOntOfflineNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OntStatusChanged $event): void
    {
        $newStatus = $event->ont->status;

        if (! in_array($newStatus, ['offline', 'los'])) {
            return;
        }

        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new OntOfflineNotification($event->ont));
    }
}
