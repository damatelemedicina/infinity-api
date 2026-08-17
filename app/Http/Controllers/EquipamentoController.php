<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\CadastroException;

use App\Models\Equipamento;
use App\Models\Cliente;

class EquipamentoController extends Controller
{

    private function doValidate($data) {
        if ($this->isNullOrEmptyValue($data['serie'])) throw new CadastroException("Nº de série do equipamento é requerido!");
        if ($this->isNullOrEmptyValue($data['exame'])) throw new CadastroException("Tipo de exame é requerido!");
        if ($this->isNullOrEmptyValue($data['marca'])) throw new CadastroException("Marca do equipamento é requerido!");
        return $data;
    }

    public function getEquipamentos(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $equipamentos = Equipamento::where('empresa_id', $empresa->id)->get();
        foreach ($equipamentos as $equipamento) {
            $cliente = Cliente::Where('id', $equipamento->cliente_id)->first();
            $equipamento->clienteNome = $cliente ? $cliente->nome : "NAO DEFINIDO";
        }
        return $equipamentos;
    }

    public function setEquipamento(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $data = $this->doValidate($request['body']);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $model = isset($data['id']) ? Equipamento::where('id', $data['id'])->first() : new Equipamento();
        $data['empresa_id'] = $empresa->id;
        $model->fill($data);
        $model->save();
        return ['id' => $model->id ];
    }

    public function getEquipamento(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $model = Equipamento::where('id', $request['body']['id'])->first();
        if (!$model) throw new CadastroException("Equipamentos não encontrado!");
        return $model;
    }
}
