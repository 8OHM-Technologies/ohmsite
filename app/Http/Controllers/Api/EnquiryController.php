<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WebsiteEnquiryReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EnquiryController extends Controller
{
    /**
     * Store and forward website enquiry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'division' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Send notifications to admins
        try {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new WebsiteEnquiryReceived($validated));
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification for website enquiry: '.$e->getMessage());
        }

        // Forward to Control Plane
        try {
            $response = Http::post('https://control-plane.8ohm.co.za/api/send-website-enquiry', $validated);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Failed to forward website enquiry to control plane: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to connect to the control plane server.',
            ], 502);
        }
    }
}
