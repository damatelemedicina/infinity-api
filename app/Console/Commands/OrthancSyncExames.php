<?php

namespace App\Console\Commands;

use App\Models\OrthancSyncState;
use App\Services\Orthanc\OrthancClient;
use App\Services\Orthanc\OrthancExameImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Roda um único ciclo de consulta no feed /changes do Orthanc e importa cada
 * estudo estável pra tabela exames. Feito pra ser chamado repetidamente por um
 * loop externo (ver infinity/docker/docker-compose.yml, serviço
 * "orthanc-sync") — o comando em si não fica em loop nem dorme, o que
 * mantém ele simples de rodar e testar isoladamente.
 *
 * Reage a "StableStudy" em vez de "NewInstance": o Orthanc só dispara
 * StableStudy quando um estudo para de receber instâncias novas, que é o
 * momento certo pra agrupar o estudo inteiro por tipo (StudyInstanceUID +
 * tipoExame) e criar um exame por grupo — igual um upload de zip de lote faz
 * — em vez de criar um exame por instância conforme elas vão chegando.
 */
class OrthancSyncExames extends Command
{
    protected $signature = 'orthanc:sync-exames {--limit=50 : Max Orthanc changes to fetch per cycle}';

    protected $description = 'Poll Orthanc for stable studies since the last cursor and import them as exames, grouped by type';

    public function handle(OrthancClient $client, OrthancExameImporter $importer): int
    {
        $state = OrthancSyncState::first();
        if (!$state) {
            $state = OrthancSyncState::create(['last_change_id' => 0]);
        }

        $limit = (int) $this->option('limit');
        $imported = 0;
        $skipped = 0;
        $failed = 0;

        // Safety cap: bounds how many /changes pages one cycle drains, so a bug in
        // Orthanc's "Done" flag (or a very large backlog) can't hang this process
        // forever and starve the outer poll loop.
        $maxPages = 200;

        for ($page = 0; $page < $maxPages; $page++) {
            $result = $client->getChangesSince($state->last_change_id, $limit);
            $changes = $result['Changes'] ?? [];

            foreach ($changes as $change) {
                if (($change['ChangeType'] ?? null) !== 'StableStudy') {
                    continue;
                }

                $studyId = $change['ID'];

                try {
                    $created = $importer->importStudy($client, $studyId);
                    $created ? $imported++ : $skipped++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::error("OrthancSync: falha ao importar estudo {$studyId}: " . $e->getMessage(), [
                        'exception' => $e,
                    ]);
                }
            }

            $state->last_change_id = $result['Last'] ?? $state->last_change_id;
            $state->save();

            if (!empty($result['Done'])) {
                break;
            }
        }

        $this->info("OrthancSync: importados={$imported} ignorados={$skipped} falhas={$failed} cursor={$state->last_change_id}");

        return self::SUCCESS;
    }
}
