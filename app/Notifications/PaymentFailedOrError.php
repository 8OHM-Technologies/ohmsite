<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentFailedOrError extends Notification
{
    use Queueable;

    protected $order;
    protected $errorType;
    protected $errorMessage;

    public function __construct($order, string $errorType, string $errorMessage)
    {
        $this->order = $order;
        $this->errorType = $errorType;
        $this->errorMessage = $errorMessage;
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'routeNotificationForTelegraph') && $notifiable->routeNotificationForTelegraph($this)) {
            $channels[] = 'telegraph';
        }

        return $channels;
    }

    public function toTelegraph($notifiable): \DefStudio\Telegraph\Notifications\TelegraphMessage
    {
        $orderId = $this->order ? $this->order->id : 'N/A';
        $amount = $this->order ? 'R' . number_format($this->order->total_amount, 2) : 'N/A';

        $html = "⚠️ <b>Payment Failed or Error!</b>\n\n" .
            "<b>Error Type:</b> {$this->errorType}\n" .
            "<b>Order ID:</b> #{$orderId}\n" .
            "<b>Amount:</b> {$amount}\n" .
            "<b>Message:</b> <code>" . e($this->errorMessage) . "</code>";

        return \DefStudio\Telegraph\Notifications\TelegraphMessage::make($html)->html();
    }

    public function toArray($notifiable): array
    {
        $orderId = $this->order ? $this->order->id : null;
        $amount = $this->order ? $this->order->total_amount : null;

        $msg = '[' . $this->errorType . '] ' . ($orderId ? 'Order #' . $orderId . ': ' : '') . $this->errorMessage;

        return [
            'order_id' => $orderId,
            'amount' => $amount,
            'error_type' => $this->errorType,
            'error_message' => $this->errorMessage,
            'message' => $msg,
            'type' => 'error',
        ];
    }
}
