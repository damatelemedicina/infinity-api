<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\MedicoNaoEncontradoException;
use App\Exceptions\ModeloNaoEncontradoException;
use App\Exceptions\UsuarioNaoAssociadoAMedicoException;
use App\Exceptions\CadastroException;
use App\Exceptions\AutenticacaoRequeridaException;

use App\Services\Entity\EntityManager;
use App\Services\Security\PermissionManager;
use App\Services\Security\CadastroManager;

use Illuminate\Support\Facades\Log;

use App\Models\Medico;
use App\Models\MedicoExame;
use App\Models\MedicoModelo;
use App\Models\Usuario;

class MedicoController extends Controller
{
    protected $entity;
    protected $permissions;

    public function __construct(EntityManager $entity, PermissionManager $permissions)
    {
        $this->entity = $entity;
        $this->permissions = $permissions;
    }

    public function getMedico(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $medico = Medico::where('id', $request['body']['id'])->first();
        if (!$medico) throw new MedicoNaoEncontradoException();
        $medico->exames = $medico->exames();
        $medico->modelos = $medico->modelos();
        return $medico;
    }

    public function getMedicosCompartilhados(Request $request) {
        $this->validarRequisicao($request);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        if (!$empresa->medico_compartilhado) return $this->getMedicos($request);
        return Medico::whereIn('empresa_id', [ $empresa->id, $empresa->matriz])->get();
    }

    public function getMedicos(Request $request)
    {
        $this->validarRequisicao($request);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $login = $request['session'] ? $request['session']['login'] : null;
        $usuario = $login ? Usuario::where('login', $login)->first() : null;
        if (!$usuario) throw new AutenticacaoRequeridaException();
        $medico = Medico::where('id', $usuario->conta_medico)->first();
        return $medico ? $medico : Medico::where('empresa_id', $empresa->id)->get();
    }

    private function doFormUpload(Request $request) {

        $result = array(
            'certificado' => null,
            'assinatura' => null,
            'arquivo' => null,
            'assinatura-oit' => null
        );

        $filesToUpload =$request->file('files');

        if (!$filesToUpload) {
            return $result;
        }

        $this->makeDir($this->storePath(Self::$PATH_ASSETS));

        foreach ($filesToUpload as $file) {
            $type = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            $name = str_replace(
                '.' . $extension,
                '',
                strtolower($file->getClientOriginalName())
            );

            if (!str_contains($type, 'image') && $extension != 'pfx') continue;

            $fullName =\Str::random(40).'.'.$extension;

            $file->move(
                $this->storePath(Self::$PATH_ASSETS),
                $fullName
            );

            if ($name == 'assinatura') {
                $result['assinatura'] = Self::$PATH_ASSETS.$fullName;
            }

            if ($name == 'assinatura-oit') {
                $result['assinatura-oit'] = Self::$PATH_ASSETS.$fullName;
            }

            if ($extension == 'pfx') {
                $result['certificado'] = Self::$PATH_ASSETS.$fullName;
                $result['arquivo'] = strtolower($file->getClientOriginalName());
            }

        }

        return $result;

    }

    private function doValidate($data) {
        if ($this->isNullOrEmptyValue($data['crm'])) throw new CadastroException('O CRM é de preenchimento obrigatório!');
        return $data;
    }

    public function setMedico(Request $request)
    {
        $this->validarRequisicao($request);

        $data = $request;

        $upload = $this->doFormUpload($request);

        $data = $this->doValidate($data);

        $bloqueado = filter_var($data['bloqueado'], FILTER_VALIDATE_BOOLEAN);

        $renovar = filter_var($data['renovar'], FILTER_VALIDATE_BOOLEAN);

        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));

        $medico = is_null($data['id']) ? new Medico() : Medico::where('id', $data['id'])->first();

        $medico->nome = $data['nome'];
        $medico->email = $data['email'];
        $medico->situacao = $bloqueado ? Medico::$BLOQUEADO : Medico::$ATIVO;
        $medico->crm = $data['crm'];
        $medico->solicitante = $this->isNullOrEmptyValue($data['solicitante']) ? 0 : $data['solicitante'];
        $medico->empresa_id = $medico->empresa_id ?? $empresa->id;
        $medico->assinatura = $this->isNullOrEmptyValue($upload['assinatura']) ? $medico->assinatura : $upload['assinatura'];
        $medico->assinatura_oit = $this->isNullOrEmptyValue($upload['assinatura-oit']) ? $medico->assinatura_oit : $upload['assinatura-oit'];

        if ($renovar) {
            $medico->certificado = $this->isNullOrEmptyValue($upload['certificado']) ? null : $upload['certificado'];
            $medico->arquivo = $this->isNullOrEmptyValue($upload['arquivo']) ? null : $upload['arquivo'];
            $medico->expira =  $this->toDateTime($request['expira']);
            $medico->senha = $request['senha'];
        }

        $medico->save();

        $this->liberarExames(
            $medico->id,
            $this->checkIfNull($data['liberar_exames'], false)
        );

        $recusas = $this->getListaDeRecusas($data['recusas']);

        MedicoExame::where('medico_id', '=', $medico->id)->delete();
        $exames = explode(',', $data['exames']);
        foreach($exames as $id){
            if (!$id) continue;
            $exame = new MedicoExame();
            $exame->medico_id = $medico->id;
            $exame->tipo_exame_id = $id;
            $exame->recusa = str_contains($recusas, '|' . $id . '|');
            $exame->save();
        }

        return ['id' => $medico->id];

    }

    private function getListaDeRecusas($recusas) {
        return '|' . str_replace(',', '|', $recusas) . '|';
    }

    private function getMedicoAtual(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $usuario = $this->getUsuarioLogado($request);
        if ($usuario->conta_medico == Self::$MEDICO_NAO_DEFINIDO) return null;
        $medico = Medico::where('id', $usuario->conta_medico)->first();
        if (!$medico) throw new MedicoNaoEncontradoException();
        return $medico;
    }

    public function getModelos(Request $request) {
        $medico = $this->getMedicoAtual($request);
        return !$medico ? [] : $medico->modelos();
    }

    public function getModelo(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $modelo = MedicoModelo::where('id', $request['body']['id'])->first();
        if (!$modelo) throw new ModeloNaoEncontradoException();
        return $modelo;
    }

    public function setModelo(Request $request) {
        $medico = $this->getMedicoAtual($request);
        if (!$medico) throw new MedicoNaoEncontradoException();
        $modelo = new MedicoModelo();
        if (isset($request['body']['id'])) {
            $modelo =  MedicoModelo::where('id', $request['body']['id'])->first();
            if (!$modelo) throw new ModeloNaoEncontradoException();
        }
        $modelo->medico_id = $medico->id;
        $modelo->nome = $request['body']['nome'];
        $modelo->padrao = $request['body']['padrao'];
        $modelo->modelo = $request['body']['text'] ? $request['body']['modelo'] : null;
        if ($modelo->nome && $modelo->modelo) $modelo->save();
        else $modelo->delete();
        return [ 'id' => $modelo->id ];
    }

}
