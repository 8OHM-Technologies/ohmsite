<?php

namespace App\Notifications;

use App\Models\User;
use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public function __construct(public User $user)
    {
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
        $company = $this->user->company_name ?? 'N/A';
        $country = $this->user->country ?? 'N/A';
        $phone = $this->user->phone ?? 'N/A';

        $html = "👤 <b>New User Registered!</b>\n\n".
            "<b>Name:</b> {$this->user->first_name} {$this->user->last_name}\n".
            "<b>Email:</b> {$this->user->email}\n".
            "<b>Company:</b> {$company}\n".
            "<b>Phone:</b> <code>".e($phone)."</code>\n".
            "<b>Country:</b> {$country}";

        return TelegraphMessage::make($html)->html();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->first_name.' '.$this->user->last_name,
            'email' => $this->user->email,
            'message' => "New User Registered: {$this->user->first_name} {$this->user->last_name} ({$this->user->email})",
            'type' => 'registration',
        ];
    }
}
