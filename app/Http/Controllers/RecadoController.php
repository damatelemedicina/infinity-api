<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Exceptions\EmpresaNaoEncontradaException;

use App\Models\Empresa;

class RecadoController extends Controller {

    public function __construct()
    {
    }

    public function getRecados(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        return [
            'recado_medicos' => $empresa->recado_medicos,
            'recado_clientes' => $empresa->recado_clientes,
            'recado_colaboradores' => $empresa->recado_colaboradores
        ];
    }

    public function setRecados(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $empresa->recado_medicos = $request['body']['recado_medicos'];
        $empresa->recado_clientes = $request['body']['recado_clientes'];
        $empresa->recado_colaboradores = $request['body']['recado_colaboradores'];
        $empresa->save();
        return [];
    }

    public function mostraRecados(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $usuario = $this->getUsuarioLogado($request);
        $isColaborador = $usuario->conta_cliente == 0 && $usuario->conta_medico == 0;
        $isMedico = $usuario->conta_cliente == 0 && $usuario->conta_medico > 0;
        $isCliente = $usuario->conta_cliente > 0 && $usuario->conta_medico == 0;
        $isMatriz = $empresa->id == $empresa->matriz;
        $recados = $this->getRecadosEmpresa($empresa, $isColaborador, $isMedico, $isCliente, []);
        $recados = $this->getRecadosMatriz($empresa, $isColaborador, $isMedico, $isCliente, $recados);
        return $recados;
    }

    private function getRecadosMatriz($empresa, $isColaborador, $isMedico, $isCliente, $recados) {
        if ($empresa->id == $empresa->matriz) return $recados;
        if (!$isColaborador) return $recados;
        $matriz = Empresa::where('id', $empresa->matriz)->first();
        if (!$matriz) throw new EmpresaNaoEncontradaException();
        $recados[] = ['recado' => $matriz->recado_clientes, 'origem' => $matriz->login, 'matriz' => true];
        return $recados;
    }

    private function getRecadosEmpresa($empresa, $isColaborador, $isMedico, $isCliente, $recados) {
        if ($isColaborador) $recados[] = ['recado' => $empresa->recado_colaboradores, 'origem' => $empresa->login];
        if ($isMedico) $recados[] = ['recado' => $empresa->recado_medicos, 'origem' => $empresa->login];
        if ($isCliente) $recados[] = ['recado' => $empresa->recado_clientes, 'origem' => $empresa->login];
        return $recados;
    }


}
