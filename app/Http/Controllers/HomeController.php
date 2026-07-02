<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSetting;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();

            $heroSlider = HomeSetting::where('key', 'hero_slider')->first();

            $heroProducts = [];
            if ($heroSlider && isset($heroSlider->value)) {
                foreach ($heroSlider->value as $slide) {
                    // Fetch brand logos if brand_ids exist
                    $slideBrands = [];
                    if (isset($slide['brand_ids'])) {
                        $slideBrands = Brand::whereIn('id', $slide['brand_ids'])->get();
                    }
                    
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
                        'brands' => $slideBrands,
                        'is_favorited' => $isFavorited
                    ];
                }
            }

            $brands = Brand::all();
            $categories = Category::all();
            
            $popularProducts = Product::with('brands')->where('clicks', '>', 0)->orderBy('clicks', 'desc')->take(10)->get();
            
            $newArrivals = Product::with('brands')->where('created_at', '>=', Carbon::now()->subWeek())->orderBy('created_at', 'desc')->get();
            
            $aboutUs = HomeSetting::where('key', 'about_us')->first();

            return Inertia::render('Home', [
                'heroProducts' => $heroProducts,
                'brands' => $brands,
                'categories' => $categories,
                'popularProducts' => $popularProducts,
                'newArrivals' => $newArrivals,
                'aboutUs' => $aboutUs ? $aboutUs->value : null,
            ]);
        } catch (\Throwable $e) {
            return Inertia::render('Home', [
                'heroProducts' => [],
                'brands' => [],
                'categories' => [],
                'popularProducts' => [],
                'newArrivals' => [],
                'aboutUs' => null,
            ]);
        }
    }
}
