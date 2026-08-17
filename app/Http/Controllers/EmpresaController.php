<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\EmpresaBloqueadaException;
use App\Exceptions\EmpresaJaCadastradaException;
use App\Exceptions\RequisicaoMalFormadaException;
use App\Exceptions\ExclusaoNaoPermitidaException;
use App\Exceptions\CamposObrigatoriosException;
use App\Exceptions\BloqueioNaoPermitidoException;

use App\ViewModels\LoginViewModel;
use App\Models\Empresa;
use App\Models\Servico;

class EmpresaController extends Controller
{
    public function getEmpresas(Request $request) {

        $this->validarRequisicao($request);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        if ($empresa->situacao == Empresa::$BLOQUEADA) throw new EmpresaBloqueadaException();

        // $login = LoginViewModel::getLogin(
        //     $this->getEmpresaDoDominio($request),
        //     $request['session']['login']
        // );

        // Todas as empresas da matriz
        $empresas = Empresa::where('matriz', $empresa->matriz)->get();

        $result = [];

        foreach ($empresas as $e) {
            if ($e->situacao == Empresa::$INATIVA) continue;
            if ($e->id == $empresa->id || $e->matriz == $empresa->id) {
                $servicos = Servico::where('empresa_id', $e['id'])->get();
                $result[] = [
                    'EmpresaId' => $e['id'],
                    'EmpresaLogin' => $e['login'],
                    'EmpresaNome' => $e['nome'],
                    'EmpresaSituacao' => $e['situacao'],
                    'EmpresaMedicoCompartilhado' => $e['medico_compartilhado'],
                    'EmpresaServicos' => $servicos
                ];

            }

        }

        return response()->json($result);

    }

    public function setEmpresa(Request $request)
    {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $data = $request["body"];

        if (!isset($data['EmpresaLogin']) || !isset($data['EmpresaNome']))
            throw new CamposObrigatoriosException();

        $empresa = Empresa::where('login', $data['EmpresaLogin'])->first();
        if ($empresa) {

            if (!isset($data['EmpresaId']) || $empresa->id != $data['EmpresaId'])
                throw new EmpresaJaCadastradaException();

            if (isset($data['EmpresaBloquear']) && $data['EmpresaBloquear']) {

                // Bloqueio do próprio domínio
                if ($this->getEmpresaDoDominio($request) == $data['EmpresaLogin'])
                   throw new BloqueioNaoPermitidoException();

                // Tentativa de bloqueio da matriz desse domínio
                $empresaAtual = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
                if ($empresaAtual->matriz == $empresa->id) throw new BloqueioNaoPermitidoException();

            }

        } else {

            $empresa = new Empresa();
            $empresa->matriz = $this->getMatriz($request);

        }

        $empresa->login = $data['EmpresaLogin'];
        $empresa->nome = $data['EmpresaNome'];
        $empresa->situacao = (isset($data['EmpresaBloquear']) && $data['EmpresaBloquear'])
                             ? Empresa::$BLOQUEADA : Empresa::$ATIVA;

        $empresa->medico_compartilhado = (
            isset($data['EmpresaMedicoCompartilhado']) && $data['EmpresaMedicoCompartilhado']
        ) ? 1 : 0;

        $empresa->save();

        Servico::where('filial_id', '=', $empresa->id)->delete();

        foreach($data['EmpresaServicos'] as $value){
            if (!$value) continue;
            $servico = new Servico();
            $servico->empresa_id = $empresa->matriz;
            $servico->filial_id = $empresa->id;
            $servico->tipo_exame_id = $value;
            $servico->save();
        }

        return array(
            'EmpresaId' => $empresa->id,
            'EmpresaLogin' => $empresa->login,
            'EmpresaNome' => $empresa->nome,
            'EmpresaMatriz' => $empresa->matriz,
            'EmpresaSituacao' => $empresa->situacao,
            'EmpresaServicos' => $data['EmpresaServicos']
        );

    }

    public function delEmpresa(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        if (!isset($request['body']['login'])) throw new RequisicaoMalFormadaException();
        $login = strtoupper($request['body']['login']);
        if ($this->getEmpresaDoDominio($request) == $login) throw new ExclusaoNaoPermitidaException();
        $empresa = Empresa::where('login', $login)->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $empresa->situacao = Empresa::$INATIVA;
        $empresa->save();
        return $this->getEmpresas($request);
    }

    public function getEmpresa(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        if (!isset($request['body']['login'])) throw new RequisicaoMalFormadaException();
        $login = strtoupper($request['body']['login']);
        $empresa = Empresa::where('login', $login)->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $servicos = Servico::where('filial_id', $empresa['id'])
        ->select('tipo_exame_id AS TipoExameId')
        ->get();

/*
        $servicos = array(
            array(
                'TipoExameId' => '1',
                //'TipoExameNome' => 'ECG Ocupacional I'
            ),
        );
*/
        return [
            'EmpresaId' => $empresa['id'],
            'EmpresaLogin' => $empresa['login'],
            'EmpresaNome' => $empresa['nome'],
            'EmpresaMatriz' => $empresa['matriz'],
            'EmpresaSituacao' => $empresa['situacao'],
            'EmpresaMedicoCompartilhado' => $empresa['medico_compartilhado'],
            'EmpresaServicos' => $servicos
        ];
    }

}
