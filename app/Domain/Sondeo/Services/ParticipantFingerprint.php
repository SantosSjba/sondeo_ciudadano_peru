<?php

namespace App\Domain\Sondeo\Services;

use Illuminate\Http\Request;

/**
 * Huella anónima derivada (no almacenamos IP en claro).
 * Objetivo: una participación por campaña por dispositivo/red típica, sin identidad personal.
 */
final class ParticipantFingerprint
{
    public function hash(Request $request): string
    {
        $ip = $request->ip() ?? '';
        $ua = substr((string) $request->userAgent(), 0, 512);
        $salt = config('sondeo.fingerprint_pepper') ?: config('app.key');

        return hash('sha256', $ip.'|'.$ua.'|'.$salt);
    }
}
