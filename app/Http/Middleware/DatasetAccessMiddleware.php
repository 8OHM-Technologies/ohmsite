<?php

namespace App\Http\Middleware;

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

        return redirect()->route('orders.index')->with('error', 'You must purchase the Once-off Dataset to access this download.');
    }
}
