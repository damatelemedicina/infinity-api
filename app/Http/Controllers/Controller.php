<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Exceptions\SessaoNaoEncontradaException;
use App\Exceptions\SessaoMalFormadaException;
use App\Exceptions\RequisicaoMalFormadaException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\EmpresaBloqueadaException;
use App\Exceptions\EmpresaInativaException;
use App\Exceptions\FichaNaoEncontradaException;
use App\Exceptions\UsuarioNaoEncontradoException;
use App\Exceptions\ClienteNaoEncontradoException;
use App\Exceptions\ExameNaoEncontradoException;
use App\Exceptions\BloqueioException;

use App\Exceptions\AutenticacaoRequeridaException;
use App\Exceptions\LoginInvalidoException;
use App\Exceptions\LoginBloqueadoException;

use App\Exceptions\UsuarioException;
use App\Exceptions\ClienteException;

use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Usuario;
use App\Models\TipoExame;
use App\Models\TipoExameCampo;
use App\Models\FichaProcedimento;
use App\Models\MotivoExame;
use App\Models\Cliente;
use App\Models\Exame;
use App\Models\Operacao;
use App\Models\Medico;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static $REGRA_TIPO_RESERVA = 'RESERVA';
    public static $REGRA_TIPO_EXCLUSIVIDADE = 'EXCLUSIVIDADE';
    public static $REGRA_TIPO_EXCLUSAO = 'EXCLUSAO';

    public static $BODY_REQUIRED = true;
    public static $SESSION_NOT_REQUIRED = false;
    public static $ENTITY_WITH_CHILDS = true;
    private static $MOTIVO_EXAME_PADRAO = 1;

    protected static $MEDICO_NAO_DEFINIDO = 0;

    protected static $FILA_AGUARDANDO = 0;
    protected static $FILA_DESPACHADO = 1;

    protected static $AGUARDANDO_LAUDO = 0;

    protected static $PATH_ASSETS = '/uploads/assets/';

    private static $REQUEST;


    protected function getMatriz(Request $request) {
        $login = $this->getEmpresaDoDominio($request);
        $empresa = Empresa::where('login', $login)->first();
        return $empresa->matriz;
    }

    protected function storePath($path) {
        return storage_path() . $this->getPath($path);
    }

    protected function makeDir($path) {
        if (!is_dir($path)){
            mkdir($path, 0777);
        }
    }

    protected function getEmpresaByLogin($login) {
        $empresa = Empresa::where('login', $login)->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        return $empresa;
    }

    protected function registerSession($request, $key, $value) {
        Self::$REQUEST = $request;
        session($key, $value);
    }

    protected function getSession($key = null) {
        if (Self::$REQUEST == null) return;
        if ($key == null) {
            return Self::$REQUEST->session()->all();
        }
        return Self::$REQUEST->session()->get($key, null);
    }

    protected function getUniqId($lenght = 64) {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }

    protected function getClienteByUsuario(Request $request) {
        $usuario = $this->getUsuarioLogado($request);
        if (!$usuario) throw new UsuarioNaoEncontradoException();
        return $usuario->cliente();
    }

    protected function getMedicoByUsuario(Request $request) {
        $usuario = $this->getUsuarioLogado($request);
        if (!$usuario) throw new UsuarioNaoEncontradoException();
        return $usuario->medico();
    }

    protected function cleanSession() {
        if (Self::$REQUEST == null) return;
        Self::$REQUEST->session()->flush();
    }

    protected function isNullOrEmptyValue($value) {
        return is_null($value) || $value == 'null' || $value == 'NULL' || $value == 'nulo' || $value == 'NULO' || empty($value);
    }

    protected function getMotivoExamePadrao($request) {
        $motivo = MotivoExame::where([
            ['empresa_id', '=', $this->getMatriz($request)],
            ['padrao', '=', Self::$MOTIVO_EXAME_PADRAO],
            ['atendimento', '=', $request->atendimento]
        ])->first();
        return $motivo == null ? null : $motivo;
    }

    protected function getFichaDaEmpresa($request) {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $ficha = Ficha::where([
                ['empresa_id', '=', $empresa->id],
                ['numero', '=', strtoupper($request['body']['id'])],
        ])->first();

        return $ficha;

    }

    protected function parseRequest(Request $request) {
        if (!isset($request['session'])) throw new AutenticacaoRequeridaException();
        $request['session'] = json_decode($request['session'], true);
        return $request;
    }

    protected function getUsuariosDoCliente(Request $request) {
        $usuario = Usuario::where('login', $request['session']['login'])->first();
        if (!$usuario) throw new LoginInvalidoException();
        if ($usuario->situacao == Usuario::$BLOQUEADO) throw new LoginBloqueadoException();
        if ($usuario->restringir_exames) return [ $usuario ];
        return Usuario::where('conta_cliente', $usuario->conta_cliente)->get();
    }

    protected function getUsuarioLogado(Request $request)
    {
        $usuario = Usuario::where('login', $request['session']['login'])->first();
        if (!$usuario) throw new LoginInvalidoException();
        if ($usuario->situacao == Usuario::$BLOQUEADO) throw new LoginBloqueadoException();
        return $usuario;
    }

    protected function isLoginCliente(Request $request) {
        $usuario = $this->getUsuarioLogado($request);
        if (!$usuario) throw new LoginInvalidoException();
        return $usuario->conta_cliente != null;
    }

    protected function onlyDigits($data) {
        return preg_replace('/[^0-9]/', '', $data);
    }

    protected function isLoginMedico(Request $request) {
        $usuario = $this->getUsuarioLogado($request);
        return $usuario->conta_medico != null;
    }

    protected function getEmpresaAtual($request) {

        if (!isset($request['origin'])) throw new RequisicaoMalFormadaException();

        $login = 'DAMA';

        if (\App::environment(['prod'])) {

            preg_match(
                '/http[s]?:\/\/([a-z]*).*/',
                $request['origin'],
                $matches
            );

            if (count($matches) < 2) {
                throw new EmpresaNaoEncontradaException();
            }

            $login = strtoupper($matches[1]);

        }

        $empresa = Empresa::where('login', $login)->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        if ($empresa->situacao == Empresa::$BLOQUEADA) throw new EmpresaBloqueadaException();
        if ($empresa->situacao == Empresa::$INATIVA) throw new EmpresaInativaException();

        return $empresa;
    }

    protected function calculate_years_old($birthday, $currentday = null){
        if (!$birthday || $birthday == "0000-00-00") return "";
        if (!$currentday) $currentday = date("Y-m-d");
        $birth_day = new \DateTime($birthday);
        $current_day = new \DateTime($currentday);
        $dif = $current_day->diff($birth_day);
        if ($dif->y > 0) if ($dif->y == 1) return '1 ano'; else return $dif->y . ' anos';
        if ($dif->m > 0) if ($dif->m == 1) return '1 mes'; else return $dif->m . ' meses';
        if ($dif->d == 1) return '1 dia';
        return $dif->d . ' dias';
    }

    protected function getCamposDoProcedimento($fichaId, $campos) {
        $result = [];
        foreach($campos as $campo) {
            $result[] = array(
                'id' => $campo->id,
                'nome' => $campo->nome,
                'obrigatorio' =>  $campo->obrigatorio == TipoExameCampo::$OBRIGATORIO,
                'opcoes' => $campo->opcoes,
                'ordem' => $campo->tipo_exame_id . '' . $campo->ordem,
                'tamanho' => $campo->tamanho,
                'tipo' => $campo->tipo,
                'valor' => $campo->valor
            );
        }
        usort($result, fn($a, $b) => $a['ordem'] - $b['ordem']);
        return array(
            'FichaId' => $fichaId,
            'FichaCampos' => $result
        );
    }

    protected function parseReverse($field, $fields) {
        $result = [];
        $data = explode(';', $field);
        for ($i=0; $i < count($fields); $i++) {
            $key = $fields[$i];
            $result[$key] = $data[$i];
        }
        return $result;
    }

    protected function parseField($request, $fields) {
        $result = '';
        foreach($fields as $field) {
            $value = (isset($request[$field]) ? $request[$field] : '');
            if ($value == 'true' || $value == 'false') {
                $value = filter_var( $value, FILTER_VALIDATE_BOOLEAN) ? 'S' : 'N';
            }
            $result .= $value . ';';
        }
        return $result;
    }

    private function toData($data = null) {
        $data = $this->isNullOrEmptyValue($data) ? date('d/m/YY') : $data;
        $ano = substr($data, 6, 4);
        $mes = substr($data, 3, 2);
        $dia = substr($data, 0, 2);
        return $ano . '-' . $mes . '-' . $dia;
    }

    protected function toDataIntervalo($interval = 30) {
        $inicial = date('d/m/YY', strtotime("-7 days"));
        $final = date('d/m/YY', strtotime("+{$interval} days"));
        return array(
            'inicial' => $this->toDataInicial($inicial),
            'final' => $this->toDataFinal($final)
        );
    }

    protected function toDataFinal($data) {
        return $this->toData($data) . ' 23:59:59';
    }

    protected function toDataInicial($data) {
        return $this->toData($data) . ' 00:00:00';
    }

    protected function toDateTime($date, $today = false) {
        if ($date == null && $today) return date("Y-m-d H:i:s");
        if ($date == null || \DateTime::createFromFormat("d/m/Y", $date) === false) return null;
        $aa = substr($date, 6, 4);
        $mm = substr($date, 3, 2);
        $dd = substr($date, 0, 2);
        $hh = strlen($date) > 10 ? $substr($date, 10) : '00:00:00';
        return $aa.'-'.$mm.'-'.$dd.' '.trim($hh);
    }

    protected function getPath($path) {
        $path = str_replace('//', '/', $path);
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
        return $path;
    }

    protected function getCamposDaFicha($request) {

        $ficha = $this->getFichaDaEmpresa($request);
        if (!$ficha) throw new FichaNaoEncontradaException();

        $campos = FichaProcedimento::where('ficha_id', $ficha->id)->get();

        if (count($campos)>0) {
            return $this->getCamposDoProcedimento($ficha->id, $campos);
        }

        $tipoExames = explode(';', $ficha['procedimentos']);

        $result = [];

        foreach($tipoExames as $id) {
            if (!$id) continue;
            $tipoExame = TipoExame::where('id', $id)->first();
            if (!$tipoExame) continue;
            $campos = $tipoExame->campos();
            if (!$campos) continue;
            foreach($campos as $campo) {
                if ($result) {
                    $found = false;
                    foreach($result as &$item) {
                        if ($item['nome'] == $campo->nome) {
                            $item['obrigatorio'] =
                                $item['obrigatorio'] == TipoExameCampo::$OBRIGATORIO ||
                                $campo->obrigatorio == TipoExameCampo::$OBRIGATORIO;
                            $item['opcoes'] =
                                empty($item['opcoes']) ?
                                $campo->opcoes :
                                $item['opcoes'];
                            $found = true;
                            break;
                        }
                    }
                    if ($found) continue;
                }

                $result[] = array(
                    'id' => $campo->id,
                    'nome' => $campo->nome,
                    'obrigatorio' =>  $campo->obrigatorio == TipoExameCampo::$OBRIGATORIO,
                    'opcoes' => $campo->opcoes,
                    'ordem' => $campo->tipo_exame_id . '' . $campo->ordem,
                    'tamanho' => $campo->tamanho,
                    'tipo' => $campo->tipo
                );

            }

        }

        usort($result, fn($a, $b) => $a['ordem'] - $b['ordem']);

        return array(
            'FichaId' => $ficha->id,
            'FichaCampos' => $result
        );

    }

    protected function preencheValores($campos, $valores) {
        foreach ($valores as $nome => $valor) {
            foreach($campos as $key => &$value){
                if ($value['nome'] == $nome) {
                    $value['valor'] = $valor;
                }
            }
        }
        return $campos;
    }

    protected function getEmpresaDoDominio($request)
    {
        if (!isset($request['origin'])) throw new RequisicaoMalFormadaException();
        preg_match(
            '/http[s]?:\/\/([a-z]*).*/',
            $request['origin'],
            $matches
        );
        if (count($matches) < 2) {
            throw new EmpresaNaoEncontradaException();
        }
        $login = strtoupper($matches[1]);
        $empresa = Empresa::where('login', $login)->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        if ($empresa->situacao == Empresa::$BLOQUEADA) throw new EmpresaBloqueadaException();
        if ($empresa->situacao == Empresa::$INATIVA) throw new EmpresaInativaException();

        return $login;
    }

    protected function getQuery(Request $request, $id) {
        return array(
            'body' => array(
                'id' => $id
            ),
            'origin' => $request['origin'],
            'session' => array(
                'login' => $request['session']['login']
            )
        );
    }

    private function validaBloqueioMedico($usuario) {
        if ($usuario->conta_medico == 0) return;
        $medico = Medico::where('id', $usuario->conta_medico)->first();
        if (!$medico) throw new BloqueioException('Médico não encontrado!');
        if ($medico->situacao == Medico::$BLOQUEADO) throw new BloqueioException('Médico bloqueado!');
        if ($medico->inativo == Medico::$INATIVO) throw new BloqueioException('Médico inativo!');
    }

    private function validaBloqueioCliente($usuario) {
        if ($usuario->conta_cliente == 0) return;
        $cliente = Cliente::where('id', $usuario->conta_cliente)->first();
        if (!$cliente) throw new BloqueioException('Cliente não encontrado!');
        if ($cliente->situacao == Cliente::$BLOQUEADO) throw new BloqueioException('Cliente bloqueado X!');
        if ($cliente->inativo == Cliente::$INATIVO) throw new BloqueioException('Cliente inativo X!');
    }

    protected function validaBloqueios($request, $sessionRequired) {
        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new BloqueioException('Empresa não encontrada!');
        if ($empresa->situacao == Empresa::$BLOQUEADA) throw new BloqueioException('Empresa bloqueada!');
        if ($empresa->situacao == Empresa::$INATIVA) throw new BloqueioException('Empresa inativa!');
        if ($sessionRequired) {
            $usuario = Usuario::where('login', $request['session']['login'])->first();
            if (!$usuario) throw new BloqueioException('Usuário não encontrado!');
            if ($usuario->situacao == Usuario::$BLOQUEADO) throw new BloqueioException('Login bloqueado!');
            if ($usuario->inativo == Usuario::$INATIVO) throw new BloqueioException('Login inativo!');
            $this->validaBloqueioCliente($usuario);
            $this->validaBloqueioMedico($usuario);
        }
    }

    protected function validarRequisicao($request, $bodyRequired = false, $sessionRequired = true)
    {
        if (!isset($request['origin'])) throw new RequisicaoMalFormadaException();
        if ($sessionRequired && !isset($request['session'])) throw new AutenticacaoRequeridaException();
        $request['session'] = is_string($request['session']) ? json_decode($request['session'], true) : $request['session'];
        if ($sessionRequired && !isset($request['session']['login'])) throw new AutenticacaoRequeridaException();
        $this->validaBloqueios($request, $sessionRequired);
        if ($bodyRequired && !isset($request['body'])) throw new RequisicaoMalFormadaException();
    }

    protected function getUltimaHoraDoDiaParaLaudar() {
        return $this->toData() . ' 23:59:58';
    }

    protected function checkIfNull($value, $default = null) {
        return empty($value) ? $default : $value;
    }

    protected function liberarExames($medicoId, $liberar = false) {
        if (!$liberar) return;

        $exames = Exame::where([
            'medico_id' => $medicoId,
            'status' => Self::$AGUARDANDO_LAUDO
        ])->get();

        foreach($exames as $exame) {
            $exame->medico_id = 0;
            $exame->pausado = null;
            $exame->despacho_date = null;
            $exame->despacho_prazo = null;
            $exame->save();
        }

    }

    protected function getUsuarioSistema() {
        $usuario = Usuario::where('sistema', '1')->first();
        if (!$usuario) throw new UsuarioException('Nenhum usuário do sistema registrado!');
        return $usuario;
    }

    protected function getClienteSistema() {
        $cliente = Cliente::where('sistema', '1')->first();
        if (!$cliente) throw new ClienteException('Nenhum cliente do sistema registrado!');
        return $cliente;
    }

    protected function registraAlerta($descricao) {
        $usuario = $this->getUsuarioSistema();
        $cliente = $this->getClienteSistema();
        $empresa = Empresa::whereRaw("id = matriz")->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $operacao = new Operacao();
        $operacao->empresa_id = $empresa->id;
        $operacao->usuario_id = $usuario->id;
        $operacao->cliente_id = $cliente->id;
        $operacao->operacao = $descricao;
        $operacao->save();
    }

    protected function registraOperacao(Request $request, $exame, $descricao) {
        $usuario = $this->getUsuarioLogado($request);
        if (!$usuario) throw new UsuarioNaoEncontradoException();
        if (!$exame) throw new ExameNaoEncontradoException();
        $operacao = new Operacao();
        $operacao->exame_id = $exame->id;
        $operacao->empresa_id = $exame->empresa_id;
        $operacao->cliente_id = $exame->cliente_id;
        $operacao->usuario_id = $usuario->id;
        $operacao->medico_id = $exame->medico_id;
        $operacao->operacao = $descricao;
        $operacao->save();
    }

    protected function isValidDate($date, $format = 'd/m/Y') {
        try {
            $dateTime = \DateTime::createFromFormat($format, $date);
            return $dateTime && $dateTime->format($format) === $date;
        } catch (\Throwable $th) {
            // throw $th;
        }
        return false;
    }
}
