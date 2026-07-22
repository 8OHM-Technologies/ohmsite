<?php

namespace App\Notifications;

use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification
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
        $this->order->loadMissing('items.product');

        $products = [];
        $billingTerms = [];

        foreach ($this->order->items as $item) {
            $products[] = $item->product->name;

            $options = is_string($item->options)
                ? json_decode($item->options, true)
                : $item->options;

            $term = [];
            if (isset($options['frequency'])) {
                $term[] = ucfirst($options['frequency']);
            }
            if (isset($options['dataset'])) {
                $term[] = 'Dataset: '.strtoupper($options['dataset']);
            }

            $billingTerms[] = count($term) > 0 ? implode(' - ', $term) : 'One-off';
        }

        $productList = implode(', ', $products);
        $termsList = implode(', ', $billingTerms);

        $html = "🔔 <b>New Order Placed!</b>\n\n".
            "<b>Order ID:</b> #{$this->order->id}\n".
            "<b>Customer:</b> {$this->order->first_name} {$this->order->last_name}\n".
            '<b>Amount:</b> R'.number_format($this->order->total_amount, 2)."\n".
            "<b>Products:</b> {$productList}\n".
            "<b>Billing:</b> {$termsList}";

        return TelegraphMessage::make($html)->html();
    }

    public function toArray($notifiable): array
    {
        $this->order->loadMissing('items.product');

        $products = [];
        $billingTerms = [];

        foreach ($this->order->items as $item) {
            $products[] = $item->product->name;

            $options = is_string($item->options)
                ? json_decode($item->options, true)
                : $item->options;

            $term = [];
            if (isset($options['frequency'])) {
                $term[] = ucfirst($options['frequency']);
            }
            if (isset($options['dataset'])) {
                $term[] = 'Dataset: '.strtoupper($options['dataset']);
            }

            $billingTerms[] = count($term) > 0 ? implode(' - ', $term) : 'One-off';
        }

        $productList = implode(', ', $products);
        $termsList = implode(', ', $billingTerms);

        return [
            'order_id' => $this->order->id,
            'amount' => $this->order->total_amount,
            'customer_name' => $this->order->first_name.' '.$this->order->last_name,
            'products' => $productList,
            'billing_terms' => $termsList,
            'message' => 'New order received for '.$productList.' ('.$termsList.') from '.$this->order->first_name,
            'type' => 'order',
        ];
    }
}
