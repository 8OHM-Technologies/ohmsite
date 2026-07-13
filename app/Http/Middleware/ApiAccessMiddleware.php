<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasApiSubscriptionAccess()) {
            return $next($request);
        }

        $productName = \App\Models\Product::where('slug', 'developer-api')->value('name') ?? 'Developer API';
        return redirect()->route('orders.index')->with('error', "You must have an active {$productName} subscription to access the API documentation.");
    }
}
