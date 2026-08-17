<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\ViewModels\DeviceViewModel;
use App\ViewModels\LoginViewModel;
use App\Models\FichaProcedimento;

class ProcedimentoController extends Controller
{

    public function __construct()
    {
    }

    public function setProcedimento(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED, Self::$SESSION_NOT_REQUIRED);
        $query = $this->getQuery($request, $request['body']['FichaNumero']);
        $campos = $this->getCamposDaFicha($query);
        $campos = $this->preencheValores($campos['FichaCampos'], $request['body']);
        $fichaId = $request['body']['FichaId'];
        FichaProcedimento::where('ficha_id', $fichaId)->delete();
        foreach($campos as $key => $campo){
            $fp = new FichaProcedimento;
            $fp->ficha_id = $fichaId;
            $fp->nome = $campo['nome'];
            $fp->tipo = $campo['tipo'];
            $fp->ordem = $campo['ordem'];
            $fp->tamanho = $campo['tamanho'];
            $fp->opcoes = $campo['opcoes'];
            $fp->obrigatorio = $campo['obrigatorio'];
            $fp->valor = $campo['valor'];
            $fp->save();
        }
    }

}
