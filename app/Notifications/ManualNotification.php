<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ManualNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $message,
        public string $format = 'html'
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'routeNotificationForTelegraph') && $notifiable->routeNotificationForTelegraph($this)) {
            $channels[] = 'telegraph';
        }

        return $channels;
    }

    /**
     * Get the Telegraph representation of the notification.
     */
    public function toTelegraph(object $notifiable): TelegraphMessage
    {
        $telegraphMessage = TelegraphMessage::make($this->message);

        return $this->format === 'markdown'
            ? $telegraphMessage->markdown()
            : $telegraphMessage->html();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array{message: string, type: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'type' => 'manual',
        ];
    }
}
