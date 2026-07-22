<?php

namespace App\Http\Middleware;

use App\Models\ApiCall;
use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeveloperApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'API key is missing.'], 401);
        }

        $apiKey = ApiKey::where('key', $token)->first();

        if (! $apiKey) {
            return response()->json(['error' => 'Invalid API key.'], 401);
        }

        $user = $apiKey->user;

        if (! $user) {
            return response()->json(['error' => 'User not found.'], 401);
        }

        if ($user->is_banned) {
            return response()->json([
                'error' => 'Your account has been banned: '.($user->ban_reason ?? 'No reason provided'),
            ], 403);
        }

        // Verify that the customer has active developer-api subscription / pro-analytics
        if (! $user->hasApiAccess()) {
            return response()->json(['error' => 'Active Developer API or Pro Analytics subscription is required.'], 403);
        }

        // Check rate limits (exclude admin)
        if (! $user->isAdmin()) {
            $limit = $user->getApiCallLimit();
            $callsCount = ApiCall::where('user_id', $user->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            if ($callsCount >= $limit) {
                return response()->json(['error' => 'API rate limit exceeded for this month.'], 429);
            }
        }

        // Record the API call
        $apiCall = ApiCall::create([
            'user_id' => $user->id,
            'api_key_id' => $apiKey->id,
            'endpoint' => $request->path(),
            'ip_address' => $request->ip(),
        ]);

        // Log in the user for this request context
        auth()->onceUsingId($user->id);

        return $next($request);
    }
}
