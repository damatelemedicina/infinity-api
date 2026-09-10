<?php

namespace App\Services\Orthanc;

use App\Http\Controllers\ExameController;
use App\Models\Cliente;
use App\Utils\Dicom;
use Illuminate\Support\Facades\Log;

/**
 * Imports a single Orthanc DICOM instance into the `exames` table.
 *
 * Extends ExameController on purpose: insertDCM()/insertExame() already
 * contain the tested tag-mapping and exam-creation logic used by every
 * other exam-upload channel (DAMA Desktop, manual upload). This class only
 * adds the piece those flows don't have — resolving the Cliente/tenant from
 * an Orthanc instance with no logged-in session and no chave_transmissao
 * supplied by the caller — then hands off to the existing pipeline.
 *
 * setPropertiesByInstitutionName() was widened from private to protected
 * in ExameController to make this possible; no other behavior changed.
 */
class OrthancExameImporter extends ExameController
{
    /**
     * @param string $dcmContents Raw bytes of the DICOM instance, as downloaded from Orthanc.
     * @param string $instanceId  Orthanc instance ID, used only for the local temp filename.
     * @return bool true if an exam was created (or already existed via CRC dedupe), false if skipped.
     */
    public function importInstance(string $dcmContents, string $instanceId): bool
    {
        $relativePath = '/uploads/exames/lotes/' . \Str::random(40) . '.dcm';
        $localPath = $this->storePath($relativePath);

        if (!is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }
        file_put_contents($localPath, $dcmContents);

        // NOTE: $localPath is NOT a scratch temp file. insertDCM()/insertExame() store
        // this same relative path on the exam row (arquivo_exame) as a permanent pointer
        // used later to display/download the study, exactly like every other upload
        // channel does with files under /uploads/exames/lotes/. Only delete it below on
        // the "no exam was created" paths — never after a successful insertDCM() call.

        $dicom = Dicom::getInstance($localPath);
        $dicom->parse(['InstitutionName']);
        $institutionNameRaw = $dicom->value(0x0008, 0x0080);

        $cliente = $this->resolveClienteByInstitutionName($institutionNameRaw);
        if (!$cliente) {
            Log::warning("OrthancSync: instância {$instanceId} sem Cliente correspondente para InstitutionName, ignorada.");
            @unlink($localPath);
            return false;
        }

        if (empty($cliente->chave_transmissao)) {
            Log::warning("OrthancSync: Cliente {$cliente->id} resolvido para a instância {$instanceId}, mas não tem chave_transmissao configurada, ignorada.");
            @unlink($localPath);
            return false;
        }

        $this->setPropertiesByInstitutionName($cliente);

        $fileName = basename($relativePath);
        $this->insertDCM($relativePath, $fileName, $cliente->chave_transmissao, null);

        return true;
    }

    /**
     * Mirrors ExameController::getInstitutionName() + getClinicaByInstitutionName():
     * Orthanc/DICOM pads short strings with a trailing null byte, hence the
     * "drop the last hex byte" trim before matching Cliente.institution_name_id.
     */
    protected function resolveClienteByInstitutionName(?string $institutionNameRaw): ?Cliente
    {
        if (!$institutionNameRaw) {
            return null;
        }

        $hex = bin2hex($institutionNameRaw);
        $institutionNameId = substr($hex, 0, -2);

        return Cliente::where('institution_name_id', $institutionNameId)->first();
    }
}
