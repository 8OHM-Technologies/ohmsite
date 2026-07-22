<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WebsiteEnquiryReceived extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param array{name: string, email: string, division: string, message: string} $enquiry
     */
    public function __construct(public array $enquiry)
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
        $html = "✉️ <b>New Website Enquiry!</b>\n\n".
            "<b>Name:</b> {$this->enquiry['name']}\n".
            "<b>Email:</b> {$this->enquiry['email']}\n".
            "<b>Division:</b> ".ucfirst($this->enquiry['division'])."\n\n".
            "<b>Message:</b>\n<code>".e($this->enquiry['message'])."</code>";

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
            'name' => $this->enquiry['name'],
            'email' => $this->enquiry['email'],
            'division' => $this->enquiry['division'],
            'message' => "New Website Enquiry from {$this->enquiry['name']} ({$this->enquiry['email']}) for ".ucfirst($this->enquiry['division']),
            'type' => 'enquiry',
        ];
    }
}
