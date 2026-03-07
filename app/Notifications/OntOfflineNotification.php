<?php

namespace App\Notifications;

use App\Models\Ont;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OntOfflineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ont $ont) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->ont->customer?->name ?? 'N/A';
        $customerId = $this->ont->customer_id ?? 'N/A';

        return (new MailMessage)
            ->subject("ONT Offline Alert: {$this->ont->serial_number}")
            ->greeting('ONT Offline Notification')
            ->line("An ONT device has gone offline.")
            ->line("Serial Number: {$this->ont->serial_number}")
            ->line("ONT Name: {$this->ont->name}")
            ->line("Status: {$this->ont->status}")
            ->line("Customer: {$customerName} (ID: {$customerId})")
            ->line("Last Online: " . ($this->ont->last_online_at?->format('Y-m-d H:i:s') ?? 'Unknown'))
            ->action('View ONT Details', url("/onts/{$this->ont->id}"))
            ->line('Please check the device and customer connectivity.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ont_id' => $this->ont->id,
            'serial_number' => $this->ont->serial_number,
            'status' => $this->ont->status,
            'customer_id' => $this->ont->customer_id,
        ];
    }
}
