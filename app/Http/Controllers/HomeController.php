<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomeSetting;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function test()
    {
        try {
            return Inertia::render('Test');
        } catch (\Throwable $e) {
            return Inertia::render('Test');
        }
    }

    public function privacyPolicy()
    {
        return view('privacy-policy');
    }

    public function refundCancellation()
    {
        return view('refund-and-cancellation');
    }

    public function terms()
    {
        return view('terms');
    }

    public function fairUsage()
    {
        return view('fair-usage');
    }

    public function index()
    {
        try {
            $user = auth()->user();

            $heroSlider = HomeSetting::where('key', 'hero_slider')->first();

            $heroProducts = [];
            if ($heroSlider && isset($heroSlider->value)) {
                foreach ($heroSlider->value as $slide) {
                    $isFavorited = false;
                    if ($user && isset($slide['product_id'])) {
                        $isFavorited = $user->favorites()->where('product_id', $slide['product_id'])->exists();
                    }

                    $heroProducts[] = [
                        'id' => $slide['product_id'] ?? null,
                        'title' => $slide['title'] ?? '',
                        'price' => $slide['price'] ?? '',
                        'category' => $slide['category'] ?? '',
                        'image' => $slide['image'] ?? '',
                        'is_favorited' => $isFavorited,
                    ];
                }
            }

            $categories = Category::all();

            $popularProducts = Product::where('clicks', '>', 0)->orderBy('clicks', 'desc')->take(10)->get();

            $newArrivals = Product::where('created_at', '>=', Carbon::now()->subWeek())->orderBy('created_at', 'desc')->get();

            $aboutUs = HomeSetting::where('key', 'about_us')->first();

            $products = Product::whereIn('name', [
                'Once-off Dataset',
                'Developer API',
                'Analytics Dashboard',
                'Managed Data Pipeline',
            ])->get();

            return Inertia::render('Home', [
                'heroProducts' => $heroProducts,
                'categories' => $categories,
                'popularProducts' => $popularProducts,
                'newArrivals' => $newArrivals,
                'aboutUs' => $aboutUs ? $aboutUs->value : null,
                'products' => $products,
            ]);
        } catch (\Throwable $e) {
            return Inertia::render('Home', [
                'heroProducts' => [],
                'categories' => [],
                'popularProducts' => [],
                'newArrivals' => [],
                'aboutUs' => null,
                'products' => [],
            ]);
        }
    }
}
