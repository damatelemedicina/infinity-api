<?php

namespace App\Utils;

class Utils {
    public static function getNomeLaudoParaDownload($exame) {
        $tipoExame = array('*', 'ECG', 'EEG', 'ESPIRO', 'RAIOX', 'MAPA', 'ACUIDADE', 'EEG_CLINICO', 'MAPEAMENTO', 'RAIOX_OIT', 'HOLTER', 'ESPIRO_PNEUMO', 'ESPIRO_CLINICA', 'ISHIHARA');
        $documento = $exame->cpf ? $exame->cpf : $exame->rg;
        $documento = str_replace(' ', '', $documento);
        $documento = strlen($documento) == 0 ? 'X' : $documento;
        return "LAUDO_" . $exame->id . "_" . $tipoExame[$exame->exame_id] . "_"
            . str_replace(" ", "_", $exame->paciente) . '_' . $documento . Self::getFileExtension($exame->arquivo_laudo);
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
}
