<?php

namespace Database\Seeders;

use App\Models\SondeoCampaign;
use App\Models\SondeoCandidate;
use Illuminate\Database\Seeder;

/**
 * Candidatos de ejemplo: sustituir nombres según el proceso electoral vigente (ONPE es la fuente oficial).
 */
class SondeoCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $campaign = SondeoCampaign::query()->updateOrCreate(
            ['slug' => config('sondeo.default_campaign_slug', 'presidencial-peru')],
            [
                'title' => 'Sondeo ciudadano — Intención de voto presidencial (Perú)',
                'description' => 'Participación voluntaria y anónima. No es encuesta científica ni resultado oficial.',
                'is_active' => true,
            ],
        );

        $names = [
            ['name' => 'Opción / candidato 1', 'short_label' => 'C1', 'sort_order' => 1],
            ['name' => 'Opción / candidato 2', 'short_label' => 'C2', 'sort_order' => 2],
            ['name' => 'Opción / candidato 3', 'short_label' => 'C3', 'sort_order' => 3],
            ['name' => 'Voto en blanco / anular', 'short_label' => 'VB', 'sort_order' => 99],
        ];

        foreach ($names as $row) {
            SondeoCandidate::query()->firstOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'name' => $row['name'],
                ],
                [
                    'short_label' => $row['short_label'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
