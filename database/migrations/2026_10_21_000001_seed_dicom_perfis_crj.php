<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedDicomPerfisCrj extends Migration
{
    /**
     * Primeiros perfis cadastrados: os dois equipamentos do CRJ, como MODELO
     * pra copiar na hora de criar os perfis dos outros fabricantes.
     *
     * Entram INATIVOS de propósito: a leitura do CRJ continua em código
     * (ExameController::getCRJDICOMInfoCR/DR), que é testada antes dos perfis
     * do banco, então ativar estes não muda nada hoje. Eles documentam, em
     * forma de configuração, o que o código faz — e a aproximação não é
     * exata (o código tem regras próprias, como o sufixo " - " e o fallback
     * por 5 letras da StudyDescription).
     */
    public function up()
    {
        $agora = now();

        $perfis = [
            [
                'nome' => 'CRJ - Konica Minolta DR (modelo)',
                'fabricante_contem' => 'KONICA MINOLTA',
                'modelo_contem' => null,
                'estacao_contem' => null,
                'prioridade' => 100,
                'ativo' => false,
                // Código: BodyPartExamined traduzido (HAND->MAO...) + lateralidade;
                // sem BodyPart, cai na descrição do estudo.
                'tipo_exame_tags' => 'BodyPartExamined,StudyDescription',
                'usa_lateralidade' => true,
                'oit_por_texto' => true,
                'medico_tag' => 'ReferringPhysicianName',
                'empresa_tag' => 'PatientComments',
                'observacao' => 'Modelo do CRJ (Konica Minolta CS-7, DR). Hoje lido por getCRJDICOMInfoDR no código, que vem antes dos perfis do banco; '
                    . 'este perfil serve de referência para criar os de outros fabricantes.',
            ],
            [
                'nome' => 'CRJ - Estação XC_DICOM_CRJ, CR (modelo)',
                'fabricante_contem' => null,
                'modelo_contem' => null,
                'estacao_contem' => 'XC_DICOM_CRJ',
                'prioridade' => 100,
                'ativo' => false,
                // Código: tipo vem de PerformedProcedureStepDescription (0040,0254).
                'tipo_exame_tags' => 'PerformedProcedureStepDescription',
                'usa_lateralidade' => false,
                'oit_por_texto' => true,
                'medico_tag' => 'ReferringPhysicianName',
                'empresa_tag' => null, // o código usa 0038,4000, que ainda não é uma tag selecionável
                'observacao' => 'Modelo do CRJ (CR, estação XC_DICOM_CRJ). Hoje lido por getCRJDICOMInfoCR no código; '
                    . 'a empresa vem da tag 0038,4000, ainda não disponível nos perfis.',
            ],
        ];

        foreach ($perfis as $perfil) {
            if (DB::table('dicom_perfis')->where('nome', $perfil['nome'])->exists()) {
                continue;
            }
            DB::table('dicom_perfis')->insert($perfil + ['created_at' => $agora, 'updated_at' => $agora]);
        }
    }

    public function down()
    {
        DB::table('dicom_perfis')->whereIn('nome', [
            'CRJ - Konica Minolta DR (modelo)',
            'CRJ - Estação XC_DICOM_CRJ, CR (modelo)',
        ])->delete();
    }
}
