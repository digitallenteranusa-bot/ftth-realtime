<?php

namespace App\Notifications;

use App\Models\Alarm;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Channels\TelegramChannel;
use Illuminate\Notifications\Notification;

class AlarmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Alarm $alarm) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        if (config('services.telegram.bot_token')) {
            $channels[] = TelegramChannel::class;
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $severityLabel = strtoupper($this->alarm->severity);

        return (new MailMessage)
            ->subject("[{$severityLabel}] Alarm: {$this->alarm->title}")
            ->greeting("Alarm Triggered - Severity: {$severityLabel}")
            ->line("Title: {$this->alarm->title}")
            ->line("Description: {$this->alarm->description}")
            ->line("Device Type: {$this->alarm->device_type}")
            ->line("Device ID: {$this->alarm->device_id}")
            ->action('View Alarms', url('/alarms'))
            ->line('Please investigate this alarm as soon as possible.');
    }

    /**
     * Get the Telegram representation of the notification.
     *
     * @return array<string, string>
     */
    public function toTelegram(object $notifiable): array
    {
        $severityEmoji = match ($this->alarm->severity) {
            'critical' => '🔴',
            'major' => '🟠',
            'minor' => '🟡',
            'warning' => '🔵',
            default => '⚪',
        };

        $text = implode("\n", [
            "{$severityEmoji} *ALARM [{$this->alarm->severity}]*",
            "",
            "*Title:* {$this->alarm->title}",
            "*Description:* {$this->alarm->description}",
            "*Device Type:* {$this->alarm->device_type}",
            "*Device ID:* {$this->alarm->device_id}",
            "",
            "View alarms: " . url('/alarms'),
        ]);

        return [
            'chat_id' => config('services.telegram.default_chat_id'),
            'text' => $text,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alarm_id' => $this->alarm->id,
            'severity' => $this->alarm->severity,
            'title' => $this->alarm->title,
        ];
    }
}
