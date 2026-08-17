<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\UsuarioNaoAssociadoAClienteException;

use App\Services\Entity\EntityManager;

use App\Models\Servico;
use App\Models\Empresa;

class ServicoController extends Controller
{

    protected $entity;

    public function __construct(EntityManager $entity)
    {
        $this->entity = $entity;
    }

    public function getServicosCliente(Request $request) {
        $this->validarRequisicao($request);
        $usuario = $this->getUsuarioLogado($request);
        if (!$usuario) throw new LoginInvalidoException();
        $cliente = $this->getClienteByUsuario($request);
        if (!$cliente) return []; // Usuário pode ser médico! throw new UsuarioNaoAssociadoAClienteException();
        return $cliente->servicos();
    }

    public function getServicosFilial(Request $request) {

        $this->validarRequisicao($request);

        $empresa = Empresa::where(
            'login', '=', $this->getEmpresaDoDominio($request))->first();

        if (!$empresa) throw new EmpresaNaoEncontradaException();

        if ($empresa->id == $empresa->matriz) {
            return $this->entity->findAll(
                'tipo_exames',
                [
                    'where' => [
                        'tipo_exames.empresa_id = ', $empresa->id,
                        ' and tipo_exames.situacao = ', Servico::$ATIVO
                    ]
                ]
            );
        }

        $servicos = $this->entity->findAll(
            'servicos',
            [
                'where' => [
                    'servicos.filial_id = ', $empresa->id
                ]
            ],
        );

        if (!$servicos) return [];

        $_servicos = [];

        foreach($servicos as $servico){
            if ($servico->TipoExameSituacao == Servico::$INATIVO) continue;
            $_servicos[] = array(
                'TipoExameId' => $servico->TipoExameId,
                'TipoExameNome' => $servico->TipoExameNome
            );
        }

        return $_servicos;

    }

}
