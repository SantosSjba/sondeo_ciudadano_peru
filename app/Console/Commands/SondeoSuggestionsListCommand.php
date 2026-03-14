<?php

namespace App\Console\Commands;

use App\Models\SondeoSuggestion;
use Illuminate\Console\Command;

final class SondeoSuggestionsListCommand extends Command
{
    protected $signature = 'sondeo:suggestions {--last=20 : Cantidad a mostrar}';

    protected $description = 'Lista sugerencias de mejora guardadas por los usuarios';

    public function handle(): int
    {
        $n = max(1, (int) $this->option('last'));
        $rows = SondeoSuggestion::query()->orderByDesc('id')->limit($n)->get();
        if ($rows->isEmpty()) {
            $this->info('No hay sugerencias aún.');

            return self::SUCCESS;
        }
        foreach ($rows as $s) {
            $this->line('— #'.$s->id.' · '.$s->created_at);
            $this->line($s->message);
            if ($s->contact_email) {
                $this->line('  email: '.$s->contact_email);
            }
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
