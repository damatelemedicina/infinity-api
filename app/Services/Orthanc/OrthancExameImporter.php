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
        $institutionNameId = $this->computeInstitutionNameId($institutionNameRaw);

        $cliente = $institutionNameId ? Cliente::where('institution_name_id', $institutionNameId)->first() : null;
        if (!$cliente) {
            $mensagem = "OrthancSync: instância {$instanceId} sem Cliente correspondente. "
                . "InstitutionName=[" . $this->sanitizeTextForDisplay($institutionNameRaw) . "] "
                . "InstitutionNameId=[" . ($institutionNameId ?? '(vazio)') . "] — "
                . "cadastre esse Institution Name Id no cliente correto pra próxima vez ser reconhecido.";
            Log::warning($mensagem);
            $this->tentaRegistrarAlerta($mensagem);
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
     * Mirrors ExameController::getInstitutionName(): Orthanc/DICOM pads short
     * strings with a trailing null byte, hence the "drop the last hex byte"
     * trim before matching Cliente.institution_name_id.
     *
     * IMPORTANT: this does NOT match how the web UI's "Gerar Institution Name
     * Id" checkbox computes the value (plain bin2hex(text), no trim) — typing
     * the institution name into the client form and letting it "Gerar" will
     * usually produce a value one byte too long to ever match a real DICOM
     * instance. Always paste the exact InstitutionNameId logged/alerted here
     * into the client's "Institution Name Id" field instead of generating it.
     */
    protected function computeInstitutionNameId(?string $institutionNameRaw): ?string
    {
        if (!$institutionNameRaw) {
            return null;
        }

        return substr(bin2hex($institutionNameRaw), 0, -2);
    }

    /**
     * DICOM devices often send non-UTF8 bytes (commonly Windows-1252/Latin-1
     * for accented characters, via SpecificCharacterSet ISO_IR 100) in text
     * tags like InstitutionName. Storing that raw in a UTF-8 MySQL column
     * (e.g. operacoes.operacao) throws error 1366 and silently drops the
     * alert — this is display-only sanitization, never used in the byte-exact
     * computeInstitutionNameId() matching above.
     */
    protected function sanitizeTextForDisplay(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        // Last resort: substitute whatever still isn't valid UTF-8 rather
        // than let a second encoding fail the DB insert again.
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    /**
     * Surfaces unmatched-institution warnings in the app's own Operações
     * screen (registraAlerta), not just the server log, so whoever manages
     * clientes can actually see and act on it. Uses the "sistema" user/cliente
     * (no login session exists in this CLI context) — if those aren't set up,
     * this silently falls back to the Log::warning already issued by the caller.
     */
    protected function tentaRegistrarAlerta(string $mensagem): void
    {
        try {
            $this->registraAlerta($mensagem);
        } catch (\Throwable $e) {
            Log::debug('OrthancSync: não foi possível registrar alerta em Operações: ' . $e->getMessage());
        }
    }
}
