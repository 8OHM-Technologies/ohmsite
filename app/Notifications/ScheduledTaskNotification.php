<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ScheduledTaskNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $taskName,
        public string $status, // 'failed' or 'success'
        public ?string $output = null,
        public ?float $runtimeInSeconds = null,
        public ?int $exitCode = null,
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
        $isSuccess = $this->status === 'success';
        $icon = $isSuccess ? '⏱️' : '❌';
        $title = $isSuccess ? 'Scheduled Task Succeeded' : 'Scheduled Task Failed!';

        $html = "{$icon} <b>{$title}</b>\n\n".
            "<b>Task:</b> <code>{$this->taskName}</code>\n".
            '<b>Status:</b> '.($isSuccess ? '<code>SUCCESS</code>' : '<code>FAILED</code>')."\n";

        if ($this->runtimeInSeconds !== null) {
            $formattedRuntime = number_format($this->runtimeInSeconds, 2);
            $html .= "<b>Duration:</b> <code>{$formattedRuntime}s</code>\n";
        }

        if ($this->exitCode !== null) {
            $html .= "<b>Exit Code:</b> <code>{$this->exitCode}</code>\n";
        }

        if (! empty($this->output)) {
            $sanitizedOutput = htmlspecialchars(Str::limit($this->output, 500), ENT_QUOTES, 'UTF-8');
            $label = $isSuccess ? 'Output' : 'Error Details';
            $html .= "\n<b>{$label}:</b>\n<pre>{$sanitizedOutput}</pre>";
        }

        return TelegraphMessage::make($html)->html();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'scheduled_task',
            'task' => $this->taskName,
            'status' => $this->status,
            'runtime_seconds' => $this->runtimeInSeconds,
            'exit_code' => $this->exitCode,
            'message' => 'Scheduled task '.$this->taskName.' '.($this->status === 'success' ? 'completed successfully.' : 'failed.'),
        ];
    }
}
