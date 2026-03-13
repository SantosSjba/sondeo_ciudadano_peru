<?php

namespace App\Domain\Sondeo\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Estima "usuarios en línea" según quienes consultan resultados recientemente.
 * Cada GET /api/sondeo/results registra la huella anónima con marca de tiempo.
 */
final class SondeoPresenceTracker
{
    private const TTL_SECONDS = 180;

    private const ONLINE_WINDOW = 75;

    private const MAX_ENTRIES = 5000;

    public function touchAndCount(int $campaignId, string $fingerprintHash): int
    {
        $key = 'sondeo_presence_'.$campaignId;
        $now = time();

        /** @var list<array{h: string, t: int}> $list */
        $list = Cache::get($key, []);

        // Mantener solo ventana reciente + últimos MAX_ENTRIES por tamaño
        $list = array_values(array_filter($list, static fn (array $row): bool => $row['t'] > $now - self::TTL_SECONDS));

        // Último visto por huella (una entrada por visitante activo)
        $byHash = [];
        foreach ($list as $row) {
            $h = $row['h'];
            $byHash[$h] = max($byHash[$h] ?? 0, $row['t']);
        }
        $byHash[$fingerprintHash] = $now;

        $list = [];
        foreach ($byHash as $h => $t) {
            $list[] = ['h' => $h, 't' => $t];
        }

        usort($list, static fn (array $a, array $b): int => $b['t'] <=> $a['t']);
        if (count($list) > self::MAX_ENTRIES) {
            $list = array_slice($list, 0, self::MAX_ENTRIES);
        }

        Cache::put($key, $list, self::TTL_SECONDS);

        $cutoff = $now - self::ONLINE_WINDOW;

        return count(array_filter($list, static fn (array $row): bool => $row['t'] >= $cutoff));
    }
}
