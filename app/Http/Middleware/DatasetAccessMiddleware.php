<?php

namespace App\Http\Middleware;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DatasetAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasOnceOffDatasetAccess()) {
            return $next($request);
        }

        $productName = Product::where('slug', 'once-off-dataset')->value('name') ?? 'Once-off Dataset';

        return redirect()->route('orders.index')->with('error', "You must purchase the {$productName} to access this download.");
    }
}
