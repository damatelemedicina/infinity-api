<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Http;

use App\Exceptions\RegistroNaoEncontradoException;

use App\Services\Entity\EntityManager;

use App\Models\MedicoSolicitante;

class MedicoSolicitanteController extends Controller
{
    protected $entity;

    public function __construct(EntityManager $entity)
    {
        $this->entity = $entity;
    }

    function getMedicoViaCREMESP($data) {

        Log::Debug(openssl_get_cert_locations());

        $crm = substr($data, 0, strlen($data) - 2);
        $uf = substr($data, -2);
        $url = 'https://www.consultacrm.com.br/api/index.php?tipo=crm&uf=' . $uf . '&q=' . $crm . '&chave=6222465964&destino=json';
        $response = Http::post($url);
        if ($response->ok() && $response['total'] == 1) {
            $item = $response['item'][0];
            return array(
                'MedicoSolicitanteNome' => $item['nome'],
                'MedicoSolicitanteCRM' => $item['numero'] . $item['uf'],
                //'profissao' => $item['profissao']
            );
        }
        return null;
    }

    function getMedicos(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        return MedicoSolicitante::where('empresa_id', $this->getMatriz($request))->get();
    }

    function getMedico(Request $request) {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $medico = null;

        if (isset($request['body']['crm'])) {

            $medico = MedicoSolicitante::where([
                ['crm', '=', strtoupper($request['body']['crm']) ]
            ])->get();

            if (count($medico) == 0) {
                $data = $this->getMedicoViaCREMESP(strtoupper($request['body']['crm']));
                if (!$data) throw new RegistroNaoEncontradoException();
                $medico = new MedicoSolicitante();
                $medico->empresa_id = $this->getMatriz($request);
                $medico->crm = $data['MedicoSolicitanteCRM'];
                $medico->nome = $data['MedicoSolicitanteNome'];
                $medico->save();
                $data['MedicoSolicitanteId'] = $medico->id;
                return $data;
            }

        }

        if (isset($request['body']['id'])) {
            $medico = MedicoSolicitante::where([
                ['id', '=', strtoupper($request['body']['id']) ]
            ])->get();
        }

        return $this->entity->normalize('MedicoSolicitante', $medico)[0];

    }

}
