<?php

namespace App\Domain\Sondeo\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Detecta patrones de abuso en el momento de registrar un voto.
 *
 * Señales evaluadas:
 *  A. Puntaje de interacción humana enviado por el cliente (mouse/touch).
 *  B. Huella del navegador (canvas, WebGL, screen) enviada por el cliente.
 *  C. Número de huellas de navegador distintas desde la misma IP en 1 h.
 *  D. Tiempo mínimo en página antes de votar.
 *
 * IMEI: los navegadores web NO pueden acceder al IMEI del dispositivo
 * por políticas de privacidad del sistema operativo. Solo apps nativas
 * (Android/iOS) pueden leerlo bajo permisos especiales. Lo que hacemos
 * aquí es equivalente desde web: canvas fingerprint + WebGL renderer
 * identifican el GPU/dispositivo de forma pasiva y anónima.
 */
final class VoteAbuseDetector
{
    /**
     * @throws RuntimeException si se detecta abuso
     */
    public function check(
        Request $request,
        string  $fingerprintHash,
        string  $browserFp,
        int     $interactScore,
        int     $clientElapsedMs,
        bool    $isChange,
    ): void {
        $ip = $request->ip() ?? '0.0.0.0';

        // ── A. Puntaje de interacción ────────────────────────────
        // Primer voto: exigimos algo de interacción (movimiento, toque, scroll)
        $minInteract = (int) config('sondeo.min_interact_score', 3);
        if (! $isChange && $interactScore < $minInteract) {
            throw new RuntimeException('too_fast');
        }

        // ── B. Huella del navegador vacía en primer voto ─────────
        // Si el cliente no pudo generarla o la envió vacía en el primer voto
        // es sospechoso (p. ej. fetch directo desde curl con body manual).
        // En cambios de voto se relaja este requisito.
        if (! $isChange && strlen($browserFp) < 8) {
            throw new RuntimeException('bot_ua');
        }

        // ── C. Diversidad de huellas por IP (detección de granja) ─
        // Si desde la misma IP llegan muchas huellas de navegador distintas
        // en 1 hora, es probable una granja de bots o VPN-rotante.
        $maxFpPerIp = (int) config('sondeo.max_browser_fp_per_ip_per_hour', 8);
        if ($browserFp !== '' && $maxFpPerIp > 0) {
            $ipFpKey = 'sondeo_ip_fp:' . md5($ip);
            /** @var string[] $seenFps */
            $seenFps = Cache::get($ipFpKey, []);
            if (! in_array($browserFp, $seenFps, true)) {
                $seenFps[] = $browserFp;
                Cache::put($ipFpKey, $seenFps, 3600);
                if (count($seenFps) > $maxFpPerIp) {
                    throw new RuntimeException('ip_abuse');
                }
            }
        }

        // ── D. Tiempo mínimo en página ───────────────────────────
        // (ya validado parcialmente en CastVoteHandler, pero reforzamos aquí)
        $minMs = $isChange ? 2000 : 3500;
        if ($clientElapsedMs < $minMs) {
            throw new RuntimeException('too_fast');
        }
    }
}
