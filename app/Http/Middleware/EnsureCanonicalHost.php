<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanonicalHost
{
    /**
     * Handle an incoming request.
     * Redirect www subdomains to the non-www canonical apex domain with a 301 Permanent Redirect.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (str_starts_with($host, 'www.')) {
            $canonicalHost = substr($host, 4);
            $scheme = $request->isSecure() ? 'https' : 'http';
            $targetUrl = $scheme . '://' . $canonicalHost . $request->getRequestUri();

            return redirect()->to($targetUrl, 301);
        }

        return $next($request);
    }
}
