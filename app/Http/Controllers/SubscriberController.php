<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SubscriberAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class SubscriberController extends Controller
{
    public function __construct(
        protected SubscriberAnalyticsService $analytics
    ) {}

    public function index(): Response
    {
        return Inertia::render(
            'Subscriber/Analytics/Index',
            $this->analytics->getDashboardPayload()
        );
    }

    public function products(): Response
    {
        $products = Product::with(['category'])->latest()->take(5)->get();

        return Inertia::render('Subscriber/Products/Index', [
            'products' => $products,
        ]);
    }
}
