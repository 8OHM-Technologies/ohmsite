<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscribedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isSubscribed()) {
            return $next($request);
        }

        return redirect()->route('profile.edit')->with('error', 'Please subscribe to the Pro Analytics package to access this section.');
    }
}
