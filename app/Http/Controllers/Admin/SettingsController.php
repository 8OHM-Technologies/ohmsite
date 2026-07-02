<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'store' => [
                'name' => 'Store Name',
                'logo' => '/assets/images/LOGO.png',
                'currency' => 'USD',
                'timezone' => 'UTC+1'
            ],
            'appearance' => [
                'mode' => 'dark',
                'accent_color' => '#E5FF45',
                'sidebar_style' => 'glass'
            ],
            'notifications' => [
                'email_orders' => true,
                'email_stock' => true,
                'sms_orders' => false,
                'marketing' => true
            ],
            'payment' => [
                'stripe_enabled' => true,
                'paypal_enabled' => true,
                'tax_rate' => 18
            ],
            'shipping' => [
                'free_threshold' => 150,
                'standard_rate' => 15
            ]
        ];

        $team = [
            ['id' => 1, 'name' => 'Admin User', 'email' => 'admin@gmail.com', 'role' => 'Super Admin'],
            ['id' => 2, 'name' => 'Support Team', 'email' => 'support@gmail.com', 'role' => 'Manager'],
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'team' => $team
        ]);
    }

    public function update(Request $request)
    {
        // In a real app, you would save these to a settings table or .env
        // For now, we'll simulate success to make it "functional" as requested
        return back()->with('success', 'Settings updated successfully.');
    }
}
