<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Convenience tool: computes the institution_name_id the same way
 * OrthancExameImporter does, from a plain text guess of the InstitutionName
 * a device sends. Useful to pre-register a Cliente before any real exam
 * arrives, but the DICOM tag itself is the source of truth — if the text
 * typed here doesn't exactly match what the equipment actually sends
 * (case, accents, trailing spaces), the id won't match either. Whenever
 * possible, prefer the InstitutionNameId already computed and shown on the
 * Operações screen from a real received exam.
 */
class OrthancInstitutionId extends Command
{
    protected $signature = 'orthanc:institution-id {texto : O InstitutionName a simular, ex: "CRJ - Centro Radiologico Jundiai"}';

    protected $description = 'Calcula o institution_name_id a partir de um texto (aproximação — prefira o valor real vindo de um exame)';

    public function handle(): int
    {
        $texto = $this->argument('texto');

        // DICOM pads string values to an even byte length with a trailing
        // space when needed; the matching code always drops the last byte.
        // Simulating that padding here keeps this in sync with real DICOM
        // instances of either length.
        $padded = strlen($texto) % 2 !== 0 ? $texto . ' ' : $texto;
        $id = substr(bin2hex($padded), 0, -2);

        $this->info("Texto:                 {$texto}");
        $this->info("InstitutionNameId:      {$id}");
        $this->warn('Isso é uma aproximação. Se o aparelho mandar o InstitutionName com capitalização, acento ou');
        $this->warn('espaços diferentes do que você digitou aqui, esse id NÃO vai bater. Prefira sempre copiar o');
        $this->warn('valor exato mostrado na tela Operações quando um exame real chegar.');

        return self::SUCCESS;
    }
}
