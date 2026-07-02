<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        $conversations = [
            [
                'id' => 1,
                'user' => ['name' => 'Arben Gashi', 'avatar' => null, 'status' => 'online'],
                'last_message' => 'Hi, when will the Jordan 1 Lost & Found be restocked?',
                'time' => '2m ago',
                'unread' => 2,
                'priority' => true,
                'order_id' => 'ORD-10234',
                'messages' => [
                    ['id' => 1, 'sender' => 'user', 'text' => 'Hello!', 'time' => '10:00 AM'],
                    ['id' => 2, 'sender' => 'user', 'text' => 'Hi, when will the Jordan 1 Lost & Found be restocked?', 'time' => '10:01 AM'],
                    ['id' => 3, 'sender' => 'admin', 'text' => 'Hello Arben! We expect a restock next week. We can notify you if you like.', 'time' => '10:05 AM'],
                ]
            ],
            [
                'id' => 2,
                'user' => ['name' => 'Besnik Hoxha', 'avatar' => null, 'status' => 'offline'],
                'last_message' => 'I received the wrong size for my order #1245.',
                'time' => '1h ago',
                'unread' => 0,
                'priority' => false,
                'order_id' => 'ORD-1245',
                'messages' => [
                    ['id' => 1, 'sender' => 'user', 'text' => 'I received the wrong size for my order #1245.', 'time' => '09:15 AM'],
                    ['id' => 2, 'sender' => 'admin', 'text' => 'Hello Besnik, sorry to hear that. Can you send a photo of the tag?', 'time' => '09:20 AM'],
                    ['id' => 3, 'sender' => 'user', 'text' => 'Sure, here it is. The tag says US 9 but I ordered US 10.', 'time' => '09:25 AM'],
                ]
            ],
            [
                'id' => 3,
                'user' => ['name' => 'Edona Berisha', 'avatar' => null, 'status' => 'online'],
                'last_message' => 'Thanks for the quick delivery!',
                'time' => '3h ago',
                'unread' => 0,
                'priority' => false,
                'order_id' => 'ORD-15678',
                'messages' => [
                    ['id' => 1, 'sender' => 'user', 'text' => 'Thanks for the quick delivery!', 'time' => 'Yesterday'],
                ]
            ],
             [
                'id' => 4,
                'user' => ['name' => 'Dardan Krasniqi', 'avatar' => null, 'status' => 'away'],
                'last_message' => 'Is the Nike Air Max 90 available in size 43?',
                'time' => 'Yesterday',
                'unread' => 1,
                'priority' => false,
                'order_id' => 'ORD-18901',
                'messages' => [
                    ['id' => 1, 'sender' => 'user', 'text' => 'Is the Nike Air Max 90 available in size 43?', 'time' => 'Yesterday'],
                ]
            ]
        ];

        $stats = [
            'open_tickets' => 12,
            'closed_tickets' => 450,
            'avg_response' => '14m',
            'active_chats' => 5
        ];

        return Inertia::render('Admin/Messages/Index', [
            'conversations' => $conversations,
            'stats' => $stats
        ]);
    }
}
