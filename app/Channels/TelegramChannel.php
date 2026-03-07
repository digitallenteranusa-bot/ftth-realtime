<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $data = $notification->toTelegram($notifiable);

        if (empty($data['chat_id']) || empty($data['text'])) {
            Log::warning('Telegram notification skipped: missing chat_id or text.');
            return;
        }

        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            Log::error('Telegram bot token is not configured.');
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = Http::post($url, [
            'chat_id' => $data['chat_id'],
            'text' => $data['text'],
            'parse_mode' => $data['parse_mode'] ?? 'Markdown',
        ]);

        if ($response->failed()) {
            Log::error('Telegram notification failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
