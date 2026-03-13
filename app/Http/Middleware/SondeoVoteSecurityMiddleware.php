<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Capa de seguridad del endpoint POST /api/sondeo/vote.
 * Actúa ANTES del controlador. No reemplaza CSRF, throttle ni honeypot.
 *
 * Detecciones:
 *  1. User-Agent vacío / muy corto (bots sin UA).
 *  2. User-Agent de herramientas de automatización conocidas.
 *  3. Origen / Referer inválido (petición desde fuera del sitio).
 *  4. Falta de cabeceras que siempre envían los navegadores reales.
 *  5. Abuso por IP: demasiados intentos distintos en poco tiempo.
 */
final class SondeoVoteSecurityMiddleware
{
    /** Patrones de UA que delatan automatización o scraping */
    private const BOT_UA_PATTERNS = [
        '/HeadlessChrome/i',
        '/PhantomJS/i',
        '/Puppeteer/i',
        '/Selenium/i',
        '/Python-urllib/i',
        '/python-requests/i',
        '/Go-http-client/i',
        '/curl\//i',
        '/wget\//i',
        '/libwww-perl/i',
        '/axios/i',
        '/node-fetch/i',
        '/okhttp/i',
        '/Java\/\d/i',
        '/Scrapy/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // En local no se bloquea (desarrollo)
        if (! app()->environment('production', 'staging')) {
            return $next($request);
        }

        $ua = (string) $request->userAgent();

        // ── 1. UA ausente o demasiado corto ─────────────────────
        if (strlen(trim($ua)) < 20) {
            return $this->block('bot_ua');
        }

        // ── 2. UA de herramienta de automatización ───────────────
        foreach (self::BOT_UA_PATTERNS as $pattern) {
            if (preg_match($pattern, $ua)) {
                return $this->block('bot_ua');
            }
        }

        // ── 3. Origen / Referer ──────────────────────────────────
        $allowedHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($allowedHost) {
            $origin  = $request->header('Origin');
            $referer = $request->header('Referer');
            $oHost   = $origin  ? parse_url($origin,  PHP_URL_HOST) : null;
            $rHost   = $referer ? parse_url($referer, PHP_URL_HOST) : null;

            if ($oHost !== $allowedHost && $rHost !== $allowedHost) {
                return $this->block('bot_origin');
            }
        }

        // ── 4. Cabeceras mínimas de un navegador real ────────────
        // Accept siempre lo manda el navegador; si falta probablemente es un script
        $accept = (string) $request->header('Accept', '');
        if (strlen($accept) < 3) {
            return $this->block('bot_headers');
        }

        // ── 5. Abuso por IP ──────────────────────────────────────
        $ip        = $request->ip() ?? '0.0.0.0';
        $cacheKey  = 'sondeo_ip_abuse:' . md5($ip);
        $maxPerHour = (int) config('sondeo.max_attempts_per_ip_per_hour', 20);

        $attempts = (int) Cache::get($cacheKey, 0);
        if ($attempts >= $maxPerHour) {
            return $this->block('ip_abuse');
        }
        Cache::put($cacheKey, $attempts + 1, 3600);

        return $next($request);
    }

    private function block(string $code): Response
    {
        return response()->json(['ok' => false, 'code' => $code], 422);
    }
}
