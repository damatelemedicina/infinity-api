<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Usuario extends BaseModel
{
    use HasFactory;

    public static $ATIVO = 0;
    public static $BLOQUEADO = 1;
    public static $INATIVO = 1;
    public static $V2_ATIVO = 1;
    public static $V2_INATIVO = 0;

    public function cliente()
    {
        return Cliente::where('id', $this->conta_cliente)->first();
    }

    public function medico()
    {
        return Medico::where('id', $this->conta_medico)->first();
    }

    public function perfil()
    {
        return Perfil::where('id', $this->perfil_id)->first();
    }

    public function isDesvinculado()
    {
        return $this->conta_cliente == 0 && $this->conta_medico == 0;
    }

    static function serverProcessing($EmpresaMatriz, $EmpresaId)
    {
        $situacao = DB::raw("(case when usu.situacao = " . self::$ATIVO . " then 'ATIVO' else 'BLOQUEADO' end)");

        $whereColumns = [
            'usu.id',
            'usu.nome',
            'usu.login',
            'prf.nome',
            'ac.acessos',
            $situacao,
            'cli.nome',
            'med.nome',
        ];

        $query = DB::table('usuarios', 'usu')
            ->selectRaw("usu.id," . PHP_EOL
                . "    usu.nome," . PHP_EOL
                . "    usu.login," . PHP_EOL
                . "    prf.nome as perfil," . PHP_EOL
                . "    ac.acessos," . PHP_EOL
                . "    {$situacao} as situacao," . PHP_EOL
                . "    cli.nome as conta_cliente," . PHP_EOL
                . "    med.nome as conta_medico")
            ->leftJoin('perfils as prf', 'prf.id', '=', 'usu.perfil_id')
            ->leftJoin('clientes as cli', 'cli.id', '=', 'usu.conta_cliente')
            ->leftJoin('medicos as med', 'med.id', '=', 'usu.conta_medico')
            ->leftJoinSub(DB::table('acessos', 'ac')
                ->selectRaw('count(distinct ac.empresa_id) as cnt_matriz, ac.usuario_id')
                ->where("ac.empresa_id", '=', $EmpresaMatriz)
                ->groupByRaw('ac.usuario_id'), 'ac_matriz', 'ac_matriz.usuario_id', '=', 'usu.id');

        // Login da matriz, exibe todos os usuários!
        $isMatriz = $EmpresaId == $EmpresaMatriz;

        $subQueryAcessos = DB::table('acessos', 'ac')
            ->selectRaw('GROUP_CONCAT(distinct emp.login) as acessos, ac.usuario_id')
            ->join('empresas as emp', 'emp.id', '=', 'ac.empresa_id')
            ->groupByRaw('ac.usuario_id');

        if ($isMatriz == false) {
            // Login do domínio, não exibe usuários com acesso a matriz!
            $subQueryAcessos->where("ac.empresa_id", '!=', $EmpresaMatriz);
            $query->whereRaw("coalesce(ac_matriz.cnt_matriz, 0) = 0");

            // Login do domínio, só exibe usuários com acesso ao domínio
            $subQueryAcessos->where("ac.empresa_id", '=', $EmpresaId);
        }

        $query->joinSub($subQueryAcessos, 'ac', 'ac.usuario_id', '=', 'usu.id', $isMatriz ? 'left' : 'inner');

        return self::serverProcessingBase($query, $whereColumns);
    }
}
