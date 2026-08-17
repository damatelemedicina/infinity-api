<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Exceptions\FinanceiroException;

use App\Models\PrecoCliente;
use App\Models\PrecoMedico;
use App\Models\Cliente;
use App\Models\Medico;
use App\Models\TipoExame;

class FinanceiroController extends Controller
{
    private static $UNITARIO = 'UNITARIO';

    private function getPrecoClienteNomeRegra($data) {
        $cliente = Cliente::where('id', $data['PrecoClienteCliente'])->first();
        $tipoExame = TipoExame::where('id', $data['PrecoClienteTipoExame'])->first();
        return rtrim($cliente->nome) . '-' . rtrim($tipoExame->nome);
    }

    public function setPrecoCliente(Request $request) {

        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));

        $data = $request['body'];

        $data = $this->doValidatePrecoCliente($data);

        $model = isset($data['PrecoClienteId'])
                    ? PrecoCliente::where('id', $data['PrecoClienteId'])->first()
                    : new PrecoCliente();

        $model->nome = isset($data['PrecoClienteNome']) ? $data['PrecoClienteNome'] : $this->getPrecoClienteNomeRegra($data);
        $model->empresa_id = $empresa->id;
        $model->cliente_id = $data['PrecoClienteCliente'];
        $model->tipo_exame_id = $data['PrecoClienteTipoExame'];
        $model->vigencia_de = $this->toDateTime($data['PrecoClienteVigenciaDe']);
        $model->vigencia_ate = $this->toDateTime($data['PrecoClienteVigenciaAte']);
        $model->de1 = $data['PrecoClienteDe1'];
        $model->ate1 = $data['PrecoClienteAte1'];
        $model->preco1 = $data['PrecoClientePreco1'];
        $model->cobranca1 = $data['PrecoClienteCobranca1'];
        $model->de2 = $data['PrecoClienteDe2'];
        $model->ate2 = $data['PrecoClienteAte2'];
        $model->preco2 = $data['PrecoClientePreco2'];
        $model->cobranca2 = $data['PrecoClienteCobranca2'];
        $model->save();
        return ['id' => $model->id];
    }

    private function toNumber($value) {
        if (is_numeric($value)) return $value;
        throw new FinanceiroException("Valor incorreto! " . $value);
    }

    private function doValidatePrecoCliente($data) {
        if (!isset($data['PrecoClienteCliente'])) throw new FinanceiroException("Cliente não informado!");
        if (!isset($data['PrecoClienteTipoExame'])) throw new FinanceiroException("Tipo de exame não informado!");
        if (!isset($data['PrecoClienteVigenciaDe']) || !isset($data['PrecoClienteVigenciaAte'])) {
            throw new FinanceiroException("Vigência não informada!");
        }

        if ($this->isNullOrEmptyValue($data['PrecoClienteDe1']) ||
            $this->isNullOrEmptyValue($data['PrecoClienteAte1']) ||
            $this->isNullOrEmptyValue($data['PrecoClientePreco1']) ||
            $this->isNullOrEmptyValue($data['PrecoClienteCobranca1'])) {
            throw new FinanceiroException("faixa 1 não informada!");
        }

        $data['PrecoClienteDe1'] = $this->toNumber($data['PrecoClienteDe1']);
        $data['PrecoClienteAte1'] = $this->toNumber($data['PrecoClienteAte1']);
        $data['PrecoClientePreco1'] = $this->toNumber($data['PrecoClientePreco1']);

        if ($this->isNullOrEmptyValue($data['PrecoClienteCobranca2'])) {
            $data['PrecoClienteDe2'] = null;
            $data['PrecoClienteAte2'] = null;
            $data['PrecoClientePreco2'] = null;
            $data['PrecoClienteCobranca2'] = null;
            return $data;
        }

        if ($this->isNullOrEmptyValue($data['PrecoClienteDe2']) ||
            $this->isNullOrEmptyValue($data['PrecoClienteAte2']) ||
            $this->isNullOrEmptyValue($data['PrecoClientePreco2']) ||
            $this->isNullOrEmptyValue($data['PrecoClienteCobranca2'])) {
            throw new FinanceiroException("faixa 2 não informada!");
        }

        $data['PrecoClienteDe2'] = $this->toNumber($data['PrecoClienteDe2']);
        $data['PrecoClienteAte2'] = $this->toNumber($data['PrecoClienteAte2']);
        $data['PrecoClientePreco2'] = $this->toNumber($data['PrecoClientePreco2']);

        return $data;

    }

    public function getPrecoCliente(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $model = PrecoCliente::where('id', $request['body']['id'])->first();
        if (!$model) throw new FinanceiroException("Regra não encontrada!");
        return $model;
    }

    public function getPrecoClientes(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        return PrecoCliente::where('empresa_id', $empresa->id)->get();
    }

    // ------------- MEDICOS

    private function doValidatePrecoMedico($data) {
        if (!isset($data['PrecoMedicoMedico'])) throw new FinanceiroException("Médico não informado!");
        if (!isset($data['PrecoMedicoTipoExame'])) throw new FinanceiroException("Tipo de exame não informado!");
        if (!isset($data['PrecoMedicoVigenciaDe']) || !isset($data['PrecoMedicoVigenciaAte'])) {
            throw new FinanceiroException("Vigência não informada!");
        }
        $data['PrecoMedicoPreco'] = $this->toNumber($data['PrecoMedicoPreco']);
        return $data;
    }

    private function getPrecoMedicoNomeRegra($data) {
        $medico = Medico::where('id', $data['PrecoMedicoMedico'])->first();
        $tipoExame = TipoExame::where('id', $data['PrecoMedicoTipoExame'])->first();
        return rtrim($medico->nome) . '-' . rtrim($tipoExame->nome);
    }

    public function setPrecoMedico(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));

        $data = $request['body'];

        $data = $this->doValidatePrecoMedico($data);

        $model = isset($data['PrecoMedicoId'])
                    ? PrecoMedico::where('id', $data['PrecoMedicoId'])->first()
                    : new PrecoMedico();

        $model->nome = isset($data['PrecoMedicoNome']) ? $data['PrecoMedicoNome'] : $this->getPrecoMedicoNomeRegra($data);
        $model->empresa_id = $empresa->id;
        $model->medico_id = $data['PrecoMedicoMedico'];
        $model->tipo_exame_id = $data['PrecoMedicoTipoExame'];
        $model->vigencia_de = $this->toDateTime($data['PrecoMedicoVigenciaDe']);
        $model->vigencia_ate = $this->toDateTime($data['PrecoMedicoVigenciaAte']);
        $model->preco = $data['PrecoMedicoPreco'];
        $model->save();
        return ['id' => $model->id];
    }

    public function getPrecoMedico(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $model = PrecoMedico::where('id', $request['body']['id'])->first();
        if (!$model) throw new FinanceiroException("Regra não encontrada!");
        return $model;
    }

    public function getPrecoMedicos(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        return PrecoMedico::where('empresa_id', $empresa->id)->get();
    }

}
