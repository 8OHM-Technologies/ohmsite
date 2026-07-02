<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function toggle(Product $product)
    {
        $user = Auth::user();
        
        if (!$user) {
            return back()->with('error', 'You must be logged in to favorite products.');
        }

        $isFavorite = $user->favorites()->where('product_id', $product->id)->exists();

        if ($isFavorite) {
            $user->favorites()->detach($product->id);
            $message = 'Removed from favorites';
        } else {
            $user->favorites()->attach($product->id);
            $message = 'Added to favorites';
        }

        if (request()->wantsJson()) {
            return response()->json([
                'is_favorite' => !$isFavorite,
                'message' => $message
            ]);
        }

        return back()->with('success', $message);
    }

    public function index()
    {
        $user = Auth::user();
        $favorites = $user->favorites()->with(['brands', 'category'])->get();
        
        return inertia('Favorites/Index', [
            'favorites' => $favorites
        ]);
    }
}
