<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\ClienteNaoEncontradoException;
use App\Exceptions\AutenticacaoRequeridaException;
use App\Exceptions\CadastroException;

use App\Services\Entity\EntityManager;
use App\Services\Security\PermissionManager;

use Illuminate\Support\Facades\Log;

use App\Models\Cliente;
use App\Models\ClienteServico;
use App\Models\ClienteSolicitante;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Usuario;
use App\Models\Exame;
use App\Models\JsonModel\ClienteJson;
use App\Utils\OrthancInstitutionName;
use App\ViewModels\ClienteViewModel;

class ClienteController extends Controller
{

    protected $entity;
    protected $permissions;

    public function __construct(EntityManager $entity, PermissionManager $permissions)
    {
        $this->entity = $entity;
        $this->permissions = $permissions;
    }

    public function getCliente(Request $request)
    {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $cliente = $this->entity->findOne(
            'clientes',
            ['where' => ['clientes.id = ', $request['body']['id']]],
            // Self::$ENTITY_WITH_CHILDS
        );

        if (!$cliente) throw new ClienteNaoEncontradoException();

        $clienteSOC = Cliente::where('id', $request['body']['id'])->first();

        $json = new ClienteJson(null, $cliente->ClienteJson);

        $cliente->mensagem_urgencia_emergencia = $json->mensagem_urgencia_emergencia;
        $cliente->acessowebservicesoc = empty($clienteSOC->soc_codigo_empresa) == false ? 1 : 0;
        $cliente->soc_codigo_empresa = $clienteSOC->soc_codigo_empresa;
        $cliente->soc_codigo_exporta_cad_empresas = $clienteSOC->soc_codigo_exporta_cad_empresas;
        $cliente->soc_chave_exporta_cad_empresas = $clienteSOC->soc_chave_exporta_cad_empresas;
        $cliente->soc_codigo_exporta_cad_funcionarios = $clienteSOC->soc_codigo_exporta_cad_funcionarios;
        $cliente->soc_chave_exporta_cad_funcionarios = $clienteSOC->soc_chave_exporta_cad_funcionarios;
        $cliente->soc_codigo_exporta_pedido_exame = $clienteSOC->soc_codigo_exporta_pedido_exame;
        $cliente->soc_chave_exporta_pedido_exame = $clienteSOC->soc_chave_exporta_pedido_exame;
        $cliente->soc_codigo_exporta_pedido_exame_seq_ficha = $clienteSOC->soc_codigo_exporta_pedido_exame_seq_ficha;
        $cliente->soc_chave_exporta_pedido_exame_seq_ficha = $clienteSOC->soc_chave_exporta_pedido_exame_seq_ficha;

        $cliente->ClienteClientesServicos = $this->entity->findAll(
            'clientes_servicos',
            ['where' => ['cliente_id = ', $request['body']['id']]],
            // Self::$ENTITY_WITH_CHILDS
        );

        $_servicos = [];

        if ($cliente->ClienteClientesServicos) {
            foreach ($cliente->ClienteClientesServicos as $servico) {
                if ($servico->TipoExameSituacao == Servico::$INATIVO) continue;
                $_servicos[] = $servico;
            }
        }

        $cliente->ClienteServicos = $_servicos;

        unset($cliente->ClienteClientesServicos);

        unset($cliente->ClienteClientesSolicitantes);

        $cliente->ClienteSolicitantes = [];

        $solicitantes = ClienteSolicitante::where('cliente_id', '=', $cliente->ClienteId)->get();
        if ($solicitantes) {
            foreach ($solicitantes as $solicitante) {
                $cliente->ClienteSolicitantes[] = $solicitante->solicita_id;
            }
        }

        return $cliente;
    }

    private function getClienteDoExame(Request $request)
    {
        $exameId = isset($request['body']) && isset($request['body']['exame_id']) ? $request['body']['exame_id'] : null;
        if (!$exameId) return [];
        $exame = Exame::where('id', $request['body']['exame_id'])->first();
        if (!$exame) return [];
        $cliente = Cliente::where('id', $exame->cliente_id)->first();
        if (!$cliente) return [];
        return array(
            'ClienteId' => $cliente->id,
            'ClienteNome' => $cliente->nome
        );
    }

    public function getSolicitantes(Request $request)
    {

        $usuario = $this->getUsuarioLogado($request);

        if ($usuario->isDesvinculado()) {
            return $this->getClienteDoExame($request);
        }

        $clienteId = $usuario->conta_cliente > 0 ? $usuario->conta_cliente : $request['body']['cliente_id'];

        if (!$clienteId) return [];

        $result[] = $this->entity->findOne(
            'clientes',
            ['where' => ['clientes.id = ', $clienteId]]
        );

        $solicitantes = ClienteSolicitante::where('cliente_id', '=', $clienteId)->get();
        foreach ($solicitantes as $solicitante) {
            $result[] = $this->entity->findOne(
                'clientes',
                ['where' => ['clientes.id = ', $solicitante->solicita_id]]
            );
        }

        return $result;
    }

    public function getClientes(Request $request)
    {

        $this->validarRequisicao($request);

        $empresa = $this->entity->findOne(
            'empresas',
            ['where' => ['empresas.login = ', $this->getEmpresaDoDominio($request)]]
        );

        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $login = $request['session'] ? $request['session']['login'] : null;
        $usuario = $login ? Usuario::where('login', $login)->first() : null;
        if (!$usuario) throw new AutenticacaoRequeridaException();

        $cliente = Cliente::where('id', $usuario->conta_cliente)->first();
        if ($cliente) return  array('ClienteId' => $cliente->id, 'ClienteNome' => $cliente->nome);

        return ClienteViewModel::getClientes($empresa->EmpresaId);
    }

    private function doFormUpload(Request $request)
    {

        $result = array(
            'cabecalho' => null,
            'rodape' => null,
            'logo-oit' => null,
        );

        $filesToUpload = $request->file('files');

        if (!$filesToUpload) {
            return $result;
        }

        $this->makeDir($this->storePath(Self::$PATH_ASSETS));

        foreach ($filesToUpload as $file) {
            $name = strtolower($file->getClientOriginalName());
            if (
                !str_contains($name, 'cabecalho') &&
                !str_contains($name, 'rodape') &&
                !str_contains($name, 'logo-oit')
            ) continue;

            $rawName = \Illuminate\Support\Str::random(40);
            $extension = strtolower($file->getClientOriginalExtension());
            $fullName = $rawName . '.' . $extension;
            $file->move(
                $this->storePath(Self::$PATH_ASSETS),
                $fullName
            );

            if (str_contains($name, 'cabecalho')) $result['cabecalho'] = Self::$PATH_ASSETS . $fullName;
            if (str_contains($name, 'rodape')) $result['rodape'] = Self::$PATH_ASSETS . $fullName;
            if (str_contains($name, 'logo-oit')) $result['logo-oit'] = Self::$PATH_ASSETS . $fullName;
        }

        return $result;
    }

    private function doValidate($data)
    {
        if ($this->isNullOrEmptyValue($data['ClienteCnpj'])) throw new CadastroException('O CNPJ é de preenchimento obrigatório!');
        return $data;
    }

    private function checarBloqueio($cliente)
    {
        $usuarios = Usuario::where('conta_cliente', $cliente->id)->get();
        if ($cliente->situacao == Cliente::$BLOQUEADO) {
            // bloqueia todos
            foreach ($usuarios as $usuario) {
                $usuario->situacao = Usuario::$BLOQUEADO;
                $usuario->save();
            }
        }
        if ($cliente->situacao == Cliente::$ATIVO) {
            // desbloqueia não inativos
            foreach ($usuarios as $usuario) {
                if ($usuario->inativo) continue;
                $usuario->situacao = Usuario::$ATIVO;
                $usuario->save();
            }
        }
    }

    public function setCliente(Request $request)
    {
        $this->validarRequisicao($request);

        $empresa = $this->entity->findOne(
            'empresas',
            ['where' => ['empresas.login = ', $this->getEmpresaDoDominio($request)]]
        );

        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $upload = $this->doFormUpload($request);

        $data = $request;

        $data = $this->doValidate($data);

        $acessowebservicesoc = isset($data->acessowebservicesoc) && $data->acessowebservicesoc == 'true';
        if ($acessowebservicesoc) {
            try {
                $request->validate([
                    "soc_codigo_empresa" => 'required',
                    "soc_codigo_exporta_cad_empresas" => 'required',
                    "soc_chave_exporta_cad_empresas" => 'required',
                    "soc_codigo_exporta_cad_funcionarios" => 'required',
                    "soc_chave_exporta_cad_funcionarios" => 'required',
                    "soc_codigo_exporta_pedido_exame" => 'required',
                    "soc_chave_exporta_pedido_exame" => 'required',
                    "soc_codigo_exporta_pedido_exame_seq_ficha" => 'required',
                    "soc_chave_exporta_pedido_exame_seq_ficha" => 'required',
                ], [], [
                    "soc_codigo_empresa" => '<b>Código da Empresa SOC</b>',
                    "soc_codigo_exporta_cad_empresas" => '<b>Código Exporta Dados Cadastro Empresas</b>',
                    "soc_chave_exporta_cad_empresas" => '<b>Chave Exporta Dados Cadastro Empresas</b>',
                    "soc_codigo_exporta_cad_funcionarios" => '<b>Código Exporta Dados Cadastro Funcionários</b>',
                    "soc_chave_exporta_cad_funcionarios" => '<b>Chave Exporta Dados Cadastro Funcionários</b>',
                    "soc_codigo_exporta_pedido_exame" => '<b>Código Exporta Dados Pedido Exame</b>',
                    "soc_chave_exporta_pedido_exame" => '<b>Chave Exporta Dados Pedido Exame</b>',
                    "soc_codigo_exporta_pedido_exame_seq_ficha" => '<b>Código Exporta Dados Pedido Exame Sequencial Ficha</b>',
                    "soc_chave_exporta_pedido_exame_seq_ficha" => '<b>Chave Exporta Dados Pedido Exame Sequencial Ficha</b>',
                ]);
            } catch (\Throwable $th) {
                throw $th;
            }
        }

        $novoCliente = is_null($data['ClienteId']);
        $cliente = $novoCliente ? new Cliente() : Cliente::where('id', $data['ClienteId'])->first();

        $cliente->nome = $data['ClienteNome'];
        $cliente->cnpj = $data['ClienteCnpj'];
        $cliente->email = $data['ClienteEmail'];
        $cliente->telas = filter_var($data['ClienteTelas'], FILTER_VALIDATE_BOOLEAN);
        $cliente->sistema = filter_var($data['ClienteSistema'], FILTER_VALIDATE_BOOLEAN);
        $cliente->situacao = $data['ClienteBloquear'] == 'false' ? Cliente::$ATIVO : Cliente::$BLOQUEADO;
        $cliente->inativo = $data['ClienteInativo'] == 'false' ? Cliente::$ATIVO : Cliente::$BLOQUEADO;
        $cliente->empresa_id = $cliente->empresa_id ?? $empresa->EmpresaId;
        $cliente->chave_transmissao = $novoCliente ? $this->getUniqId() : $data['ClienteChaveTransmissao'];

        // Orthanc Server InstitutionName
        $cliente->institution_name = $data['ClienteInstitutionName'];
        $cliente->institution_name_id =
            filter_var($data['ClienteResetInstitutionName'], FILTER_VALIDATE_BOOLEAN)
            ? OrthancInstitutionName::computeIdFromText($data['ClienteInstitutionName'])
            : $data['ClienteInstitutionNameId'];
        // Orthanc Server InstitutionName

        $cliente->mensagem_medicos = $data['ClienteMensagemMedicos'];
        $cliente->emergencia = filter_var($data['ClienteEmergencia'], FILTER_VALIDATE_BOOLEAN);
        $cliente->laudo_rapido = filter_var($data['ClienteLaudoRapido'], FILTER_VALIDATE_BOOLEAN);
        $cliente->laudo_imagem = filter_var($data['ClienteLaudoImagem'], FILTER_VALIDATE_BOOLEAN);
        $cliente->cabecalho = $upload['cabecalho'] ? $upload['cabecalho'] : $cliente->cabecalho;
        $cliente->rodape = $upload['rodape'] ? $upload['rodape'] : $cliente->rodape;
        $cliente->logo_oit = $upload['logo-oit'] ? $upload['logo-oit'] : $cliente->logo_oit;

        $ClienteJson = new ClienteJson();

        $ClienteJson->mensagem_urgencia_emergencia = $data['mensagem_urgencia_emergencia'];
        $cliente->json = $ClienteJson;

        if ($acessowebservicesoc) {
            $cliente->soc_codigo_empresa = $data['soc_codigo_empresa'];
            $cliente->soc_codigo_exporta_cad_empresas = $data['soc_codigo_exporta_cad_empresas'];
            $cliente->soc_chave_exporta_cad_empresas = $data['soc_chave_exporta_cad_empresas'];
            $cliente->soc_codigo_exporta_cad_funcionarios = $data['soc_codigo_exporta_cad_funcionarios'];
            $cliente->soc_chave_exporta_cad_funcionarios = $data['soc_chave_exporta_cad_funcionarios'];
            $cliente->soc_codigo_exporta_pedido_exame = $data['soc_codigo_exporta_pedido_exame'];
            $cliente->soc_chave_exporta_pedido_exame = $data['soc_chave_exporta_pedido_exame'];
            $cliente->soc_codigo_exporta_pedido_exame_seq_ficha = $data['soc_codigo_exporta_pedido_exame_seq_ficha'];
            $cliente->soc_chave_exporta_pedido_exame_seq_ficha = $data['soc_chave_exporta_pedido_exame_seq_ficha'];
        }

        $cliente->save();

        ClienteServico::where('cliente_id', '=', $cliente->id)->delete();

        $servicos = explode(',', $data['ClienteServicos']);
        foreach ($servicos as $value) {
            if (!$value) continue;
            $servico = new ClienteServico();
            $servico->cliente_id = $cliente->id;
            $servico->empresa_id = $cliente->empresa_id;
            $servico->tipo_exame_id = $value;
            $servico->save();
        }

        ClienteSolicitante::where('cliente_id', '=', $cliente->id)->delete();

        $solicitantes = explode(',', $data['ClienteSolicitantes']);

        foreach ($solicitantes as $value) {
            if (!$value) continue;
            $solicitante = new ClienteSolicitante();
            $solicitante->cliente_id = $cliente->id;
            $solicitante->solicita_id = $value;
            $solicitante->save();
        }

        $this->checarBloqueio($cliente);

        return ['id' => $cliente->id];
    }

    public function isClienteSOC(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $cliente = Cliente::where('id', $request['body']['id'])->first();

        if (!$cliente) throw new ClienteNaoEncontradoException();

        return [
            'is_cliente_soc' => empty($cliente->soc_codigo_empresa) ? 'false' : 'true',
            'is_empresa_soc' => empty($empresa->soc_codigo_empresa_principal) ? 'false' : 'true',
        ];
    }
}
