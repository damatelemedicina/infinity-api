<?php

namespace App\Utils;

/**
 * Single source of truth for computing a Cliente's institution_name_id,
 * so the value typed/generated in the Cliente screen and the value matched
 * against real Orthanc exams are always computed the same way.
 *
 * DICOM pads string tags to an even byte length with a trailing space when
 * needed, so this always drops the last hex byte before returning — this is
 * a hard constant of the format (see ExameController::getInstitutionName()
 * and App\Services\Orthanc\OrthancExameImporter), not a design choice made
 * here.
 */
class OrthancInstitutionName
{
    /**
     * From bytes read directly off a DICOM tag (already whatever encoding
     * the sending device used) — no re-encoding, just the format's trim.
     */
    public static function computeIdFromRawBytes(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        return substr(bin2hex($raw), 0, -2);
    }

    /**
     * From plain text typed by a person (e.g. the Cliente screen's
     * "Institution Name" field, submitted as UTF-8 by the browser).
     *
     * Best-effort only: real DICOM devices commonly send accented text as
     * Windows-1252/Latin-1, not UTF-8, so this converts to that encoding
     * before hashing — matches the common case (as seen with real CRJ/ECL
     * exams), but a device using a different SpecificCharacterSet would
     * still produce a different id. When in doubt, prefer the exact id
     * shown on the Operações screen from a real received exam over this.
     */
    public static function computeIdFromText(string $text): string
    {
        $bytes = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        if ($bytes === false || $bytes === null) {
            $bytes = $text;
        }

        // Mirror DICOM's even-length padding so odd- and even-length names
        // both land on the same trimmed id a real instance would produce.
        if (strlen($bytes) % 2 !== 0) {
            $bytes .= ' ';
        }

        return substr(bin2hex($bytes), 0, -2);
    }
}
