<?php

namespace App\Console\Commands;

use App\Models\OrthancSyncState;
use App\Services\Orthanc\OrthancClient;
use App\Services\Orthanc\OrthancExameImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs a single poll cycle against Orthanc's /changes feed and imports any
 * new DICOM instance into the exames table. Meant to be invoked repeatedly
 * by an external loop (see infinity/docker/docker-compose.yml, service
 * "orthanc-sync") — this command itself does not loop or sleep, which keeps
 * it simple to run and test in isolation.
 */
class OrthancSyncExames extends Command
{
    protected $signature = 'orthanc:sync-exames {--limit=50 : Max Orthanc changes to fetch per cycle}';

    protected $description = 'Poll Orthanc for new DICOM instances since the last cursor and import them as exames';

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
                if (($change['ChangeType'] ?? null) !== 'NewInstance') {
                    continue;
                }

                $instanceId = $change['ID'];

                try {
                    $bytes = $client->downloadInstanceFile($instanceId);
                    $created = $importer->importInstance($bytes, $instanceId);
                    $created ? $imported++ : $skipped++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::error("OrthancSync: falha ao importar instância {$instanceId}: " . $e->getMessage(), [
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
