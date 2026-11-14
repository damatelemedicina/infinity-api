<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class GetLaudosDoMedicoAddRgCpf extends Migration
{
    public function up()
    {
        $this->recriar(true);
    }

    public function down()
    {
        $this->recriar(false);
    }

    private function recriar($comDocumento)
    {
        $documento = $comDocumento ? "ex.rg AS rg,\n        ex.cpf AS cpf," : '';

        $preco = "if (ex.abonado_medico = 1 OR ex.ativo = 0,
             0,
            IFNULL((SELECT pm.preco FROM preco_medicos pm WHERE
                pm.empresa_id = me.empresa_id AND
                pm.medico_id = ex.medico_id AND
                pm.tipo_exame_id = ex.exame_id AND
                ex.created_at >= pm.vigencia_de AND ex.created_at <= pm.vigencia_ate
                limit 1
            ), 0)
        )";

        DB::unprepared('DROP PROCEDURE IF EXISTS `GetLaudosDoMedico`');
        DB::unprepared("
CREATE PROCEDURE `GetLaudosDoMedico`(MEDICO_ID integer, DATA_INICIAL datetime, DATA_FINAL datetime)
BEGIN
    SELECT
        ex.id AS exame_id,
        ex.paciente AS paciente,
        {$documento}
        ex.created_at AS exame_data,
        ex.laudo_date AS laudo_data,
        em.nome as empresa,
        cl.nome AS cliente_nome,
        te.nome AS tipo_exame,
        me.nome AS medico_nome,
        {$preco} AS preco_laudo,
        concat('R$ ', format({$preco}, 2, 'pt_BR')) AS preco_laudo_formatado
    FROM exames ex
    INNER JOIN empresas em
        ON em.id = ex.empresa_id
    INNER JOIN clientes cl
        ON cl.id = ex.cliente_id
    INNER JOIN medicos me
        ON me.id = ex.medico_id
    INNER JOIN tipo_exames te
        ON te.id = ex.exame_id
    WHERE
        ex.status = 1 AND
        ex.medico_id = MEDICO_ID AND
        ex.created_at >= DATA_INICIAL AND
        ex.created_at <= DATA_FINAL;
END");
    }
}
