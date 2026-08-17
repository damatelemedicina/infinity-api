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
            Self::$ENTITY_WITH_CHILDS
        );

        if (!$cliente) throw new ClienteNaoEncontradoException();

        $_servicos = [];

        if ($cliente->ClienteClientesServicos) {
            foreach($cliente->ClienteClientesServicos as $servico){
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
            foreach($solicitantes as $solicitante) {
                $cliente->ClienteSolicitantes[] = $solicitante->solicita_id;
            }
        }

        return $cliente;

    }

    public function getSolicitantes(Request $request)
    {

        $usuario = $this->getUsuarioLogado($request);

        $clienteId = $usuario->conta_cliente > 0 ? $usuario->conta_cliente : $request['body']['cliente_id'];

        if (!$clienteId) return [];

        $result[] = $this->entity->findOne(
            'clientes',
            ['where' => ['clientes.id = ', $clienteId]]
        );

        $solicitantes = ClienteSolicitante::where('cliente_id', '=', $clienteId)->get();
        foreach($solicitantes as $solicitante) {
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
        if ($cliente) return  array ('ClienteId' => $cliente->id, 'ClienteNome' => $cliente->nome);

        return ClienteViewModel::getClientes($empresa->EmpresaId);

    }

    private function doFormUpload(Request $request) {

        $result = array(
            'cabecalho' => null,
            'rodape' => null,
            'logo-oit' => null,
        );

        $filesToUpload =$request->file('files');

        if (!$filesToUpload) {
            return $result;
        }

        $this->makeDir($this->storePath(Self::$PATH_ASSETS));

        foreach ($filesToUpload as $file) {
            $name = strtolower($file->getClientOriginalName());
            if (
                !str_contains($name, 'cabecalho') &&
                !str_contains($name, 'rodape') &&
                !str_contains($name, 'logo-oit')) continue;

            $rawName = \Str::random(40);
            $extension = strtolower($file->getClientOriginalExtension());
            $fullName = $rawName.'.'.$extension;
            $file->move(
                $this->storePath(Self::$PATH_ASSETS),
                $fullName
            );

            if (str_contains($name, 'cabecalho')) $result['cabecalho'] = Self::$PATH_ASSETS.$fullName;
            if (str_contains($name, 'rodape')) $result['rodape'] = Self::$PATH_ASSETS.$fullName;
            if (str_contains($name, 'logo-oit')) $result['logo-oit'] = Self::$PATH_ASSETS.$fullName;

        }

        return $result;

    }

    private function doValidate($data) {
        if ($this->isNullOrEmptyValue($data['ClienteCnpj'])) throw new CadastroException('O CNPJ é de preenchimento obrigatório!');
        return $data;
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

        $novoCliente = is_null($data['ClienteId']);
        $cliente = $novoCliente ? new Cliente() : Cliente::where('id', $data['ClienteId'])->first();

        $cliente->nome = $data['ClienteNome'];
        $cliente->cnpj = $data['ClienteCnpj'];
        $cliente->email = $data['ClienteEmail'];
        $cliente->telas = filter_var($data['ClienteTelas'], FILTER_VALIDATE_BOOLEAN);
        $cliente->situacao = $data['ClienteBloquear'] == 'false' ? Cliente::$ATIVO : Cliente::$BLOQUEADO;
        $cliente->empresa_id = $cliente->empresa_id ?? $empresa->EmpresaId;
        $cliente->chave_transmissao = $novoCliente ? $this->getUniqId() : $data['ClienteChaveTransmissao'];
        $cliente->institution_name=$data['ClienteInstitutionName'];
        $cliente->mensagem_medicos = $data['ClienteMensagemMedicos'];

        $cliente->emergencia=filter_var($data['ClienteEmergencia'], FILTER_VALIDATE_BOOLEAN);
        $cliente->laudo_rapido=filter_var($data['ClienteLaudoRapido'], FILTER_VALIDATE_BOOLEAN);
        $cliente->laudo_imagem=filter_var($data['ClienteLaudoImagem'], FILTER_VALIDATE_BOOLEAN);
        $cliente->cabecalho = $upload['cabecalho'] ? $upload['cabecalho'] : $cliente->cabecalho;
        $cliente->rodape = $upload['rodape'] ? $upload['rodape'] : $cliente->rodape;
        $cliente->logo_oit = $upload['logo-oit'] ? $upload['logo-oit'] : $cliente->logo_oit;

        $cliente->save();

        ClienteServico::where('cliente_id', '=', $cliente->id)->delete();

        $servicos = explode(',', $data['ClienteServicos']);
        foreach($servicos as $value){
            if (!$value) continue;
            $servico = new ClienteServico();
            $servico->cliente_id = $cliente->id;
            $servico->empresa_id = $cliente->empresa_id;
            $servico->tipo_exame_id = $value;
            $servico->save();
        }

        ClienteSolicitante::where('cliente_id', '=', $cliente->id)->delete();

        $solicitantes = explode(',', $data['ClienteSolicitantes']);

        foreach($solicitantes as $value) {
            if (!$value) continue;
            $solicitante = new ClienteSolicitante();
            $solicitante->cliente_id = $cliente->id;
            $solicitante->solicita_id = $value;
            $solicitante->save();
        }

        return ['id' => $cliente->id];

    }

}
