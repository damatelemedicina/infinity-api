<?php

namespace App\Http\Controllers;

use App\Exceptions\ClienteNaoEncontradoException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\ExameNaoEncontradoException;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Exame;
use App\Models\TipoExame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExameSOCController extends Controller
{

    private function _getClienteEmpresa(Request $request, $cliente_id = null)
    {
        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $this->validarRequisicao($request);

        $cliente_id = empty($request['body']['cliente_id']) ? $cliente_id : $request['body']['cliente_id'];
        $hasCliente = empty($cliente_id) ? false : Cliente::where('id', $cliente_id)->exists();
        if (!$hasCliente) throw new ClienteNaoEncontradoException();

        $cliente = Cliente::where('id', $cliente_id)->first();

        return ["cliente" => $cliente, "empresa" => $empresa];
    }

    private static function _getEmpresasSOC(Cliente $cliente): array
    {
        $CSV = (array) (\App\Services\SOCWebService\DataExport::exportaDadosWs(
            $cliente->soc_codigo_empresa,
            $cliente->soc_codigo_exporta_cad_empresas,
            $cliente->soc_chave_exporta_cad_empresas,
            [
                'empresa' => $cliente->soc_codigo_empresa,
            ],
            'csv'
        ) ?? []);

        $lines = explode(PHP_EOL, $CSV[0] ?? []);
        $headers = str_getcsv((array_shift($lines)), ";");
        $empresas = [];
        if (empty($lines) == false)
            foreach ($lines as $line) {
                $row = str_getcsv($line, ";");
                if (empty($row[0])) continue;
                $empresa = [];
                foreach ($headers as $kHeader => $header) {
                    if (empty($row[$kHeader])) continue;
                    $empresa[$header] = $row[$kHeader];
                }
                $empresas[] = $empresa;
            }
        return $empresas;
    }

    function getEmpresasSOC(Request $request)
    {
        try {
            ["cliente" => $cliente, "empresa" => $empresa] = $this->_getClienteEmpresa($request);

            $empresas = $this->_getEmpresasSOC($cliente);

            if (empty($empresas))
                return [];

            return array_reduce($empresas, function ($carry, $item) {
                if (empty($item['CODIGO'])) return $carry;
                $carry[] = [
                    'CODIGO' => $item['CODIGO'],
                    'RAZAOSOCIAL' => $item['RAZAOSOCIAL'] ?? null,
                    'CNPJ' => $item['CNPJ'] ?? null,
                    'ATIVO' => $item['ATIVO'] ?? null,
                ];
                return $carry;
            }, []);
        } catch (\Throwable $th) {
            throw new \App\Exceptions\LaudoException($th->getMessage());
        }
    }

    private static function _getFuncionariosSOC(Cliente $cliente, string $empresaTrabalho, string $cpf = null): array
    {
        return (array) (\App\Services\SOCWebService\DataExport::exportaDadosWs(
            $cliente->soc_codigo_empresa,
            $cliente->soc_codigo_exporta_cad_funcionarios,
            $cliente->soc_chave_exporta_cad_funcionarios,
            [
                'empresaTrabalho' => $empresaTrabalho,
                'parametroData' => 0,
                'cpf' => $cpf,
            ]
        ) ?? []);
    }

    function getFuncionariosSOC(Request $request)
    {
        try {
            if (empty($request['body']['soc_codigo_empresa_trabalho'])) throw new \Exception('Código da empresa de trabalho não informado');

            ["cliente" => $cliente, "empresa" => $empresa] = $this->_getClienteEmpresa($request);

            $funcionariosSoc = $this->_getFuncionariosSOC($cliente, $request['body']['soc_codigo_empresa_trabalho'], empty($request['body']['cpf']) ? null : preg_replace('/[^0-9]/', '', $request['body']['cpf']));

            if (empty($funcionariosSoc))
                return [];

            return array_reduce($funcionariosSoc, function ($carry, $item) {
                $carry[] = [
                    $item['CODIGO'],
                    $item['NOME'],
                    $item['SITUACAO'],
                    $item['DATACADASTRO'],
                    $item['CODIGO'],
                ];
                return $carry;
            });
        } catch (\Throwable $th) {
            throw new \App\Exceptions\LaudoException($th->getMessage());
        }
    }

    private static function _getPedidosExamesFuncionarioSOC(Cliente $cliente, string $codigo_empresa, string $codigo_funcionario, \DateTime $date01): array
    {
        return (array) (\App\Services\SOCWebService\DataExport::exportaDadosWs(
            $codigo_empresa,
            $cliente->soc_codigo_exporta_pedido_exame,
            $cliente->soc_chave_exporta_pedido_exame,
            [
                'funcionarioInicio' => $codigo_funcionario,
                'funcionarioFim' => $codigo_funcionario,
                'paramData' => 1,
                'dataInicio' => $date01->format('01/m/Y'),
                'dataFim' => $date01->format('t/m/Y'),
            ]
        ) ?? []);
    }

    function getPedidosExamesFuncionarioSOC(Request $request)
    {
        try {
            ["cliente" => $cliente, "empresa" => $empresa] = $this->_getClienteEmpresa($request);

            $codigo_empresa = empty($request['body']['codigo_empresa']) ? null : $request['body']['codigo_empresa'];
            if (empty($codigo_empresa)) throw new \App\Exceptions\LaudoException('Código da empresa não informado');

            $codigo_funcionario = empty($request['body']['codigo_funcionario']) ? null : $request['body']['codigo_funcionario'];
            if (empty($codigo_funcionario)) throw new \App\Exceptions\LaudoException('Código do funcionário não informado');

            $mes = intval(empty($request['body']['mes']) ? date('n') : $request['body']['mes']);
            $ano = empty($request['body']['ano']) ? date('Y') : $request['body']['ano'];

            $date01 = date_create("{$ano}-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01");

            $pedidosExameFuncionarioSOC = $this->_getPedidosExamesFuncionarioSOC($cliente, $codigo_empresa, $codigo_funcionario, $date01);

            if (empty($pedidosExameFuncionarioSOC))
                return [];

            // abort(500, var_export($pedidosExameFuncionarioSOC));

            $tipo_exame_id = empty($request['body']['tipo_exame_id']) ? null : $request['body']['tipo_exame_id'];
            $tipoExame = TipoExame::where('id', $tipo_exame_id)->first();
            if ($tipoExame->soc_codigo_exame) {
                $soc_codigo_exame = array_unique(explode(';', $tipoExame->soc_codigo_exame));
                $pedidosExameFuncionarioSOC = array_filter($pedidosExameFuncionarioSOC, fn($item) => in_array($item['CODIGOINTERNOEXAME'], $soc_codigo_exame));
            }

            return array_reduce($pedidosExameFuncionarioSOC, function ($carry, $item) {
                $carry[] = [
                    $item['SEQUENCIAFICHA'],
                    $item['NOMEEXAME'],
                    $item['NOMEFUNCIONARIO'],
                    $item['DATAEXAME'],
                    $item['DATAFICHA'],
                    $item['CODIGOINTERNOEXAME'],
                    $item['CODIGOFUNCIONARIO'],
                    $item['CPFFUNCIONARIO'],
                ];
                return $carry;
            });
        } catch (\Throwable $th) {
            throw new \App\Exceptions\LaudoException($th->getMessage());
        }
    }

    private static function _getSeqResultadoPedidoExame(Cliente $cliente, string $empresaTrabalho, string $seqficha, string $codexame = null): array
    {
        $peds = (array) (\App\Services\SOCWebService\DataExport::exportaDadosWs(
            $cliente->soc_codigo_empresa,
            $cliente->soc_codigo_exporta_pedido_exame_seq_ficha,
            $cliente->soc_chave_exporta_pedido_exame_seq_ficha,
            [
                'empresaTrabalho' => $empresaTrabalho,
                'sequencial' => $seqficha,
            ]
        ));

        if (empty($codexame) == false && empty($peds) == false)
            return array_values(array_filter($peds, fn($item) => $item['CODIGOEXAME'] == $codexame)) ?? [];

        return $peds;
    }

    function vincularSOCSeqResultado(Request $request)
    {
        $exame_id = empty($request['body']['exame_id']) ? null : $request['body']['exame_id'];
        if (empty($exame_id)) throw new \App\Exceptions\LaudoException('Exame não informado');

        $hasExame = Exame::where('id', $exame_id)->exists();
        if (!$hasExame) throw new ExameNaoEncontradoException();

        $exame = Exame::where('id', $exame_id)->first();

        $empresa_trabalho = empty($request['body']['empresa_trabalho']) ? null : $request['body']['empresa_trabalho'];
        if (empty($empresa_trabalho)) throw new \App\Exceptions\LaudoException('Empresa SOC de trabalho não informada');

        $empresa_trabalho = json_decode($empresa_trabalho ?? '[]', JSON_OBJECT_AS_ARRAY);
        if (empty($empresa_trabalho['CODIGO'])) throw new \App\Exceptions\LaudoException('Empresa SOC de trabalho não informada');

        if (empty($exame->soc_resultado_enviado) == false)
            throw new \App\Exceptions\LaudoException('Laudo ja enviado para SOC. Não é mais necessário alterar o vínculo.');

        if (empty($exame->laudo_date) == false)
            throw new \App\Exceptions\LaudoException('Laudo já lançado. Não é mais necessário vincular. Faça a alteração do resultado diretamente no SOC');

        ["cliente" => $cliente, "empresa" => $empresa] = $this->_getClienteEmpresa($request);

        $codexame = empty($request['body']['codexame']) ? null : $request['body']['codexame'];
        if (empty($codexame)) throw new \App\Exceptions\LaudoException('Código do exame não informado');

        $seqficha = empty($request['body']['seqficha']) ? null : $request['body']['seqficha'];
        if (empty($seqficha)) throw new \App\Exceptions\LaudoException('Sequêncial da ficha não informado');

        $tipo_exame_id = $exame->exame_id;
        if (empty($tipo_exame_id)) throw new \App\Exceptions\LaudoException('Tipo de Exame não informado');

        $tipoExame = TipoExame::where('id', $tipo_exame_id)->first();
        if (empty($tipoExame->soc_codigo_exame))
            throw new \App\Exceptions\LaudoException('Tipo de Exame do exame não tem vinculo com SOC cadastrado');

        $soc_codigo_exame = array_unique(explode(';', $tipoExame->soc_codigo_exame));
        if (!in_array($codexame, $soc_codigo_exame))
            throw new \App\Exceptions\LaudoException('Tipo de Exame do exame informado diferente do vinculado no SOC');

        try {
            $filtered = $this->_getSeqResultadoPedidoExame($cliente, $empresa_trabalho['CODIGO'], $seqficha, $codexame);

            if (empty($filtered[0]))
                throw new \App\Exceptions\LaudoException('Exame não encontrado');

            if (count($filtered) > 1)
                throw new \App\Exceptions\LaudoException('Mais de um exame encontrado. Contate o suporte');

            /** @var \Illuminate\Database\Query\Builder */
            $exameQuery = Exame::query();
            $isPedidoExameVinculado = intval($exameQuery
                ->select(DB::raw("count(id) as cnt"))
                ->where(DB::raw("soc_seq_ficha"), $request['body']['soc_seq_ficha'])
                ->where(DB::raw("soc_seq_resultado"), $filtered[0]['SEQUENCIALRESULTADO'])
                ->whereIn(DB::raw("soc_codigo_exame"), $soc_codigo_exame)
                ->where("id", "!=", $exame_id)
                ->where("ativo", 1)
                ->whereIn("status", [Exame::$AGUARDADO_LAUDO, Exame::$LAUDADO])
                ->value('cnt')) > 0;

            if ($isPedidoExameVinculado)
                throw new \App\Exceptions\LaudoException('Exame SOC já vinculado a um exame');

            $exame->soc_codigo_empresa_trabalho = $empresa_trabalho['CODIGO'];
            $exame->soc_nome_empresa_trabalho = $empresa_trabalho['RAZAOSOCIAL'];
            $exame->soc_cnpj_empresa_trabalho = $empresa_trabalho['CNPJ'];
            $exame->soc_codigo_funcionario = $request['body']['soc_codigo_funcionario'];
            $exame->soc_nome_funcionario = $request['body']['soc_nome_funcionario'];
            $exame->soc_situacao_funcionario = $request['body']['soc_situacao_funcionario'];
            $exame->soc_data_cadastro_funcionario = $request['body']['soc_data_cadastro_funcionario'];
            $exame->soc_cpf_funcionario = $request['body']['soc_cpf_funcionario'];
            $exame->soc_seq_ficha = $request['body']['soc_seq_ficha'];
            $exame->soc_nome_exame = $request['body']['soc_nome_exame'];
            $exame->soc_data_exame = $request['body']['soc_data_exame'];
            $exame->soc_data_ficha = $request['body']['soc_data_ficha'];
            $exame->soc_codigo_exame = $codexame;
            $exame->soc_seq_resultado = $filtered[0]['SEQUENCIALRESULTADO'];

            $exame->save();

            $exame->soc_codigo_cliente = $cliente->soc_codigo_empresa;
            $exame->soc_codigo_empresa_principal = $empresa->soc_codigo_empresa_principal;

            return $exame;
        } catch (\Throwable $th) {
            throw new \App\Exceptions\LaudoException($th->getMessage());
        }
    }

    function desvincularExameSOC(Request $request)
    {
        $id = empty($request['body']['id']) ? null : $request['body']['id'];
        if (empty($id)) throw new \App\Exceptions\LaudoException('Exame não informado');

        $hasExame = Exame::where('id', $id)->exists();
        if (!$hasExame) throw new ExameNaoEncontradoException();

        $exame = Exame::where('id', $id)->first();

        if (empty($exame->soc_resultado_enviado) == false)
            throw new \App\Exceptions\LaudoException('Laudo ja enviado para SOC. Não é mais necessário desvincular.');

        if (empty($exame->laudo_date) == false)
            throw new \App\Exceptions\LaudoException('Laudo já lançado. Não é mais necessário desvincular.');

        ["cliente" => $cliente, "empresa" => $empresa] = $this->_getClienteEmpresa($request, $exame->cliente_id);

        try {
            $exame->soc_codigo_funcionario = null;
            $exame->soc_nome_funcionario = null;
            $exame->soc_situacao_funcionario = null;
            $exame->soc_data_cadastro_funcionario = null;
            $exame->soc_cpf_funcionario = null;
            $exame->soc_seq_ficha = null;
            $exame->soc_nome_exame = null;
            $exame->soc_data_exame = null;
            $exame->soc_data_ficha = null;
            $exame->soc_codigo_exame = null;
            $exame->soc_seq_resultado = null;

            $exame->save();

            $exame->soc_codigo_cliente = $cliente->soc_codigo_empresa;
            $exame->soc_codigo_empresa_principal = $empresa->soc_codigo_empresa_principal;

            return $exame;
        } catch (\Throwable $th) {
            throw new \App\Exceptions\LaudoException($th->getMessage());
        }
    }

    private static function isExecSOC(Empresa $empresa, Cliente $cliente)
    {
        return (empty($cliente->soc_codigo_empresa) == false && empty($empresa->soc_codigo_empresa_principal) == false);
    }


    public static function vinculaExamesSOC(Empresa $empresa, Cliente $cliente, Exame &$exame, array $soc_empresa_trabalho = [])
    {
        try {
            $tipoExame = TipoExame::where('id', $exame->exame_id)->first();

            if (
                empty($tipoExame->soc_codigo_exame)
                || empty($exame->cpf)
                || Self::isExecSOC($empresa, $cliente) != true
            ) return;

            $empresasSoc = empty($soc_empresa_trabalho) ? Self::_getEmpresasSOC($cliente) : [$soc_empresa_trabalho];

            if (empty($empresasSoc)) return;

            foreach ($empresasSoc as $empresaSOC) {
                $funcionariosSoc = Self::_getFuncionariosSOC($cliente, $empresaSOC['CODIGO'], $exame->cpf);

                if (empty($funcionariosSoc)) continue;

                $funcionarioSoc = $funcionariosSoc[0];

                $pedidosExamefuncionarioSOC = Self::_getPedidosExamesFuncionarioSOC($cliente, $empresaSOC['CODIGO'], $funcionarioSoc['CODIGO'], date_create($exame->exame_date));

                if (empty($pedidosExamefuncionarioSOC)) return;

                // ordernar do mais recente para o mais antigo pelo campo DATACRIACAOPEDIDOEXAMES
                usort($pedidosExamefuncionarioSOC, function ($a, $b) {
                    return date_create_from_format('d/m/Y', $b['DATACRIACAOPEDIDOEXAMES']) <=> date_create_from_format('d/m/Y', $a['DATACRIACAOPEDIDOEXAMES']);
                });

                $exameSelecionado = null;
                foreach ($pedidosExamefuncionarioSOC as $value) {

                    $soc_codigo_exame = array_unique(explode(';', $tipoExame->soc_codigo_exame));

                    if (in_array($value['CODIGOINTERNOEXAME'], $soc_codigo_exame) == false) continue;

                    /** @var \Illuminate\Database\Query\Builder */
                    $exameQuery = Exame::query();
                    $isPedidoExameVinculado = intval($exameQuery
                        ->select(DB::raw("count(id) as cnt"))
                        ->where(DB::raw("soc_seq_ficha"), $value['SEQUENCIAFICHA'])
                        ->whereIn(DB::raw("soc_codigo_exame"), $soc_codigo_exame)
                        ->where("ativo", 1)
                        ->whereIn("status", [Exame::$AGUARDADO_LAUDO, Exame::$LAUDADO])
                        ->value('cnt')) > 0;
                    if (!$isPedidoExameVinculado) {
                        $exameSelecionado = $value;
                        break;
                    }
                }

                if (empty($exameSelecionado)) return;

                $pedidoSeqFicha = Self::_getSeqResultadoPedidoExame($cliente, $empresaSOC['CODIGO'], $exameSelecionado['SEQUENCIAFICHA'], $exameSelecionado['CODIGOINTERNOEXAME']);

                if (empty($pedidoSeqFicha[0])) return;

                $pedidoSeqFicha = $pedidoSeqFicha[0];

                $exame->soc_codigo_empresa_trabalho = $empresaSOC['CODIGO'];
                $exame->soc_nome_empresa_trabalho = $empresaSOC['RAZAOSOCIAL'];
                $exame->soc_cnpj_empresa_trabalho = $empresaSOC['CNPJ'];
                $exame->soc_codigo_funcionario = $pedidoSeqFicha['CODIGOFUNCIONARIO'];
                $exame->soc_nome_funcionario = $pedidoSeqFicha['NOMEFUNCIONARIO'];
                $exame->soc_situacao_funcionario = $funcionarioSoc['SITUACAO'];
                $exame->soc_data_cadastro_funcionario = $funcionarioSoc['DATACADASTRO'];
                $exame->soc_cpf_funcionario = $funcionarioSoc['CPFFUNCIONARIO'];
                $exame->soc_seq_ficha = $exameSelecionado['SEQUENCIAFICHA'];
                $exame->soc_nome_exame = $exameSelecionado['NOMEEXAME'];
                $exame->soc_data_exame = $exameSelecionado['DATAEXAME'];
                $exame->soc_data_ficha = $exameSelecionado['DATAFICHA'];
                $exame->soc_codigo_exame = $pedidoSeqFicha['CODIGOEXAME'];
                $exame->soc_seq_resultado = $pedidoSeqFicha['SEQUENCIALRESULTADO'];

                break;
            }

            return;
        } catch (\Throwable $th) {
            return;
        }
    }


    public static function fnUploadLaudoNoSOCGED(Empresa $empresa, Cliente $cliente, Exame $exame, array $extraParams = [])
    {
        if (empty($exame->soc_codigo_empresa_trabalho)) return;

        if (Self::isExecSOC($empresa, $cliente) != true) return;

        $soc_nome_arquivo = null;

        $client = new \App\Services\SOCWebService\FileUploadGED($empresa->soc_usuario_webservice, $empresa->soc_password_webservice);

        $self = new self;

        $path_file = $self->storePath($exame->arquivo_laudo);
        if (!file_exists($path_file)) {
            throw new \App\Exceptions\LaudoException("Arquivo do laudo não encontrado!");
        }

        $fileDetails = pathinfo($path_file);
        $tipoExame = TipoExame::where('id', $exame->exame_id)->first();

        $soc_nome_arquivo = (\Illuminate\Support\Str::of("{$tipoExame->nome}_{$exame->paciente}")->slug()->replace('-', '_')->upper()->__toString()) . "_{$exame->id}";

        $client->fileUpload(
            array_merge([
                "arquivo" => base64_encode(file_get_contents($path_file)),
                "codigoEmpresa" => $exame->soc_codigo_empresa_trabalho,
                "codigoSequencialFicha" => $exame->soc_seq_ficha,
                "extensaoArquivo" => \Illuminate\Support\Str::of($fileDetails['extension'])->upper(),
                "identificacaoVo" => [
                    "codigoUsuario" => $empresa->soc_codigo_usuario,
                    "chaveAcesso" => $empresa->soc_chave_acesso,
                    "codigoEmpresaPrincipal" => $empresa->soc_codigo_empresa_principal,
                    "codigoResponsavel" => $empresa->soc_codigo_responsavel,
                ],
                "nomeArquivo" => $soc_nome_arquivo,
            ], $extraParams)
        );

        return $soc_nome_arquivo;
    }

    public static function fnUpdateLaudoNoSOC(Empresa $empresa, Cliente $cliente, Exame $exame)
    {
        if (Self::isExecSOC($empresa, $cliente) != true) return;

        $exame_id = $exame->exame_id;
        $tipoExame = TipoExame::where('id', $exame_id)->first();

        $client = new \App\Services\SOCWebService\ExamResult($empresa->soc_usuario_webservice, $empresa->soc_password_webservice);

        $client->examResult([
            "examesIdentificacaoPorIdWsVo" => [
                "codigoIdFicha" => $exame->soc_seq_ficha,
                "codigoIdResultadoExame" => $exame->soc_seq_resultado,
            ],
            "identificacaoWsVo" => [
                "codigoUsuario" => $empresa->soc_codigo_usuario,
                "chaveAcesso" => $empresa->soc_chave_acesso,
                "codigoEmpresaPrincipal" => $empresa->soc_codigo_empresa_principal,
                "codigoResponsavel" => $empresa->soc_codigo_responsavel,
            ],
            "resultadoExamesDadosWsVo" => [
                "codigoExame" => $exame->soc_codigo_exame,
                "sobrepoeResultadoExistente" => true,
                "resultado" => $tipoExame->soc_nao_envia_texto_laudo === 1 ? null : $exame->modelo_content,
                "dataResultadoExame" => empty($exame->laudo_date) ? null : date_create($exame->laudo_date)->format('d/m/Y'),
                "resultadoAlterado" => $exame->soc_resultado_alterado === 1
            ]
        ]);

        return 1;
    }

    private function dealSOCExame(Request $request, $tryCallback)
    {
        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $this->validarRequisicao($request);

        $id = $request['body']['id'];
        $hasExame = empty($id) ? false : Exame::where('id', $id)->exists();
        if (!$hasExame) throw new ExameNaoEncontradoException();

        try {
            $exame = Exame::where('id', $id)->first();
            $cliente = Cliente::where('id', $exame->cliente_id)->first();

            $tryCallback($exame, $empresa, $cliente);

            $exame->save();
        } catch (\Throwable $th) {
            throw new \App\Exceptions\LaudoException($th->getMessage());
        }

        /* Apenas para alterar estado do botão SOC */
        $exame->soc_codigo_cliente = $cliente->soc_codigo_empresa;
        $exame->soc_codigo_empresa_principal = $empresa->soc_codigo_empresa_principal;

        return $exame;
    }

    function enviarExameSOCGED(Request $request)
    {
        return $this->dealSOCExame($request, function (Exame &$exame, Empresa $empresa, Cliente $cliente) {
            if ($exame->soc_nome_arquivo)
                throw new \Exception("Laudo ja enviado para SOCGED");
            $exame->soc_nome_arquivo = self::fnUploadLaudoNoSOCGED($empresa, $cliente, $exame);
        });
    }

    function enviarDadosLaudoSOC(Request $request)
    {
        return $this->dealSOCExame($request, function (Exame &$exame, Empresa $empresa, Cliente $cliente) {
            if ($exame->soc_resultado_enviado === 1)
                throw new \Exception("Dados do laudo já enviado para o SOC");
            $exame->soc_resultado_enviado = self::fnUpdateLaudoNoSOC($empresa, $cliente, $exame);
        });
    }
}
