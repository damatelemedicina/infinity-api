<?php

namespace App\Services\SOCWebService;

class DataExport extends SOCWebService
{
    protected const WSDL_URI = 'https://ws1.soc.com.br/WSSoc/services/ExportaDadosWs?wsdl';

    /* 
        * Exemplo de como usar - Pedido de Exame pelo Sequencial da ficha(WS Resultado de exames)
        $empresa = '1831692';
        $codigoExportaDados = '202024';
        $chave = 'a4c80bb4fa8d74830652';
        $parametrosExtra = ['sequencial' => '293883598', 'empresaTrabalho' => '1831692'];
        $response = \App\Services\SOCWebService\DataExport::exportaDadosWs($empresa, $codigoExportaDados, $chave, $parametrosExtra);
        dd($response, json_decode($response->return->retorno));
        
        * Exemplo de como usar - Tabela de Exames (Ativos / Inativos)
        $empresa = '1831692';
        $codigoExportaDados = '202070';
        $chave = 'edf7aeb45fd1b568fcfb';
        $parametrosExtra = ['ativo'=>'1', 'inativo'=>'1'];
        $response = \App\Services\SOCWebService\DataExport::exportaDadosWs($empresa, $codigoExportaDados, $chave, $parametrosExtra);
        dd($response, json_decode($response->return->retorno));
        
        * Exemplo de como usar - Pedido de exame
        $empresa = '1831692';
        $codigoExportaDados = '202275';
        $chave = 'b60db106cbc702ffe819';
        $parametrosExtra = [
            'funcionarioInicio' => 1,
            'funcionarioFim' => 1,
            'paramData' => 0,
            'dataInicio' => '01/02/2025',
            'dataFim' => '28/02/2025',
        ];
        $response = \App\Services\SOCWebService\DataExport::exportaDadosWs($empresa, $codigoExportaDados, $chave, $parametrosExtra);
        dd($response);
    
    */

    public static function exportaDadosWs($empresa, $codigoExportaDados, $chave, $parametrosExtra = [], $tipoSaida = 'json')
    {
        $dataExport = (new self)->defaultRequest('exportaDadosWs', 'arg0', [
            "parametros" => json_encode(array_merge([
                'empresa' => $empresa,
                'codigo' => $codigoExportaDados,
                'chave' => $chave,
                'tipoSaida' => $tipoSaida,
            ], $parametrosExtra)),
            "erro" => true
        ]);

        if (empty($dataExport->return))
            throw new \Exception('Erro ao exportar dados', 1);

        if (empty($dataExport->return->mensagemErro) == false)
            throw new \Exception($dataExport->return->mensagemErro);
        
        return $tipoSaida == 'json' ? json_decode($dataExport->return->retorno, true): $dataExport->return->retorno;
    }
}
