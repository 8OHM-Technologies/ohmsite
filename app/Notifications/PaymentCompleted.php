<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentCompleted extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'routeNotificationForTelegraph') && $notifiable->routeNotificationForTelegraph($this)) {
            $channels[] = 'telegraph';
        }

        return $channels;
    }

    public function toTelegraph($notifiable): TelegraphMessage
    {
        $html = "✅ <b>Payment Received!</b>\n\n".
            "<b>Order ID:</b> #{$this->order->id}\n".
            '<b>Amount:</b> R'.number_format($this->order->total_amount, 2)."\n".
            "<b>Reference:</b> <code>{$this->order->payment_reference}</code>\n\n".
            'Payment verified successfully.';

        return TelegraphMessage::make($html)->html();
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'amount' => $this->order->total_amount,
            'payment_reference' => $this->order->payment_reference,
            'message' => 'Payment of R'.number_format($this->order->total_amount, 2).' verified successfully for Order #'.$this->order->id,
            'type' => 'payment',
        ];
    }
}
