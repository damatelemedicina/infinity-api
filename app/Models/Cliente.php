<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    public static $ATIVO = 0;
    public static $BLOQUEADO = 1;
    public static $MARCADO = 1;

    public function servicos() {
        $result = [];
        $servicos = ClienteServico::where('cliente_id', $this->id)->get();
        if (!$servicos) return $result;
        foreach ($servicos as $servico) {
            $tipoExame = TipoExame::where('id', $servico->tipo_exame_id)->first();
            $result[] = array('id' => $tipoExame->id, 'nome' => $tipoExame->nome);
        }
        return $result;
    }

    public function imagemLaudoDesativado() {
        return $this->laudo_imagem == Self::$MARCADO;
    }
}
