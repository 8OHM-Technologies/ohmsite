<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SystemErrorNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $exceptionClass,
        public string $errorMessage,
        public string $file,
        public int $line,
        public ?string $contextUrl = null,
        public ?string $method = null,
        public ?string $ip = null,
        public ?int $userId = null,
        public int $occurrences = 1,
    ) {
    }

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
        $env = app()->environment();
        $shortFile = Str::after($this->file, base_path().'/');
        $sanitizedMessage = htmlspecialchars(Str::limit($this->errorMessage, 500), ENT_QUOTES, 'UTF-8');

        $html = "🚨 <b>Backend Exception Occurred!</b>\n\n".
            "<b>Environment:</b> <code>{$env}</code>\n".
            "<b>Exception:</b> <code>{$this->exceptionClass}</code>\n".
            "<b>Location:</b> <code>{$shortFile}:{$this->line}</code>\n";

        if ($this->contextUrl !== null) {
            $methodPrefix = $this->method ? "[{$this->method}] " : '';
            $html .= "<b>Context:</b> <code>{$methodPrefix}{$this->contextUrl}</code>\n";
        }

        if ($this->ip !== null || $this->userId !== null) {
            $ipPart = $this->ip ? "IP: {$this->ip}" : '';
            $userPart = $this->userId ? "User #{$this->userId}" : '';
            $meta = implode(' | ', array_filter([$ipPart, $userPart]));
            if (!empty($meta)) {
                $html .= "<b>Client:</b> <code>{$meta}</code>\n";
            }
        }

        if ($this->occurrences > 1) {
            $html .= "<b>Occurrences:</b> <code>{$this->occurrences} times in throttle window</code>\n";
        }

        $html .= "\n<b>Message:</b>\n<pre>{$sanitizedMessage}</pre>";

        return TelegraphMessage::make($html)->html();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_error',
            'exception' => $this->exceptionClass,
            'message' => Str::limit($this->errorMessage, 255),
            'file' => $this->file,
            'line' => $this->line,
            'context_url' => $this->contextUrl,
            'occurrences' => $this->occurrences,
        ];
    }
}
