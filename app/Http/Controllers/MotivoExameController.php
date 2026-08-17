<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\RegistroNaoEncontradoException;
use App\Exceptions\EmpresaNaoEncontradaException;

use App\Services\Entity\EntityManager;

use App\Models\Empresa;
use App\Models\MotivoExame;

class MotivoExameController extends Controller
{

    protected $entity;

    public function __construct(EntityManager $entity)
    {
        $this->entity = $entity;
    }


    function getMotivoExames(Request $request)
    {
        $this->validarRequisicao($request);

        $empresa = Empresa::where('id', $this->getMatriz($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $motivos = $this->entity->findAll(
            'motivo_exames',
            ['where' => ['motivo_exames.empresa_id = ', $empresa->id]]
        );

        return $motivos;

    }

    function getMotivoExame(Request $request) {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $motivo = $this->entity->findOne(
            'motivo_exames',
            [
                'where' => ['motivo_exames.id = ', $request['body']['id']],
            ],
        );

        if (!$motivo) throw new RegistroNaoEncontradoException();

        return $motivo;
    }

    public function setMotivoExame(Request $request)
    {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $data = $request["body"];

        $motivo = isset($data['MotivoExameId'])
                    ? MotivoExame::where('id', $data['MotivoExameId'])->first()
                    : new MotivoExame();

        $motivo->nome = $data['MotivoExameNome'];
        $motivo->atendimento = $data['MotivoExameAtendimento'];
        $motivo->empresa_id = isset($data['MotivoExameId']) ?
                                 $motivo->empresa_id :
                                 $this->getMatriz($request);

        $motivo->padrao = $data['MotivoExamePadrao'];

        $motivo->save();

        return $motivo;

    }

}
