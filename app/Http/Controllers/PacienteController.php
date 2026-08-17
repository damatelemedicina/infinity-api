<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\RegistroNaoEncontradoException;

use App\Services\Entity\EntityManager;

use App\Models\Paciente;
use App\Models\Empresa;

class PacienteController extends Controller
{

    protected $entity;

    public function __construct(EntityManager $entity)
    {
        $this->entity = $entity;
    }

    public function getPaciente(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $documento = $this->onlyDigits($request['body']['documento']);
        $paciente = Paciente::where('rg', $documento)->orWhere('cpf', $documento)->first();
        if (!$paciente) throw new RegistroNaoEncontradoException();
        return $paciente;
    }

}
