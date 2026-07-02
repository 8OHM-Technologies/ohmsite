<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'unread_notifications_count' => $user ? ($user->unreadNotifications()->count() ?? 0) : 0,
                'notifications' => $user ? $user->notifications()->take(5)->get() : [],
            ],
            'cart_count' => function () use ($user) {
                if (!$user) {
                    return 0;
                }

                try {
                    return app(\App\Services\CartService::class)
                        ->getCart()
                        ->items
                        ->sum('quantity');
                } catch (\Throwable $e) {
                    return 0;
                }
            },
            'app_url' => config('app.url'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
