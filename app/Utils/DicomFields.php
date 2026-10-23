<?php

namespace App\Utils;

/**
 * Helpers puros (sem acesso a arquivo/banco) pra tolerar as variações de
 * preenchimento de tags DICOM entre fabricantes de raio-X: texto em encoding
 * errado/corrompido, parte do corpo com bytes inválidos, documento do paciente
 * com ou sem máscara, valores placeholder. Usado por ExameController.
 */
class DicomFields
{
    /**
     * Partes do corpo conhecidas, usadas só pra reparar valores com byte
     * corrompido (ex: Fuji grava "T\xD3RAX" mesmo declarando ISO_IR 100 e o
     * byte chega inválido). "?" casa com qualquer caractere.
     */
    private const BODY_PARTS = [
        'TORAX', 'THORAX', 'CHEST', 'COLUNA', 'CRANIO', 'ABDOME', 'ABDOMEN',
        'JOELHO', 'OMBRO', 'PUNHO', 'COTOVELO', 'TORNOZELO', 'QUADRIL',
        'CLAVICULA', 'ESCAPULA', 'UMERO', 'ANTEBRACO', 'MAO', 'PE', 'PELVE',
        'BACIA', 'FEMUR', 'TIBIA', 'COSTELAS', 'SEIOS DA FACE', 'CAVUM',
    ];

    /**
     * Texto cru de uma tag -> UTF-8 válido, sem nunca lançar. UTF-8 válido
     * passa direto; o resto é tratado como Windows-1252 (superset do Latin-1
     * / ISO_IR 100, o que os equipamentos brasileiros mandam). Byte que nem
     * assim vira caractere é descartado em vez de quebrar a leitura.
     */
    public static function toUtf8(?string $raw): string
    {
        $raw = (string) $raw;
        if ($raw === '' || mb_check_encoding($raw, 'UTF-8')) {
            return $raw;
        }

        $converted = @mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        return mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
    }

    /**
     * Texto livre -> MAIÚSCULAS ASCII sem acento, só A-Z/0-9/espaço, com
     * espaços colapsados. Caractere que não vira ASCII (ex: U+FFFD de um
     * byte corrompido) vira "?" pra permitir o reparo em normalizeBodyPart().
     */
    public static function toAsciiUpper(?string $raw): string
    {
        // Mapa próprio em vez de iconv//TRANSLIT: o resultado do iconv muda
        // por plataforma (no Windows "Ó" vira "'O").
        $ascii = strtr(self::toUtf8($raw), [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c', 'Ñ' => 'N', 'ñ' => 'n',
        ]);
        // Cada caractere restante fora do ASCII (inclusive U+FFFD) vira um "?".
        $ascii = preg_replace('/[^\x20-\x7E]/u', '?', $ascii);
        $ascii = strtoupper($ascii);
        $ascii = preg_replace('/[^A-Z0-9 ?]/', '?', $ascii);

        return trim(preg_replace('/ +/', ' ', $ascii));
    }

    /**
     * BodyPartExamined em MAIÚSCULAS ASCII, reparando um único caractere
     * corrompido contra a lista de partes do corpo conhecidas
     * ("T?RAX" -> "TORAX"). Devolve '' quando vazio ou irrecuperável.
     */
    public static function normalizeBodyPart(?string $raw): string
    {
        $value = self::toAsciiUpper($raw);
        if ($value === '') {
            return '';
        }
        if (strpos($value, '?') === false) {
            return $value;
        }

        foreach (self::BODY_PARTS as $known) {
            if (strlen($known) === strlen($value) && fnmatch($value, $known)) {
                return $known;
            }
        }

        return trim(str_replace('?', '', $value));
    }

    /**
     * Fabricantes de raio-X comuns no mercado, pelo texto que costumam gravar
     * em Manufacturer (0008,0070). Casa por trecho, sem diferenciar
     * maiúsculas — "FUJIFILM Corporation", "FUJI PHOTO FILM CO." e "Fuji"
     * viram a mesma família. Só identifica (hoje usado em log, pra descobrir
     * equipamentos novos); a leitura em si segue as tags padrão DICOM.
     */
    private const MANUFACTURER_FAMILIES = [
        'KONICA'     => 'KONICA MINOLTA',
        'FUJI'       => 'FUJIFILM',
        'CARESTREAM' => 'CARESTREAM',
        'KODAK'      => 'CARESTREAM',
        'AGFA'       => 'AGFA',
        'SIEMENS'    => 'SIEMENS',
        'GE '        => 'GE',
        'GENERAL ELECTRIC' => 'GE',
        'PHILIPS'    => 'PHILIPS',
        'SAMSUNG'    => 'SAMSUNG',
        'SHIMADZU'   => 'SHIMADZU',
        'CANON'      => 'CANON',
        'TOSHIBA'    => 'CANON',
        'HOLOGIC'    => 'HOLOGIC',
        'VAREX'      => 'VAREX',
        'VARIAN'     => 'VAREX',
        'DRGEM'      => 'DRGEM',
        'MINDRAY'    => 'MINDRAY',
        'VIEWORKS'   => 'VIEWORKS',
        'IMEX'       => 'IMEX',
        'E-COM'      => 'E-COM',
        'MEDIX'      => 'MEDIX',
        'DEL MEDICAL' => 'DEL MEDICAL',
    ];

    public static function manufacturerFamily(?string $raw): string
    {
        $value = strtoupper(self::toAsciiUpper($raw)) . ' ';
        if (trim($value) === '') {
            return 'DESCONHECIDO';
        }

        foreach (self::MANUFACTURER_FAMILIES as $needle => $family) {
            if (strpos($value, $needle) !== false) {
                return $family;
            }
        }

        return 'DESCONHECIDO';
    }

    /**
     * Raio-x de tórax OIT reconhecido pelo texto das tags de descrição
     * (ProtocolName, StudyDescription, SeriesDescription...): precisa citar
     * "OIT" como palavra e "TORAX"/"THORAX"/"CHEST" (ou "PA"). Independe do
     * fabricante; é o que Braga ("TORAX OIT"), Bem Viver ("Torax OIT PA") e
     * CRJ ("OIT PA") já fazem cada um à sua maneira.
     */
    public static function isOitByText(array $descricoes): bool
    {
        $texto = self::toAsciiUpper(implode(' ', $descricoes));

        return preg_match('/\bOIT\b/', $texto) === 1
            && preg_match('/\b(TORAX|THORAX|CHEST|PEITO|PA)\b/', $texto) === 1;
    }

    /**
     * Só os dígitos de um PatientID (CPF com pontos/hífen, com espaços entre
     * os dígitos, ou sem máscara -> sempre a mesma string).
     */
    public static function onlyDigits(?string $raw): string
    {
        return preg_replace('/[^0-9]/', '', (string) $raw);
    }

    /**
     * Valores que alguns exportadores gravam no lugar de "sem informação".
     */
    public static function isPlaceholder(?string $raw): bool
    {
        $value = strtoupper(trim((string) $raw, " \t\n\r\0^"));

        return in_array($value, ['', '0', 'UNDEFINED', 'NULL', 'NONE', 'N/A', ',,,'], true);
    }
}
