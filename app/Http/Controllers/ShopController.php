<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'brand', 'min_price', 'max_price', 'sort', 'search']);
        $cacheKey = 'products_page_' . md5(json_encode($filters) . $request->get('page', 1) . auth()->id());

        $products = cache()->remember($cacheKey, 300, function() use ($request) {
            $query = Product::with(['brands', 'category']);

            // Search
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Filtering
            if ($request->filled('category')) {
                $query->whereHas('category', function($q) use ($request) {
                    $q->where('name', $request->category);
                });
            }

            if ($request->filled('brand')) {
                $query->whereHas('brands', function($q) use ($request) {
                    $q->where('name', $request->brand);
                });
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting
            $sort = $request->get('sort', 'featured');
            switch ($sort) {
                case 'newest':
                    $query->latest();
                    break;
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                default:
                    $query->latest();
                    break;
            }

            $paginated = $query->paginate(12)->withQueryString();

            // Handle favorites inside the cached closure if needed, or better, outside
            return $paginated;
        });

        // Favorites check must be dynamic (outside cache or include auth()->id() in key)
        $user = auth()->user();
        if ($user) {
            $favorites = $user->favorites()->pluck('product_id')->toArray();
            $products->getCollection()->transform(function($product) use ($favorites) {
                $product->is_favorited = in_array($product->id, $favorites);
                return $product;
            });
        }

        $categories = cache()->remember('categories_all', 3600, fn() => Category::all());
        $brands = cache()->remember('brands_all', 3600, fn() => Brand::all());

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'filters' => $filters
        ]);
    }

    public function show(Product $product)
    {
        // Increment click count
        $product->increment('clicks');

        $product->load(['brands', 'category']);
        
        $user = auth()->user();
        $product->is_favorited = $user ? $user->favorites()->where('product_id', $product->id)->exists() : false;

        $relatedProducts = Product::with('brands')->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return Inertia::render('Shop/Show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts
        ]);
    }
}
