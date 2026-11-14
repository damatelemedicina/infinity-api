<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class GetLaudosDoClienteAddRgCpf extends Migration
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
        $selectExterno = $comDocumento ? "rg,\n        cpf," : '';
        $selectInterno = $comDocumento ? "ex.rg as rg,\n            ex.cpf as cpf," : '';

        $subPreco = function ($coluna, $padrao) {
            $sub = "(select pc.{$coluna} from preco_clientes pc where
                pc.empresa_id = ex.empresa_id and
                pc.cliente_id = ex.cliente_id and
                pc.tipo_exame_id = ex.exame_id and
                ex.created_at >= pc.vigencia_de and ex.created_at <= pc.vigencia_ate
                limit 1)";
            return $padrao ? "ifnull({$sub}, 0)" : $sub;
        };

        DB::unprepared('DROP PROCEDURE IF EXISTS `GetLaudosDoCliente`');
        DB::unprepared("
CREATE PROCEDURE `GetLaudosDoCliente`(EMPRESA_ID integer, CLIENTE_ID integer, DATA_INICIAL datetime, DATA_FINAL datetime)
BEGIN
    SELECT
        exame_id,
        exame_data,
        laudo_data,
        upper(paciente) as paciente,
        {$selectExterno}
        upper(tipo_exame) as tipo_exame,
        upper(empresa) as empresa,
        upper(cliente) as cliente,
        upper(medico) as medico,
        upper(contratante) as contratante,
        if (abonado = 1 OR ativo = 0, 0,
            if(
                num <= limite1,
                if (cobranca1 = 'PACOTE', preco1 / limite1, preco1),
                preco2
            )
        ) as preco,
        concat('R$ ', format(if(
            abonado = 1 OR ativo = 0,
            0,
            if(
                num <= limite1,
                if (cobranca1 = 'PACOTE', preco1 / limite1, preco1),
                preco2
            )
        ), 2, 'pt_BR')) as preco_formatado
    FROM (
        SELECT
            (@cnt := @cnt + 1) AS num,
            ex.id as exame_id,
            te.nome as tipo_exame,
            em.nome as empresa,
            cl.nome as cliente,
            ex.paciente as paciente,
            {$selectInterno}
            ex.created_at as exame_data,
            ex.laudo_date as laudo_data,
            ex.abonado as abonado,
            ex.ativo as ativo,
            ex.contratante as contratante,
            (select me.nome from medicos me where me.id = ex.medico_id) as medico,
            {$subPreco('cobranca1', false)} as cobranca1,
            {$subPreco('ate1', true)} as limite1,
            {$subPreco('preco1', true)} as preco1,
            {$subPreco('cobranca2', false)} as cobranca2,
            {$subPreco('ate2', true)} as limite2,
            {$subPreco('preco2', true)} as preco2
        FROM exames AS ex
        CROSS JOIN (SELECT @cnt := 0) AS dummy
        INNER JOIN empresas em ON em.id = ex.empresa_id
        INNER JOIN clientes cl ON cl.id = ex.cliente_id
        INNER JOIN tipo_exames te ON te.id = ex.exame_id
        WHERE
            ex.status = 1 AND
            IF(CLIENTE_ID > 0, ex.cliente_id = CLIENTE_ID, true) AND
            IF(EMPRESA_ID > 0, ex.empresa_id = EMPRESA_ID, true) AND
            ex.created_at >= DATA_INICIAL AND
            ex.created_at <= DATA_FINAL
    ) as result;
END");
    }
}
