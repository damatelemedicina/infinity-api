<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\MotivoDeImpossibilidadeNaoEncontradoException;

use App\Models\Impossibilidade;

class LaudoController extends Controller {

    public function getImpossibilidade(Request $request){
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $model = Impossibilidade::where('id', $request['body']['id'])->first();
        if (!$model) throw new MotivoDeImpossibilidadeNaoEncontradoException();
        return $model;
    }

    public function getImpossibilidades(Request $request){
        $this->validarRequisicao($request);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        return Impossibilidade::where('empresa_id', $empresa->matriz)->get();
    }

    public function setImpossibilidade(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $data = $request["body"];
        $model = isset($data['ImpossibilidadeId'])
                    ? Impossibilidade::where('id', $data['ImpossibilidadeId'])->first()
                    : new Impossibilidade();
        $model->nome = $data['ImpossibilidadeNome'];
        $model->empresa_id = isset($data['ImpossibilidadeId']) ?
                                 $model->empresa_id :
                                 $this->getMatriz($request);
        $model->save();
        return ['id' => $model->id];
    }

}
