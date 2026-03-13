<?php

namespace App\Domain\Sondeo\Services;

use Illuminate\Http\Request;

/**
 * Huella anónima derivada — ningún dato personal se almacena en claro.
 *
 * Composición:
 *  - IP real del cliente (tras proxy/CDN gracias a trustProxies).
 *  - User-Agent (primeros 512 caracteres).
 *  - Pepper secreto (SONDEO_FINGERPRINT_PEPPER en .env; si no, APP_KEY).
 *
 * Se usa HMAC-SHA-256 en vez de SHA simple para que el pepper no pueda
 * ser "quitado" por extensión de longitud (length-extension attack).
 *
 * Nota sobre IMEI: los navegadores web no pueden acceder al IMEI/UDID
 * del dispositivo (bloqueado por el SO y los navegadores por privacidad).
 * Solo apps nativas bajo permisos especiales pueden leerlo. La huella
 * aquí —canvas, WebGL, IP, UA— es el equivalente web anónimo.
 */
final class ParticipantFingerprint
{
    public function hash(Request $request): string
    {
        $ip   = $request->ip() ?? '';
        $ua   = substr((string) $request->userAgent(), 0, 512);
        $key  = config('sondeo.fingerprint_pepper') ?: config('app.key');

        // HMAC-SHA256: el pepper es la clave, no solo el salt concatenado
        return hash_hmac('sha256', $ip . '|' . $ua, (string) $key);
    }
}

