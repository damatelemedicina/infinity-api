<?php

namespace App\Utils;

use App\Models\TipoExame;

class Utils {
    public static function getNomeLaudoParaDownload($exame) {
        $documento = $exame->cpf ? $exame->cpf : $exame->rg;
        $documento = str_replace(' ', '', $documento);
        $documento = strlen($documento) == 0 ? 'X' : $documento;
        return "LAUDO_" . $exame->id . "_" . Self::getNomeTipoExame($exame) . "_"
            . str_replace(" ", "_", $exame->paciente) . '_' . $documento . Self::getFileExtension($exame->arquivo_laudo);
    }

    private static function getNomeTipoExame($exame) {
        $prefixoTipoExame = array(
            '*', 'ECG', 'EEG', 'ESPIRO', 'RAIOX', 'MAPA',
            'ACUIDADE', 'EEG_CLINICO', 'MAPEAMENTO', 'RAIOX_OIT',
            'HOLTER', 'ESPIRO_PNEUMO', 'ESPIRO_CLINICA',
            'ISHIHARA', '*', 'AUDIOMETRIA');
        if ($exame->exame_id < count($prefixoTipoExame)) return $prefixoTipoExame[$exame->exame_id];
        $tipoExame = TipoExame::where(['id' => $exame->exame_id])->first();
        $nomeExame = !$tipoExame ? "NAO_DEFINIDO" : strtoupper(trim($tipoExame->nome));
        $nomeExame = str_replace(" ", "_", $nomeExame);
        return $nomeExame;
    }

    private static function getFileExtension($path) {
        $info = pathinfo($path);
        return '.' . $info['extension'];
    }
    public static function getNomeTracadoParaDownload($exame) {
        $nome = Self::getNomeLaudoParaDownload($exame);
        return str_replace('LAUDO_', 'TRACADO_', $nome);
    }

    public static function getMesAno() {
        $meses = [ '*',
            'JANEIRO', 'FEVEREIRO', 'MARCO',
            'ABRIL', 'MAIO', 'JUNHO',
            'JULHO', 'AGOSTO', 'SETEMBRO',
            'OUTUBRO', 'NOVEMBRO', 'DEZEMBRO'
        ];
        return $meses[(int)date('m')] . '_' . date('Y');
    }


    public static function getRG_CPF($doc) {
        $result = array('RG' => '', 'CPF' => '');
        if (Self::isCPF($doc)) $result['CPF'] = $doc;
        else $result['RG'] = $doc;
        return $result;
    }

    public static function isCPF($cpf) {
        $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
        if (strlen($cpf) != 11) {
            return false;
        }
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    public static function removeAccents($str) {
        // Define arrays for accented characters and their replacements
        $accents = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
        $noAccents = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];

        // Replace accented characters with their non-accented equivalents
        $str = str_replace($accents, $noAccents, $str);

        return $str;
    }

    public static function convert_strtoupper($str) {
        return mb_convert_case(self::removeAccents($str), MB_CASE_UPPER, "UTF-8");
    }

}
