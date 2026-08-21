<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SecurityLockoutNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?string $ip = null,
        public ?string $identifier = null,
        public ?string $userAgent = null,
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
        $html = "🛡️ <b>Security Alert: Auth Lockout!</b>\n\n".
            "Too many failed login attempts detected.\n\n";

        if ($this->identifier !== null) {
            $html .= "<b>Target Account:</b> <code>{$this->identifier}</code>\n";
        }

        if ($this->ip !== null) {
            $html .= "<b>IP Address:</b> <code>{$this->ip}</code>\n";
        }

        if ($this->userAgent !== null) {
            $shortUa = htmlspecialchars(Str::limit($this->userAgent, 100), ENT_QUOTES, 'UTF-8');
            $html .= "<b>User-Agent:</b> <code>{$shortUa}</code>\n";
        }

        $html .= '<b>Timestamp:</b> <code>'.now()->toIso8601String().'</code>';

        return TelegraphMessage::make($html)->html();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'security_lockout',
            'ip' => $this->ip,
            'identifier' => $this->identifier,
            'user_agent' => $this->userAgent,
            'message' => 'Authentication lockout triggered for '.$this->identifier.' from IP '.$this->ip,
        ];
    }
}
