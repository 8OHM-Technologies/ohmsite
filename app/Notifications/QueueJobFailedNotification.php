<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QueueJobFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $connectionName,
        public string $queueName,
        public string $jobName,
        public string $exceptionMessage,
        public ?int $attempts = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'routeNotificationForTelegraph') && $notifiable->routeNotificationForTelegraph($this)) {
            $channels[] = 'telegraph';
        }

        return $channels;
    }

    public function toTelegraph(object $notifiable): TelegraphMessage
    {
        $sanitizedMessage = htmlspecialchars(Str::limit($this->exceptionMessage, 500), ENT_QUOTES, 'UTF-8');

        $html = "💥 <b>Queue Job Failed!</b>\n\n".
            "<b>Job:</b> <code>{$this->jobName}</code>\n".
            "<b>Connection:</b> <code>{$this->connectionName}</code>\n".
            "<b>Queue:</b> <code>{$this->queueName}</code>\n";

        if ($this->attempts !== null) {
            $html .= "<b>Attempts:</b> <code>{$this->attempts}</code>\n";
        }

        $html .= "\n<b>Error:</b>\n<pre>{$sanitizedMessage}</pre>";

        return TelegraphMessage::make($html)->html();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'queue_job_failed',
            'job' => $this->jobName,
            'connection' => $this->connectionName,
            'queue' => $this->queueName,
            'attempts' => $this->attempts,
            'error' => Str::limit($this->exceptionMessage, 255),
            'message' => 'Job '.$this->jobName.' on queue '.$this->queueName.' failed.',
        ];
    }
}
