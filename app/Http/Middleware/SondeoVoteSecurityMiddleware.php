<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reduce bots directos al POST /api/sondeo/vote (curl sin navegador).
 * No sustituye CSRF ni throttle; complementa honeypot y tiempo mínimo en página.
 */
final class SondeoVoteSecurityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production')) {
            return $next($request);
        }

        $ua = (string) $request->userAgent();
        if (strlen(trim($ua)) < 12) {
            return response()->json(['ok' => false, 'code' => 'bot_ua'], 422);
        }

        $allowedHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($allowedHost === false || $allowedHost === null || $allowedHost === '') {
            return $next($request);
        }

        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        $originHost = $origin ? parse_url($origin, PHP_URL_HOST) : null;
        $refererHost = $referer ? parse_url($referer, PHP_URL_HOST) : null;

        $match = ($originHost === $allowedHost) || ($refererHost === $allowedHost);
        if (! $match) {
            return response()->json(['ok' => false, 'code' => 'bot_origin'], 422);
        }

        return $next($request);
    }
}
