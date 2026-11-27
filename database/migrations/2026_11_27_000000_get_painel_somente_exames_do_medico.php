<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class GetPainelSomenteExamesDoMedico extends Migration
{
    public function up()
    {
        $this->recriar(true);
    }

    public function down()
    {
        $this->recriar(false);
    }

    private function recriar($somenteDoMedico)
    {
        $tiposDoMedico = "IF (MEDICO_ID > 0, exames.exame_id IN (
            SELECT medicos_exames.tipo_exame_id FROM medicos_exames WHERE medicos_exames.medico_id = MEDICO_ID
        ), TRUE)";

        $filtroAntigo = "AND {$tiposDoMedico}
        AND IF (MEDICO_ID > 0, exames.medico_id = MEDICO_ID OR exames.medico_id = 0, TRUE)";

        $filtroDoMedico = "AND IF (MEDICO_ID > 0, exames.medico_id = MEDICO_ID, TRUE)";

        $filtroEcg = $somenteDoMedico ? $filtroDoMedico : "AND {$tiposDoMedico}";
        $filtroAguardando = $somenteDoMedico ? $filtroDoMedico : $filtroAntigo;

        // Emergências são exibidas para todos os médicos.
        $filtroEmergencia = $somenteDoMedico ? '' : $filtroAntigo;

        DB::unprepared('DROP PROCEDURE IF EXISTS `GetPainel`');
        DB::unprepared("
CREATE PROCEDURE `GetPainel`(CLIENTE_ID integer, MEDICO_ID integer, MATRIZ_ID integer)
BEGIN
    SELECT * FROM (
        SELECT
            tipo_exames.nome as exame,
            medicos.nome as medico,
            COUNT(exames.id) as total
        FROM exames
        INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
        INNER JOIN medicos on medicos.id = exames.medico_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
        WHERE exames.status = 1 AND exames.ativo = 1
        AND Day(laudo_date) = Day(Now())
        AND Month(laudo_date) = Month(Now())
        AND Year(laudo_date) = Year(Now())
        AND IF (CLIENTE_ID > 0, FALSE, TRUE)
        AND IF (MEDICO_ID > 0, exames.medico_id = MEDICO_ID, TRUE)
        AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
        GROUP BY exames.medico_id, exames.exame_id
        ORDER BY medico ASC, total DESC
    ) AS laudados;

    SELECT * FROM (
        SELECT IFNULL(SUM(exames.arquivo_imagem IS NULL), 0) AS sem_imagem,
               IFNULL(SUM(exames.arquivo_imagem IS NOT NULL), 0) AS com_imagem
        FROM exames
        INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
        WHERE exames.status = 0 AND exames.ativo = 1 AND exames.exame_id = 1
        AND IF (CLIENTE_ID > 0, FALSE, TRUE)
        AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
        {$filtroEcg}
    ) AS ECG;

    SELECT * FROM (
        SELECT
            tipo_exames.id as id,
            tipo_exames.nome as exame,
            COUNT(exames.id) as total
        FROM exames
        INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
        WHERE exames.status = 0 AND exames.ativo = 1
        AND IF (CLIENTE_ID > 0, exames.cliente_id = CLIENTE_ID, TRUE)
        {$filtroAguardando}
        AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
        GROUP BY exames.exame_id
        ORDER BY total DESC
    ) AS aguardandoLaudo
    WHERE total > 0;

    SELECT * FROM (
        SELECT
            count(exames.id) as emergencias
        FROM exames
        INNER JOIN tipo_exames on tipo_exames.id = exames.exame_id
        INNER JOIN empresas on exames.empresa_id = empresas.id
        WHERE exames.status = 0 AND exames.ativo = 1 AND exames.emergencia = 2
        AND IF (CLIENTE_ID > 0, exames.cliente_id = CLIENTE_ID, TRUE)
        {$filtroEmergencia}
        AND IF (MATRIZ_ID > 0, empresas.matriz = MATRIZ_ID, TRUE)
    ) AS emergencias;
END");
    }
}
