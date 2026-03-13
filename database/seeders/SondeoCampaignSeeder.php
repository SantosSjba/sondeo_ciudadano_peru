<?php

namespace Database\Seeders;

use App\Models\SondeoCampaign;
use App\Models\SondeoCandidate;
use Illuminate\Database\Seeder;

/**
 * Lista de candidatos para la campaña del sondeo (configuración editorial del sitio).
 * Fotos/logos: columnas photo_url / party_logo_url o comando sondeo:sync-jne-fotos.
 */
class SondeoCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $campaign = SondeoCampaign::query()->updateOrCreate(
            ['slug' => config('sondeo.default_campaign_slug', 'presidencial-peru')],
            [
                'title' => 'Sondeo ciudadano — ¿Por quién votarías hoy para Presidente del Perú?',
                'description' => 'Participación voluntaria y anónima. No es encuesta científica ni resultado oficial.',
                'is_active' => true,
            ],
        );

        $candidates = [
            [
                'name' => 'PABLO ALFONSO LOPEZ CHAU NAVA',
                'party_name' => 'AHORA NACION - AN',
                'short_label' => 'AN',
                'sort_order' => 1,
            ],
            [
                'name' => 'RONALD DARWIN ATENCIO SOTOMAYOR',
                'party_name' => 'ALIANZA ELECTORAL VENCEREMOS',
                'short_label' => 'VENCEREMOS',
                'sort_order' => 2,
            ],
            [
                'name' => 'CESAR ACUÑA PERALTA',
                'party_name' => 'ALIANZA PARA EL PROGRESO',
                'short_label' => 'APP',
                'sort_order' => 3,
            ],
            [
                'name' => 'JOSE DANIEL WILLIAMS ZAPATA',
                'party_name' => 'AVANZA PAIS - PARTIDO DE INTEGRACION SOCIAL',
                'short_label' => 'AVANZA PAÍS',
                'sort_order' => 4,
            ],
            [
                'name' => 'ALVARO GONZALO PAZ DE LA BARRA FREIGEIRO',
                'party_name' => 'FE EN EL PERU',
                'short_label' => 'FE EN EL PERÚ',
                'sort_order' => 5,
            ],
            [
                'name' => 'KEIKO SOFIA FUJIMORI HIGUCHI',
                'party_name' => 'FUERZA POPULAR',
                'short_label' => 'FP',
                'sort_order' => 6,
            ],
            [
                'name' => 'FIORELLA GIANNINA MOLINELLI ARISTONDO',
                'party_name' => 'FUERZA Y LIBERTAD',
                'short_label' => 'FUERZA Y LIB.',
                'sort_order' => 7,
            ],
            [
                'name' => 'ROBERTO HELBERT SANCHEZ PALOMINO',
                'party_name' => 'JUNTOS POR EL PERU',
                'short_label' => 'JPP',
                'sort_order' => 8,
            ],
            [
                'name' => 'RAFAEL JORGE BELAUNDE LLOSA',
                'party_name' => 'LIBERTAD POPULAR',
                'short_label' => 'LIB. POPULAR',
                'sort_order' => 9,
            ],
            [
                'name' => 'PITTER ENRIQUE VALDERRAMA PEÑA',
                'party_name' => 'PARTIDO APRISTA PERUANO',
                'short_label' => 'PAP',
                'sort_order' => 10,
            ],
            [
                'name' => 'RICARDO PABLO BELMONT CASSINELLI',
                'party_name' => 'PARTIDO CIVICO OBRAS',
                'short_label' => 'OBRAS',
                'sort_order' => 11,
            ],
            [
                'name' => 'NAPOLEON BECERRA GARCIA',
                'party_name' => 'PARTIDO DE LOS TRABAJADORES Y EMPRENDEDORES PTE - PERU',
                'short_label' => 'PTE',
                'sort_order' => 12,
            ],
            [
                'name' => 'JORGE NIETO MONTESINOS',
                'party_name' => 'PARTIDO DEL BUEN GOBIERNO',
                'short_label' => 'P. BUEN GOB.',
                'sort_order' => 13,
            ],
            [
                'name' => 'CHARLIE CARRASCO SALAZAR',
                'party_name' => 'PARTIDO DEMOCRATA UNIDO PERU',
                'short_label' => 'PDUP',
                'sort_order' => 14,
            ],
            [
                'name' => 'ALEX GONZALES CASTILLO',
                'party_name' => 'PARTIDO DEMOCRATA VERDE',
                'short_label' => 'PDV',
                'sort_order' => 15,
            ],
            [
                'name' => 'ARMANDO JOAQUIN MASSE FERNANDEZ',
                'party_name' => 'PARTIDO DEMOCRATICO FEDERAL',
                'short_label' => 'PDF',
                'sort_order' => 16,
            ],
            [
                'name' => 'GEORGE PATRICK FORSYTH SOMMER',
                'party_name' => 'PARTIDO DEMOCRATICO SOMOS PERU',
                'short_label' => 'SOMOS PERÚ',
                'sort_order' => 17,
            ],
            [
                'name' => 'LUIS FERNANDO OLIVERA VEGA',
                'party_name' => 'PARTIDO FRENTE DE LA ESPERANZA 2021',
                'short_label' => 'ESPERANZA',
                'sort_order' => 18,
            ],
            [
                'name' => 'MESIAS ANTONIO GUEVARA AMASIFUEN',
                'party_name' => 'PARTIDO MORADO',
                'short_label' => 'P. MORADO',
                'sort_order' => 19,
            ],
            [
                'name' => 'CARLOS GONSALO ALVAREZ LOAYZA',
                'party_name' => 'PARTIDO PAIS PARA TODOS',
                'short_label' => 'PAÍS P/TODOS',
                'sort_order' => 20,
            ],
            [
                'name' => 'HERBERT CALLER GUTIERREZ',
                'party_name' => 'PARTIDO PATRIOTICO DEL PERU',
                'short_label' => 'PPP',
                'sort_order' => 21,
            ],
            [
                'name' => 'YONHY LESCANO ANCIETA',
                'party_name' => 'PARTIDO POLITICO COOPERACION POPULAR',
                'short_label' => 'COOP. POPULAR',
                'sort_order' => 22,
            ],
            [
                'name' => 'WOLFGANG MARIO GROZO COSTA',
                'party_name' => 'PARTIDO POLITICO INTEGRIDAD DEMOCRATICA',
                'short_label' => 'P. INTEGRIDAD',
                'sort_order' => 23,
            ],
            [
                'name' => 'VLADIMIR ROY CERRON ROJAS',
                'party_name' => 'PARTIDO POLITICO NACIONAL PERU LIBRE',
                'short_label' => 'PERÚ LIBRE',
                'sort_order' => 24,
            ],
            [
                'name' => 'FRANCISCO ERNESTO DIEZ-CANSECO TÁVARA',
                'party_name' => 'PARTIDO POLITICO PERU ACCION',
                'short_label' => 'PERÚ ACCIÓN',
                'sort_order' => 25,
            ],
            [
                'name' => 'MARIO ENRIQUE VIZCARRA CORNEJO',
                'party_name' => 'PARTIDO POLITICO PERU PRIMERO',
                'short_label' => 'PERÚ PRIMERO',
                'sort_order' => 26,
            ],
            [
                'name' => 'WALTER GILMER CHIRINOS PURIZAGA',
                'party_name' => 'PARTIDO POLITICO PRIN',
                'short_label' => 'PRIN',
                'sort_order' => 27,
            ],
            [
                'name' => 'ALFONSO CARLOS ESPA Y GARCES-ALVEAR',
                'party_name' => 'PARTIDO SICREO',
                'short_label' => 'SICREO',
                'sort_order' => 28,
            ],
            [
                'name' => 'CARLOS ERNESTO JAICO CARRANZA',
                'party_name' => 'PERU MODERNO',
                'short_label' => 'PERÚ MODERNO',
                'sort_order' => 29,
            ],
            [
                'name' => 'JOSE LEON LUNA GALVEZ',
                'party_name' => 'PODEMOS PERU',
                'short_label' => 'PODEMOS',
                'sort_order' => 30,
            ],
            [
                'name' => 'MARIA SOLEDAD PEREZ TELLO DE RODRIGUEZ',
                'party_name' => 'PRIMERO LA GENTE - COMUNIDAD, ECOLOGIA, LIBERTAD Y PROGRESO',
                'short_label' => 'PRIMERO LA GENTE',
                'sort_order' => 31,
            ],
            [
                'name' => 'PAUL DAVIS JAIMES BLANCO',
                'party_name' => 'PROGRESEMOS',
                'short_label' => 'PROGRESEMOS',
                'sort_order' => 32,
            ],
            [
                'name' => 'RAFAEL BERNARDO LOPEZ ALIAGA CAZORLA',
                'party_name' => 'RENOVACION POPULAR',
                'short_label' => 'RP',
                'sort_order' => 33,
            ],
            [
                'name' => 'ANTONIO ORTIZ VILLANO',
                'party_name' => 'SALVEMOS AL PERU',
                'short_label' => 'SALVEMOS',
                'sort_order' => 34,
            ],
            [
                'name' => 'ROSARIO DEL PILAR FERNANDEZ BAZAN',
                'party_name' => 'UN CAMINO DIFERENTE',
                'short_label' => 'UN CAMINO',
                'sort_order' => 35,
            ],
            [
                'name' => 'ROBERTO ENRIQUE CHIABRA LEON',
                'party_name' => 'UNIDAD NACIONAL',
                'short_label' => 'UNIDAD NAC.',
                'sort_order' => 36,
            ],
        ];

        foreach ($candidates as $row) {
            SondeoCandidate::query()->firstOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'name' => $row['name'],
                ],
                [
                    'party_name' => $row['party_name'],
                    'short_label' => $row['short_label'],
                    'photo_url' => null,
                    'party_logo_url' => null,
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
