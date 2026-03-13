<?php

namespace App\Console\Commands;

use App\Models\SondeoCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Sincroniza photo_url y party_logo_url desde la API pública de Voto Informado (JNE).
 *
 * @see https://votoinformado.jne.gob.pe/presidente-vicepresidentes
 */
class SondeoSyncJneFotosCommand extends Command
{
    protected $signature = 'sondeo:sync-jne-fotos {--dry-run : Solo mostrar coincidencias, sin guardar}';

    protected $description = 'Rellena photo_url y party_logo_url de candidatos usando el listado oficial JNE (EG 2026)';

    private const API_LISTAR = 'https://web.jne.gob.pe/serviciovotoinformado/api/votoinf/listarCanditatos';

    private const BASE_FOTO = 'https://mpesije.jne.gob.pe/apidocs/';

    private const BASE_LOGO = 'https://sroppublico.jne.gob.pe/Consulta/Simbolo/GetSimbolo/';

    /** Elecciones generales 2026 (según front JNE) */
    private const ID_PROCESO = 124;

    private const ID_TIPO_PRESIDENCIAL = 1;

    public function handle(): int
    {
        $this->info('Consultando API JNE…');
        $response = Http::timeout(60)
            ->acceptJson()
            ->post(self::API_LISTAR, [
                'idProcesoElectoral' => self::ID_PROCESO,
                'strUbiDepartamento' => '',
                'idTipoEleccion' => self::ID_TIPO_PRESIDENCIAL,
            ]);

        if (! $response->successful()) {
            $this->error('API JNE no respondió OK: '.$response->status());

            return self::FAILURE;
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            $this->error('Respuesta sin array data');

            return self::FAILURE;
        }

        $byName = [];
        foreach ($data as $row) {
            $nombreCompleto = strtoupper(trim(implode(' ', array_filter([
                $row['strNombres'] ?? '',
                $row['strApellidoPaterno'] ?? '',
                $row['strApellidoMaterno'] ?? '',
            ]))));
            if ($nombreCompleto === '') {
                continue;
            }
            $foto = isset($row['strNombre']) ? self::BASE_FOTO.ltrim((string) $row['strNombre'], '/') : null;
            $idOp = (int) ($row['idOrganizacionPolitica'] ?? 0);
            $logo = $idOp > 0 ? self::BASE_LOGO.$idOp : null;
            $byName[$nombreCompleto] = ['foto' => $foto, 'logo' => $logo, 'partido' => $row['strOrganizacionPolitica'] ?? ''];
        }

        $dry = (bool) $this->option('dry-run');
        $updated = 0;
        $missing = [];

        foreach (SondeoCandidate::query()->cursor() as $c) {
            $key = strtoupper(preg_replace('/\s+/', ' ', trim($c->name)));
            if (! isset($byName[$key])) {
                $missing[] = $c->name;

                continue;
            }
            $foto = $byName[$key]['foto'];
            $logo = $byName[$key]['logo'];
            if ($dry) {
                $this->line("✓ {$c->name} → foto + logo");
                $updated++;

                continue;
            }
            $c->photo_url = $foto;
            $c->party_logo_url = $logo;
            $c->save();
            $updated++;
        }

        $this->info("Coincidencias JNE: {$updated}".($dry ? ' (dry-run)' : ' actualizados en BD'));
        if ($missing !== []) {
            $this->warn('Sin coincidencia exacta de nombre ('.count($missing).'):');
            foreach (array_slice($missing, 0, 15) as $m) {
                $this->line('  - '.$m);
            }
            if (count($missing) > 15) {
                $this->line('  …');
            }
        }

        $this->newLine();
        $this->comment('Fuentes: listado '.self::API_LISTAR);
        $this->comment('Foto: '.self::BASE_FOTO.'{strNombre}');
        $this->comment('Logo: '.self::BASE_LOGO.'{idOrganizacionPolitica}');

        return self::SUCCESS;
    }
}
