<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\FichaNaoEncontradaException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\ChaveDeTransmissaoNaoEncontradaException;
use App\Exceptions\MotivoDeExamePadraoNaoEncontradoException;
use App\Exceptions\UsuarioNaoEncontradoException;
use App\Exceptions\ExameNaoEncontradoException;
use App\Exceptions\UsuarioNaoAssociadoAClienteException;
use App\Exceptions\InclusaoDeExameException;
use App\Exceptions\ClienteNaoEncontradoException;
use App\Exceptions\MedicoNaoEncontradoException;
use App\Exceptions\MedicoNaoAssociadoAClienteException;
use App\Exceptions\TipoExameNaoEncontradoException;
use App\Exceptions\InclusaoDeLaudoException;
use App\Exceptions\ExameNaoPodeSerPausadoException;
use App\Exceptions\RecusaNaoPermitidaException;
use App\Exceptions\LaudoException;

use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Cliente;
use App\Models\Exame;
use App\Models\Paciente;
use App\Models\MotivoExame;
use App\Models\TipoExame;
use App\Models\Usuario;
use App\Models\MedicoSolicitante;
use App\Models\Medico;
use App\Models\Impossibilidade;
use App\Models\DespachoFila;
use App\Models\DespachoRecusa;
use App\Models\MedicoExame;
use App\Models\DespachoRegra;

use App\Utils\Dicom;
use App\Utils\PdfToText;
use App\Utils\Utils;

use Smalot\PdfParser\Parser;

use App\Builders\WinspiroProBuilder;
use App\Builders\CardioBrasilBuilder;

use Illuminate\Support\Facades\Log;

use App\ViewModels\ExameViewModel;

use Carbon\Carbon;

// https://blog.filestack.com/tutorials/step-step-guide-laravel-file-upload/

class ExameController extends Controller
{

    private static $TARGET = '/^.*\.(wxml|xml|datest|dte|dat|plg|tep|eeg|pdf|txt|dcm|oit)$/i';
    private static $HIGH_PRIORITY = '/^.*\.(xml|datest|dte)$/i';

    private static $EXAME_LAUDADO = 1;
    private static $LAUDO_IMPOSSIBILITADO = 2;

    private static $ID_EMPRESA_DO_DOMINIO = 0;
    private static $ID_EMPRESA_MATRIZ = 0;
    private static $LOGIN_EMPRESA_DO_DOMINIO;
    private static $ID_MOTIVO_EXAME_PADRAO;
    private static $NAO_INFORMADO = 0;

    private static $TIPO_ENVIO = 'MANUAL';

    private static $MARCADO = 1;
    private static $ATENDIMENTO_PADRAO = 'OCUPACIONAL';
    private static $LAUDO_NORMAL = 0;
    private static $LAUDO_RAPIDO = 1;
    private static $LAUDO_EMERGENCIA = 2;

    private static $EXAME_ATIVO = 1;
    private static $LAUDO_PAUSADO = 1;

    private static $MAPEAMENTO_CEREBRAL = 8;
    private static $HOLTER = 10;

    private static $PATH_EXAMES = '/uploads/exames/';
    private static $PATH_LAUDOS = '/uploads/laudos/';
    private static $PATH_LOTES = '/uploads/exames/lotes/';

    private static $PACIENTE_NAO_INFORMADO = 'Paciente não informado!';
    private static $DATA_DE_NASCIMENTO_NAO_INFORMADO = 'Data de nascimento não informada!';
    private static $DATA_DO_EXAME_NAO_INFORMADO = 'Data do exame não informado!';
    private static $ANEXOS_NAO_INFORMADOS = 'Arquivos anexados não informados!';
    private static $TIPO_DE_ATENDIMENTO_NAO_INFORMADO = 'Tipo de atendimento não informados!';
    private static $MOTIVO_DO_EXAME_NAO_INFORMADO = 'Motivo do exame não informados!';
    private static $EXAME_DESPACHADO_PARA_OUTRO_MEDICO = 'Exame despachado para outro médico!';
    private static $USUARIO_NAO_VINCULADO_A_MEDICO = 'Usuário não vinculado a um médico!';

    private static $DEFAULT_LAUDO_VIEW = 'laudo';
    private static $OIT_LAUDO_VIEW = 'laudo-oit';

    private static $RAIOX_OIT_ID = 9;
    private static $ACUIDADE_VISUAL = 6;

    private static $USUARIO_LOGADO = null;

    function __construct() {
        // $exame = (Object)array(
        //     'exame_id' => 8,
        //     'arquivo_laudo' => '/uploads/laudos/3muBcPCo9p7agKQu4Spwx4iwSpraOhJBvT0afvpk.pdf',
        //     'laudo_anexo' => '/uploads/laudos/Hlg3zLDr56Ha5R2qwXLW4uDfm4ftLHvnTpDc5xN0.pdf',
        //     'cpf' => '11776329856',
        //     'id' => 1969,
        //     'paciente' => 'Carlos L. Santos',
        // );
        // $exame->arquivo_laudo = $this->compoeLaudoComAnexo($exame);
        // \Log::Debug('>>>> arquivo_laudo:' . $exame->arquivo_laudo);
        // $arquivo_laudo = Utils::getNomeLaudoParaDownload($exame);
        // \Log::Debug('>>>> para download:' . $arquivo_laudo);
        // \Log::Debug(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
        // Self::SHORTPNG("/uploads/exames/zt2xojpkwfx7ytksh64osk4vymx8qkmp5sphpdiu.png");
        // \Log::Debug(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
    }

    function retirada(Request $request) {
        $exame = Exame::where('protocolo', $request->id)->first();
        if (!$exame) {
            return response()
                ->json(['erro' => 'Protocolo não encontrado!'])
                ->setStatusCode(200);
        }
        if ($exame->status == Exame::$CANCELADO) {
            return response()
                ->json(['erro' => 'Laudo cancelado pelo médico!'])
                ->setStatusCode(200);
        }
        $path = storage_path($exame->arquivo_laudo);
        if (!\File::exists($path)) {
            return response()
                ->json(['erro' => 'Laudo não encontrado!'])
                ->setStatusCode(200);
        }
        if ($request->download) {
            return response()->download($path);
        }
        $domain = env('DOMAIN') ? env('DOMAIN') : \URL::to('/');
        return $domain . '/retirada?id=' . $request->id . '&download=true';
    }

    function getCampos(Request $request) {
        return $this->getCamposDaFicha($request);
    }

    private function getQueryParam($request, $param) {
        return str_replace(' ', '+', $request->get($param));
    }

    private function registerProperties($key, $tipoEnvio) {
        $cliente = Cliente::where('chave_transmissao', $key)->first();
        if (!$cliente) throw new ChaveDeTransmissaoNaoEncontradaException();
        $empresa = Empresa::where('id', $cliente->empresa_id)->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $motivo = MotivoExame::where([
            ['empresa_id', '=',  $empresa->matriz],
            ['padrao', '=', Self::$MARCADO],
        ])->first();
        if (!$motivo) throw new MotivoDeExamePadraoNaoEncontradoException();
        Self::$LOGIN_EMPRESA_DO_DOMINIO = $empresa->login;
        Self::$ID_EMPRESA_DO_DOMINIO = $empresa->id;
        Self::$ID_EMPRESA_MATRIZ = $empresa->matriz;
        Self::$ID_MOTIVO_EXAME_PADRAO = $motivo->id;
        Self::$TIPO_ENVIO = $tipoEnvio;
    }

    function getExames(Request $request) {
        $this->validarRequisicao($request);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $usuario = Usuario::where('login', $request['session']['login'])->first();
        $ativos = isset($request['body']) && isset($request['body']['inativos']) ? 0 : 1;
        if ($usuario == null) throw new UsuarioNaoEncontradoException();
        if ($usuario->conta_cliente > 0) {
            return ExameViewModel::getExamesDoCliente($usuario->conta_cliente, $ativos);
        }
        if ($usuario->conta_medico > 0) {
            return ExameViewModel::getExamesDoMedico($usuario->conta_medico, $ativos);
        }
        return ExameViewModel::getExamesDaEmpresa($empresa->id, $ativos);
    }

    function getExameParaLaudar(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $id = $request['body']['id'];
        $medicoId = $request['body']['medicoId'];
        $exame = ExameViewModel::getExameParaLaudar($id);
        if (count($exame) == 0) throw new ExameNaoEncontradoException();
        $exame = $exame[0];
        if ($exame['laudar_medico_id'] != $medicoId) throw new InclusaoDeLaudoException(Self::$EXAME_DESPACHADO_PARA_OUTRO_MEDICO);
        return $exame;
    }

    function getExame(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $exame = Exame::where('id', $request['body']['id'])->first();
        if (!$exame) throw new ExameNaoEncontradoException();
        $cliente = Cliente::where('id', $exame->cliente_id)->first();
        if (!$cliente) throw new ClienteNaoEncontradoException();
        $tipoExame = TipoExame::where(['id' => $exame->exame_id])->first();
        $exame->tipo_exame = $tipoExame->nome;
        $exame->exame_date = date_format(new \DateTime($exame->exame_date),"d/m/Y");
        $exame->numero = $exame->id;
        $exame->mensagem_medicos = $cliente->mensagem_medicos;
        $exame->imc = $this->getIMC($exame->peso, $exame->altura);
        return $exame;
    }

    private function oneZip(&$zip, $path, $name, $rawName, $extension) {
        $rawName = $rawName.'.'.$extension;
        $fullName = $path.$rawName;

        if (!str_contains('zip', $extension)) {
            $file = $this->storePath($fullName);
            $zip->addFile($file, $this->stripAccents($name));
            return [ $file ];
        }

        $zip2 = new \ZipArchive();

        if ($zip2->open($this->storePath($fullName)) == TRUE) {

            $files = array($this->storePath($fullName));

            for ($i = 0; $i < $zip2->numFiles; $i++){

                $originalName = $this->stripAccents(strtolower($zip2->getNameIndex($i)));

                if (str_contains('zip', $originalName)) {
                    continue; // zip inside zip is not allowed
                }

                $tempName = $rawName.'_'.$originalName;

                $zip2->renameIndex($i, $tempName);

                $zip2->extractTo(
                    $this->storePath($path),
                    $tempName
                );

                $file = $this->storePath($path.$tempName);

                $zip->addFile(
                    $file,
                    $originalName
                );

                array_push($files, $file);

            }

            $zip2->close();

        }

        return $files;

    }

    private function doUploadSingleFile($file) {
        $rawName = \Str::random(40);
        $extension = strtolower($file->getClientOriginalExtension());
        $fullName = $rawName.'.'.$extension;
        $isImage = str_contains($file->getMimeType(), 'image');
        $isPdf = str_contains($file->getMimeType(), 'pdf');
        $isZip = $extension == 'zip';
        $isDCM = $extension == 'dcm';

        $file->move(
            $this->storePath(Self::$PATH_EXAMES),
            $fullName
        );
        $fullName = Self::$PATH_EXAMES.$fullName;
        $imagemIndex = 0;
        if ($isZip) {
            return array(
                'arquivo_exame' => $fullName,
                'arquivo_imagem' => $this->extraiImagensDoZip(
                    $imagemIndex,
                    $rawName,
                    Self::$PATH_EXAMES,
                    $extension
                )
            );
        }
        if ($isImage) {
            return array(
                'arquivo_exame' => $fullName,
                'arquivo_imagem' => $fullName
            );
        }
        if ($isPdf) {
            return array(
                'arquivo_exame' => $fullName,
                'arquivo_imagem' => $this->getImageFromFile($fullName),
                'arquivo_pdf' => $fullName
            );
        }
        if ($isDCM) {
            return array(
                'arquivo_exame' => $fullName,
                'arquivo_imagem' => $this->getImageFromDCM($fullName),
            );
        }

        return array(
            'arquivo_exame' => $fullName,
            'arquivo_imagem' => null
        );
    }

    private function doFormUpload(Request $request, $filesToUpload = null, $path = null) {

        $path = $path == null ? Self::$PATH_EXAMES : $path;

        $result = array(
            'arquivo_exame' => null,
            'arquivo_imagem' => null,
            'arquivo_pdf' => null
        );

        $filesToUpload = $filesToUpload == null ? $request->file('files') : $filesToUpload;

        if (!$filesToUpload) {
            return $result;
        }

        if (count($filesToUpload) == 1) {
            return $this->doUploadSingleFile($filesToUpload[0]);
        }

        $this->makeDir($this->storePath($path));

        $zipRawName = \Str::random(40);
        $zipName = $path . $zipRawName . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($this->storePath($zipName), \ZipArchive::CREATE) === true) {

            $imagemIndex = 0;
            $fullNames = array();
            $files = array();
            foreach ($filesToUpload as $file) {

                $rawName = \Str::random(40);
                $extension = strtolower($file->getClientOriginalExtension());
                $fullName = $rawName.'.'.$extension;

                array_push($fullNames, array('rawName' => $rawName, 'extension' => $extension));

                $file->move(
                    $this->storePath($path),
                    $fullName
                );

                if ($extension == 'pdf') $result['arquivo_pdf'] = $path.$fullName;

                $result['arquivo_imagem'] = $this->extraiImagensDoZip(
                    $imagemIndex,
                    $rawName,
                    $path,
                    $extension
                );

                $files = array_merge(
                    $files,
                    $this->oneZip(
                        $zip,
                        $path,
                        $file->getClientOriginalName(),
                        $rawName,
                        $extension
                    )
                );

            }

            $zip->close();

        }

        $imagemIndex = 0;
        foreach ($fullNames as $fullName) {
            $result['arquivo_imagem'] = $this->agrupaImagens(
                $result['arquivo_imagem'],
                $imagemIndex,
                $path,
                $fullName,
                $zipRawName
            );
        }


        $result['arquivo_exame'] = $zipName;

        return $result;

    }

    private function agrupaImagens($arquivoImagens, &$imagemIndex, $path, $fullName, $zipRawName) {
        $isDCM = $fullName['extension'] == 'dcm';
        if (!$this->isImageFile($fullName['extension']) && !$isDCM) return $arquivoImagens;
        $oldName = $this->storePath($path) . $fullName['rawName'] . '.' . $fullName['extension'];
        $newName = $this->storePath($path) . $zipRawName . '_' . $imagemIndex . '.' . $fullName['extension'];
        rename($oldName, $newName);
        if ($isDCM) {
            $dcmName = $zipRawName . '_' . $imagemIndex . '.dcm';
            $this->DCM2JPG($path.$dcmName);
            $dcmName = $this->storePath($path) . $dcmName;
            $fullName['extension'] = 'jpg';
        }
        $imagemIndex++;
        return $path . $zipRawName . '{{' . $imagemIndex . '}}.' . $fullName['extension'];
    }

    private function createDir($path) {
        if (!is_dir($this->storePath($path))){
            mkdir($this->storePath($path), 0777);
        }
    }

    private function extraiImagensDoZip(&$index, $rawName, $path, $extension) {
        if ($extension != 'zip') return null;
        $zip = new \ZipArchive();
        $haImagem = false;
        if ($zip->open($this->storePath($path) . $rawName . '.zip') === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $isDCM = $this->isDCMFile($name);
                if (!$this->isImageFile($name) && !$isDCM) continue;
                $haImagem = true;
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $newName = $rawName . '_' . $index . '.' . $ext;
                $path_images = $path . 'imagens/';
                $this->createDir($path_images);
                $zip->renameName($name, $newName);
                $zip->extractTo(
                    $this->storePath($path_images),
                    $newName
                );

                if ($isDCM) {
                    $this->DCM2JPG($path_images . $newName);
                    $ext = 'jpg';
                }

                $zip->renameName($newName, $name);
                $index++;
            }
        }
        return $haImagem ? ($path_images . $rawName . '{{' . $index  . '}}.' . $ext) : null;
    }

    private function setMedicoSolicitante($request) {
        $nome = $request->medico_solicitante;
        $crm = $request->crm_solicitante;
        if (!isset($nome) || !isset($crm)) return;
        $medico = MedicoSolicitante::where([
            ['crm', '=', strtoupper($crm) ]
        ])->first();
        if ($medico) return;
        $medico = new MedicoSolicitante();
        $medico->empresa_id = $this->getMatriz($request);
        $medico->nome = $nome;
        $medico->crm = $crm;
        $medico->save();
    }

    private function doValidate($exame) {
        if (empty($exame->cliente_id)) throw new ClienteNaoEncontradoException();
        if (empty($exame->atendimento)) throw new InclusaoDeExameException(Self::$TIPO_DE_ATENDIMENTO_NAO_INFORMADO);
        if (empty($exame->motivo_id)) throw new InclusaoDeExameException(Self::$MOTIVO_DO_EXAME_NAO_INFORMADO);
        if (empty($exame->paciente)) throw new InclusaoDeExameException(Self::$PACIENTE_NAO_INFORMADO);
        if (empty($exame->nascimento)) throw new InclusaoDeExameException(Self::$DATA_DE_NASCIMENTO_NAO_INFORMADO);
        if (empty($exame->exame_date)) throw new InclusaoDeExameException(Self::$DATA_DO_EXAME_NAO_INFORMADO);
        $this->verificaSeAnexosOk($exame);
    }

    private function verificaSeAnexosOk($exame) {
        if ($exame->status == Self::$LAUDO_IMPOSSIBILITADO) return;
        if (empty($exame->arquivo_exame) && $exame->exame_id != Self::$ACUIDADE_VISUAL) throw new InclusaoDeExameException(Self::$ANEXOS_NAO_INFORMADOS);
    }

    private function formataNumeros($exame) {
        $exame->peso = !$exame->peso ? '0.00' : str_replace(",", ".", $exame->peso);
        $exame->altura = !$exame->altura ? '0.00' : str_replace(",", ".", $exame->altura);
        return $exame;
    }

    private function doInsertNew(Request $request) {
        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $cliente = Cliente::where('id', $request->cliente_id)->first();
        if (!$cliente) throw new ClienteNaoEncontradoException();
        $usuario = $this->getUsuarioLogado($request);
        $exames = explode(',', $request->exames);
        foreach($exames as $exame_id) {
            $upload = $this->doFormUpload($request, $request['files_'.$exame_id]);
            $this->setMedicoSolicitante($request);
            $exame = new Exame();
            $exame->fill($request->all());
            $exame = $this->formataNumeros($exame);
            $exame->exame_id = $exame_id;
            $exame->empresa_id = $empresa->id;
            $exame->recepcionado = $cliente->id;
            $exame->digitado = $usuario->id;
            $exame = $this->parseFormFields($request, $exame, $upload);
            $this->doValidate($exame);
            $this->cadastraPaciente($exame, $empresa);
            $reenviar = filter_var($request->reenviar, FILTER_VALIDATE_BOOLEAN) ? true : false;
            if (!$reenviar) {
                $exame->crc = crc32($this->calcCRCFields($exame));
                if ($this->isExameInserido($exame->crc)) continue;
            }
            $exame->save();
            $exame->protocolo = $this->getProtocolo($exame->id);
            $exame->save();
        }
        return ['id' => $exame->id];
    }

    private function setMotivoExame($request, $exame) {
        $exame->motivo_id = isset($request->motivo) ? $request->motivo : null;
        $exame->atendimento = isset($request->atendimento) ? $request->atendimento : null;
        if ($this->isNullOrEmptyValue($exame->motivo_id)) {
            $motivo = $this->getMotivoExamePadrao($request);
            if (!$motivo) throw new MotivoDeExamePadraoNaoEncontradoException();
            $exame->motivo_id = $motivo->id;
            $exame->atendimento = $motivo->atendimento;
        }
        return $exame;
    }

    private function parseFormFields($request, $exame, $upload) {
        $exame->numero = $exame->id ? $exame->id : 0;
        $exame->paciente = strtoupper($exame->paciente);
        $exame->contratante = strtoupper($exame->contratante);
        $exame->nascimento = $this->toDateTime(isset($request->nascimento) ? $request->nascimento : null);
        $exame->exame_date = $this->toDateTime(isset($request->exame_date) ? $request->exame_date : null);
        $exame->rg = preg_replace( '/[^0-9]/', '', $request->rg);
        $exame->cpf = preg_replace( '/[^0-9]/', '', $request->cpf);
        $exame->fumante = filter_var($request->fumante, FILTER_VALIDATE_BOOLEAN) ? 'S' : 'N';
        $exame->arquivo_exame = $upload['arquivo_exame'] ? $upload['arquivo_exame'] : $exame->arquivo_exame;
        $exame->arquivo_id = $this->getArquivoIdv2($exame->arquivo_exame);
        $exame->arquivo_imagem = $upload['arquivo_imagem'] ? $upload['arquivo_imagem'] : $exame->arquivo_imagem;
        $exame->imc = $this->getIMC($exame->peso, $exame->altura);
        $exame->emergencia = filter_var($request->rapido, FILTER_VALIDATE_BOOLEAN) ?
            Self::$LAUDO_RAPIDO :
            Self::$LAUDO_NORMAL;
        $exame->emergencia = $this->validaUrgenciaEmergencia($exame);
        $exame = $this->setMotivoExame($request, $exame);
        return $exame;
    }

    private function validaUrgenciaEmergencia($exame) {
        $emergencia = $exame->emergencia;
        $tipoExame = TipoExame::where('id', $exame->exame_id)->first();
        if (!$tipoExame) throw new TipoDeExameNaoEncontradoException();
        $cliente = Cliente::where('id', $exame->cliente_id)->first();
        if (!$cliente) throw new ClienteNaoEncontradoException();
        if ($cliente->laudo_rapido == Self::$MARCADO && $tipoExame->laudo_rapido == Self::$MARCADO) {
            $emergencia = Self::$LAUDO_RAPIDO;
        }
        if ($cliente->emergencia == Self::$MARCADO && $tipoExame->emergencia == Self::$MARCADO) {
            $emergencia = Self::$LAUDO_EMERGENCIA;
        }
        if ($cliente->laudo_rapido !== Self::$MARCADO && $tipoExame->laudo_rapido !== Self::$MARCADO) {
            $emergencia = Self::$LAUDO_NORMAL;
        }
        return $emergencia;
    }

    private function cadastraPaciente($exame, $empresa) {
        if ($this->IsNullOrEmptyString($exame->rg) && $this->IsNullOrEmptyString($exame->cpf)) return;
        $pacienteRG = Paciente::where('rg', $exame->rg)->first();
        $pacienteCPF = Paciente::where('cpf', $exame->cpf)->first();
        $paciente = $pacienteRG ? $pacienteRG : ($pacienteCPF ? $pacienteCPF : new Paciente());
        $paciente->nome = $exame->paciente;
        $paciente->nascimento = $exame->nascimento;
        $paciente->rg = $exame->rg;
        $paciente->cpf = $exame->cpf;
        $paciente->sexo = $exame->sexo;
        $paciente->empresa_id = $empresa->matriz;
        $paciente->save();
    }

    private function clonarExameSeCancelado($usuario, $exame) {
        if ($exame->status != Exame::$CANCELADO) {
            return;
        }
        $novo = new Exame();
        $novo->exame_id = $exame->exame_id;
        $novo->motivo_id = $exame->motivo_id;
        $novo->cliente_id = $exame->cliente_id;
        $novo->empresa_id = $exame->empresa_id;
        $novo->medico_id = $usuario->conta_medico;
        $novo->status = Self::$AGUARDANDO_LAUDO;
        $novo->arquivo_exame = $exame->arquivo_exame;
        $novo->observacoes  = $exame->observacoes;
        $novo->observacoes_medico = $exame->observacoes_medico;
        $novo->arquivos_selecionados = $exame->arquivos_selecionados;
        $novo->paciente = $exame->paciente;
        $novo->rg = $exame->rg;
        $novo->cpf = $exame->cpf;
        $novo->nascimento = $exame->nascimento;
        $novo->sexo = $exame->sexo;
        $novo->funcao = $exame->funcao;
        $novo->contratante = $exame->contratante;
        $novo->ativo = $exame->ativo;
        $novo->emergencia = $exame->emergencia;
        $novo->exame_date = $novo->exame_date;
        $novo->medico_solicitante = $exame->medico_solicitante;
        $novo->crm_solicitante = $exame->crm_solicitante;
        $novo->sub_tipo_exame = $exame->sub_tipo_exame;
        $novo->peso = $exame->peso;
        $novo->altura = $exame->altura;
        $novo->imc = $exame->imc;
        $novo->fumante = $exame->fumante;
        $novo->fumante_tempo = $exame->fumante_tempo;
        $novo->extra_data = $exame->extra_data;
        $novo->crc = $exame->crc . 'X';
        $novo->rnd = $exame->rnd . 'X';
        $novo->protocolo = $exame->protocolo . 'X';
        $novo->arquivo_imagem = $exame->arquivo_imagem;
        $novo->arquivo_id = $exame->arquivo_id;
        $novo->reenviar_exame = $exame->reenviar_exame;
        $novo->empresa = $exame->empresa;
        $novo->enviado_por = $exame->enviado_por;
        $novo->imagem_date = $exame->imagem_date;
        $novo->empresa = $exame->empresa;
        $novo->recepcionado = $exame->recepcionado;
        $novo->abonado = $exame->abonado;
        $novo->motivo_abono = $exame->motivo_abono;
        $novo->copiado_de = $exame->id;
        $novo->exame_date = $exame->exame_date;
        $novo->acuidade_longe_od = $exame->acuidade_longe_od;
        $novo->acuidade_longe_oe = $exame->acuidade_longe_oe;
        $novo->acuidade_perto_od = $exame->acuidade_perto_od;
        $novo->acuidade_perto_oe = $exame->acuidade_perto_oe;
        $novo->lente_corretiva = $exame->lente_corretiva;
        $novo->senso_cromatico = $exame->senso_cromatico;
        $novo->visao_noturna = $exame->visao_noturna;
        $novo->visao_ofuscada = $exame->visao_ofuscada;
        $novo->profundidade = $exame->profundidade;
        $novo->save();
    }

    function setReciclaExame(Request $request) {
        $data = $request['body'];
        if (!isset($data['id'])) return "";
        DespachoRecusa::where('exame_id', $data['id'])->delete();
        return array('id' => $data['id']);
    }

    function setStatusExame(Request $request) {
        $data = $request['body'];
        if (!isset($data['id'])) return;
        $exame = Exame::where(['id' => $data['id']])->first();
        if (!$exame) throw new ExameNaoEncontradoException();
        $exame->ativo = isset($data['ativo']) ? !$exame->ativo : $exame->ativo;
        $exame->abonado = isset($data['abonado']) ? !$exame->abonado : $exame->abonado;
        $exame->abonado_medico = isset($data['abonado_medico']) ? !$exame->abonado_medico : $exame->abonado_medico;
        if (isset($data['cancelado'])) {
            $exame->status = Exame::$CANCELADO;
            $exame->laudo_cancelado_date = Carbon::now();
        }
        $exame->save();
        $this->clonarExameSeCancelado($this->getUsuarioLogado($request), $exame);
        return array('id' => $exame->id);
    }

    private function calcCRCFields($exame) {
        // Futuramente alterar para categoria em tipo_exames
        // Espirometria = 3,11,12
        // EEG = 2,7
        // RAIOX_OIT = 9
        // RAIO = 4
        // ECG = 1

        $tipo = $exame->exame_id;
        $subTipo = $exame->sub_tipo_exame;

        if (in_array($exame->exame_id, [3, 11, 12])) $tipo = 'ESPIRO';
        if (in_array($exame->exame_id, [2, 7])) $tipo = 'EEG';
        if (in_array($exame->exame_id, [4])) $tipo = 'RAIO';
        if (in_array($exame->exame_id, [9])) $tipo = 'RAIOX_OIT';
        if (in_array($exame->exame_id, [1])) $tipo = 'ECG';

        $dt_nasc = '';

        if ($tipo != 'ESPIRO') $dt_nasc = str_replace(' ', '', strtoupper(trim($exame->nascimento))) . '#';
		if ($tipo == 'EEG') { $exame->peso = 0; $exame->altura = 0; }

		$sub_tipo = $subTipo ? strtoupper(trim($subTipo)) : "";
		$sub_tipo = strlen($sub_tipo) > 0 ? $sub_tipo . '#' : "";
		$clinica_id = $exame->cliente_id;

        $ret = str_replace(' ', '', strtoupper(trim($tipo))) . '#' .
			   $sub_tipo .
               str_replace(' ', '', strtoupper(trim($exame->motivo_id))) . '#' .
               str_replace(' ', '', strtoupper(trim($exame->paciente))) . '#' .
               $dt_nasc .
               str_replace(' ', '', strtoupper(trim($exame->idade))) .  '#' .
               str_replace(' ', '', strtoupper(trim($exame->sexo))) .  '#' .
               str_replace(' ', '', strtoupper(trim($exame->altura))) .  '#' .
               str_replace(' ', '', strtoupper(trim($exame->peso))) .  '#' .
               str_replace(' ', '', strtoupper(trim($exame->contratante))) . '#' .
			   $clinica_id;

        return $ret;
    }

    private function isExameInserido($crc) {
        $atual = Exame::where(['crc' => $crc, 'ativo' => Self::$EXAME_ATIVO])->first();
        return $atual != null && $atual->status != Self::$LAUDO_IMPOSSIBILITADO;
    }

    function setExame(Request $request) {
        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $this->validarRequisicao($request);
        if (isset($request->exames)) {
            return $this->doInsertNew($request);
        }
        $upload = $this->doFormUpload($request);
        $this->setMedicoSolicitante($request);
        $exame = Exame::where(['id' => $request->id])->first();
        if (!$exame) throw new ExameNaoEncontradoException();
        $laudoDate = $exame->laudo_date;
        $exame->fill($request->all());
        $exame = $this->parseFormFields($request, $exame, $upload);
        $exame->laudo_date = $laudoDate;
        $this->doValidate($exame);
        $this->cadastraPaciente($exame, $empresa);
        $exame->save();
        return array('id' => $exame->id);
    }

	private function doZipFiles($file_upload_list) {

		$path = $file_upload_list[0]['file_path'];
		$raw_name = $file_upload_list[0]['raw_name'];

		$data = array(
			'file_path' => $path,
			'full_path' => $path . $raw_name . '.zip',
			'raw_name' => $raw_name
		);

		$zip_name = $path . $raw_name . '.zip';
		$zip = new \ZipArchive();

		if ($zip->open($this->storePath($zip_name),  \ZipArchive::CREATE) == true) {
			for ($i = 0; $i < count($file_upload_list); $i++){
                $cn = strtolower($file_upload_list[$i]['client_name']);
                $cn = str_replace('.dcm', '#dcm', $cn);
				$zip->addFile($this->storePath($path.$file_upload_list[$i]['file_name']), $cn);
			}
			$zip->close();
			for ($i = 0; $i < count($file_upload_list); $i++) {
				$fn = $path . $file_upload_list[$i]['file_name'];
				unlink($this->storePath($fn));
			}
			return $data;
		}

		return null;

	}

    private function assinarLaudo($name, $medico) {
        $pfx = $this->storePath($medico->certificado);
        if (!file_exists($pfx)) return;
        $app = $this->storePath('/utils/signer.jar');
        $cmd = "java -jar " . $app . " " . $pfx . " " . $medico->senha . " " . $this->storePath($name);
        $ret = array();
		exec($cmd, $ret);
        $signed = str_replace('.pdf', '-signed.pdf', $name);
        if (!file_exists($this->storePath($signed))) return;
        unlink($this->storePath($name));
        rename($this->storePath($signed), $this->storePath($name));
    }

    private function geraLaudo($view, $data, $medico) {
        $this->makeDir($this->storePath(Self::$PATH_LAUDOS));
        $name = Self::$PATH_LAUDOS . \Str::random(40).'.pdf';
        $pdf = \PDF::loadView($view, $data);
        $pdf->save($this->storePath($name));
        $this->assinarLaudo($name, $medico);
        return $name;
    }

    private function convert_accent($string)
    {
        return htmlspecialchars_decode(htmlentities(utf8_decode($string)));
    }

    private function getImpossibilidades($values) {
        if (!$values) return null;
        $result = [];
        $values = explode(',', $values);
        foreach ($values as $id) {
            $i = Impossibilidade::where('id', $id)->first();
            $result[] = $i ? $i->nome : 'MOTIVO NAO ENCONTRADO';
        }
        return $result;
    }

    private function getDocumentos($exame) {
        $result = '';
        if ($exame->rg) $result .= ' RG: '.$exame->rg;
        if ($exame->cpf) $result .= ' CPF: '.$exame->cpf;
        if ($exame->funcao) $result .= ' Função:: '.$exame->funcao;
        return $result;
    }

    private function getIdadeOuNascimento($exame_id, $nascimento) {
        if (array_search($exame_id, [2,3,7,8]) === false) {
            return "Dt.Nasc: " . date_format(new \DateTime($nascimento),"d/m/Y")
            . ' (' . $this->calculate_years_old($nascimento) . ')';
        }
        return "Idade: " . $this->calculate_years_old($nascimento);
    }

    private function getQrCode($exame) {
        if ($exame->status == Self::$LAUDO_IMPOSSIBILITADO) return null;
        $url = url('') . "/retirada?id=" . $exame->protocolo;
        $qrCode = $this->geraQrCode($url, $exame->protocolo);
        return $this->storePath($qrCode);
    }

    private function getLaudoImagem($exame, $cliente, $arquivoImagem) {
        if ($cliente->imagemLaudoDesativado()) return null;
        if ($arquivoImagem) return $arquivoImagem;
        if (!$exame->arquivo_imagem) return null;
        if (strpos($exame->arquivo_imagem,"{{") !== false) {
            return preg_replace('/{{.*}}/', '_0', $exame->arquivo_imagem);
        }
        return $exame->arquivo_imagem;
    }

    private function cleanup($modelo) {
        //$modelo = '<p>Ritmo: SINUSAL<br><span style="font-size: 1rem;">Frequencia: 60 bpm<br></span><span style="font-size: 1rem;">Conclusão: ECG. com traçado sugestivo de:&nbsp;</span><span style="font-size: 1rem;">TESTE</span></p><p><span style="font-size: 1rem;">aaaaaaaaaaaaaaaaaaaaaaaaaa</span></p><p><span style="font-size: 1rem;">nNNNNNNNNNNNNNNNNN<br></span><span style="font-size: 1rem;">* A correta interpretação dos exames complementares deve ser feita mediante correlação com dados clínico-epidemiológicos do paciente.</span></p>';
        $lh = '/style="line-height: [0-9]*;"/';
        $fs12 = '/style="font-size: 12px;"/';
        $fs10 = 'style="font-size: 10px;"';
        $style = '/style=.*?".*?"/';
        $ret = preg_replace($lh, '', $modelo);
        $ret = preg_replace($fs12, $fs10, $ret);
        $ret = preg_replace($style, '', $ret);
        $ret = '<div class="cleanup">'.$ret.'</div>';
        return $ret;
    }

    function uploadArquivosLaudo($request) {
        return $this->doFormUpload($request, null, Self::$PATH_LAUDOS);
    }

    private function setRequestToDataFields($exame, $request) {

        $exame->zonas = $this->parseField($request, [
            'zonas_d1',
            'zonas_e1',
            'zonas_d2',
            'zonas_e2',
            'zonas_d3',
            'zonas_e3'
        ]);

        $exame->placas_parede_local = $this->parseField($request, [
            'placas_parede_local_0',
            'placas_parede_local_D',
            'placas_parede_local_E'
        ]);

        $exame->placas_frontal_local = $this->parseField($request, [
            'placas_frontal_local_0',
            'placas_frontal_local_D',
            'placas_frontal_local_E'
        ]);

        $exame->placas_diafrag_local = $this->parseField($request, [
            'placas_diafrag_local_0',
            'placas_diafrag_local_D',
            'placas_diafrag_local_E'
        ]);

        $exame->placas_outros_local = $this->parseField($request, [
            'placas_outros_local_0',
            'placas_outros_local_D',
            'placas_outros_local_E'
        ]);

        $exame->placas_parede_calcif = $this->parseField($request, [
            'placas_parede_calcif_0',
            'placas_parede_calcif_D',
            'placas_parede_calcif_E'
        ]);

        $exame->placas_frontal_calcif = $this->parseField($request, [
            'placas_frontal_calcif_0',
            'placas_frontal_calcif_D',
            'placas_frontal_calcif_E'
        ]);

        $exame->placas_diafrag_calcif = $this->parseField($request, [
            'placas_diafrag_calcif_0',
            'placas_diafrag_calcif_D',
            'placas_diafrag_calcif_E'
        ]);

        $exame->placas_outros_calcif = $this->parseField($request, [
            'placas_outros_calcif_0',
            'placas_outros_calcif_D',
            'placas_outros_calcif_E'
        ]);

        $exame->placas_extensao_od = $this->parseField($request, [
            'placas_extensao_od_0',
            'placas_extensao_od_D',
            'placas_extensao_od_1',
            'placas_extensao_od_2',
            'placas_extensao_od_3'
        ]);

        $exame->placas_extensao_oe = $this->parseField($request, [
            'placas_extensao_oe_0',
            'placas_extensao_oe_E',
            'placas_extensao_oe_1',
            'placas_extensao_oe_2',
            'placas_extensao_oe_3'
        ]);

        $exame->placas_largura_d = $this->parseField($request, [
            'placas_largura_d_D',
            'placas_largura_d_A',
            'placas_largura_d_B',
            'placas_largura_d_C'
        ]);

        $exame->placas_largura_e = $this->parseField($request, [
            'placas_largura_e_E',
            'placas_largura_e_A',
            'placas_largura_e_B',
            'placas_largura_e_C'
        ]);

        $exame->obliteracao = $this->parseField($request, [
            'obliteracao_0',
            'obliteracao_D',
            'obliteracao_E'
        ]);

        $exame->espes_parede_local = $this->parseField($request, [
            'espes_parede_local_0',
            'espes_parede_local_D',
            'espes_parede_local_E'
        ]);

        $exame->espes_frontal_local = $this->parseField($request, [
            'espes_frontal_local_0',
            'espes_frontal_local_D',
            'espes_frontal_local_E'
        ]);

        $exame->espes_parede_calcif = $this->parseField($request, [
            'espes_parede_calcif_0',
            'espes_parede_calcif_D',
            'espes_parede_calcif_E'
        ]);

        $exame->espes_frontal_calcif = $this->parseField($request, [
            'espes_frontal_calcif_0',
            'espes_frontal_calcif_D',
            'espes_frontal_calcif_E'
        ]);

        $exame->espes_extensao_od = $this->parseField($request, [
            'espes_extensao_od_0',
            'espes_extensao_od_D',
            'espes_extensao_od_1',
            'espes_extensao_od_2',
            'espes_extensao_od_3'
        ]);

        $exame->espes_extensao_oe = $this->parseField($request, [
            'espes_extensao_oe_0',
            'espes_extensao_oe_E',
            'espes_extensao_oe_1',
            'espes_extensao_oe_2',
            'espes_extensao_oe_3'
        ]);

        $exame->espes_largura_d = $this->parseField($request, [
            'espes_largura_d_D',
            'espes_largura_d_A',
            'espes_largura_d_B',
            'espes_largura_d_C'
        ]);

        $exame->espes_largura_e = $this->parseField($request, [
            'espes_largura_e_E',
            'espes_largura_e_A',
            'espes_largura_e_B',
            'espes_largura_e_C'
        ]);

        $exame->simbolos = $this->parseField($request, [
            'simbolos_aa',
            'simbolos_at',
            'simbolos_ax',
            'simbolos_bu',
            'simbolos_ca',
            'simbolos_cg',
            'simbolos_cn',
            'simbolos_co',
            'simbolos_cp',
            'simbolos_cv',
            'simbolos_di',
            'simbolos_ef',
            'simbolos_em',
            'simbolos_es',
            'simbolos_fr',
            'simbolos_hi',
            'simbolos_ho',
            'simbolos_id',
            'simbolos_ih',
            'simbolos_kl',
            'simbolos_me',
            'simbolos_pa',
            'simbolos_pb',
            'simbolos_pi',
            'simbolos_px',
            'simbolos_ra',
            'simbolos_rp',
            'simbolos_tb',
            'simbolos_od'
        ]);

        return $exame;

    }

    private function getSimbolos($exame) {

        $simbolos = $this->parseReverse($exame->simbolos, [
            'aa',
            'at',
            'ax',
            'bu',
            'ca',
            'cg',
            'cn',
            'co',
            'cp',
            'cv',
            'di',
            'ef',
            'em',
            'es',
            'fr',
            'hi',
            'ho',
            'id',
            'ih',
            'kl',
            'me',
            'pa',
            'pb',
            'pi',
            'px',
            'ra',
            'rp',
            'tb',
            'od'
        ]);

        $titulos = [
            'aa' => 'aorta aterosclerótica',
            'at' => 'espessamento pleural apical significativo',
            'ax' => 'coalescência de pequenas opacidades',
            'bu' => 'bolhas',
            'ca' => 'câncer',
            'cg' => 'nódulos não pneumoconióticos calcificados',
            'cn' => 'calcificação de pequenas opacidades pneumoconióticas',
            'co' => 'anormalidade de forma e tamanho do coração',
            'cp' => 'cor pulmonale',
            'cv' => 'cavidade',
            'di' => 'distorção significativa de estrutura intratorácica',
            'ef' => 'derrame pleural',
            'em' => 'enfisema',
            'es' => 'calcificações em casca de ovo',
            'fr' => 'fratura(s) de costela(s) recente(s) ou consolidadas',
            'hi' => 'Aumento de gânglios hilares e/ou mediastinais',
            'ho' => 'faveolamento',
            'id' => 'borda diafragmática mal definida',
            'ih' => 'borda cardíaca mal definida',
            'kl' => 'linhas septais (kerley)',
            'me' => 'mesotelioma',
            'pa' => 'atelectasia laminar',
            'pb' => 'banda(s) parenquimatosa(s)',
            'pi' => 'espessamento pleural de cisura(s) interlobar(es)',
            'px' => 'pneumotórax',
            'ra' => 'atelectasia redonda',
            'rp' => 'pneumoconiose reumática',
            'tb' => 'tuberculose',
            'od' => 'outras doenças'
        ];

        $result = [];

        foreach ($simbolos as $key => $value) {
            if ($value == 'S')  $result[$key] = $titulos[$key];
        }

        return $result;

    }

    private function isLaudoOit($exame) {
        return (
            $exame->exame_id == Self::$RAIOX_OIT_ID &&
            $exame->status != Self::$LAUDO_IMPOSSIBILITADO
        );
    }

    private function getOitFields($request) {

        $fields = [
            'rx_digital' => null,
            'negatoscopio' => null,
            'qualidade' => null,
            'comentarios_qualidade' => null,
            'normal' => null,
            'anormalidade_parenquima' => null,
            'primarias' => null,
            'secundarias' => null,
            'zonas_d1' => null,
            'zonas_e1' => null,
            'zonas_d2' => null,
            'zonas_e2' => null,
            'zonas_d3' => null,
            'zonas_e3' => null,
            'profusao' => null,
            'grd_opacidade' => null,
            'anormalidade_pleural' => null,
            'placas_pleurais' => null,
            'placas_parede_local_0'  => null,
            'placas_parede_local_D' => null,
            'placas_parede_local_E' => null,
            'placas_frontal_local_0' => null,
            'placas_frontal_local_D' => null,
            'placas_frontal_local_E' => null,
            'placas_diafrag_local_0' => null,
            'placas_diafrag_local_D' => null,
            'placas_diafrag_local_E' => null,
            'placas_outros_local_0' => null,
            'placas_outros_local_D' => null,
            'placas_outros_local_E' => null,
            'placas_parede_calcif_0' => null,
            'placas_parede_calcif_D' => null,
            'placas_parede_calcif_E' => null,
            'placas_frontal_calcif_0' => null,
            'placas_frontal_calcif_D' => null,
            'placas_frontal_calcif_E' => null,
            'placas_diafrag_calcif_0' => null,
            'placas_diafrag_calcif_D' => null,
            'placas_diafrag_calcif_E' => null,
            'placas_outros_calcif_0' => null,
            'placas_outros_calcif_D' => null,
            'placas_outros_calcif_E' => null,
            'placas_extensao_od_0' => null,
            'placas_extensao_od_D' => null,
            'placas_extensao_od_1' => null,
            'placas_extensao_od_2' => null,
            'placas_extensao_od_3' => null,
            'placas_extensao_oe_0' => null,
            'placas_extensao_oe_E' => null,
            'placas_extensao_oe_1' => null,
            'placas_extensao_oe_2' => null,
            'placas_extensao_oe_3' => null,
            'placas_largura_d_D' => null,
            'placas_largura_d_A' => null,
            'placas_largura_d_B' => null,
            'placas_largura_d_C' => null,
            'placas_largura_e_E' => null,
            'placas_largura_e_A' => null,
            'placas_largura_e_B' => null,
            'placas_largura_e_C' => null,
            'obliteracao_0' => null,
            'obliteracao_D' => null,
            'obliteracao_E' => null,
            'espessamento_pleural' => null,
            'espes_parede_local_0' => null,
            'espes_parede_local_D' => null,
            'espes_parede_local_E' => null,
            'espes_frontal_local_0' => null,
            'espes_frontal_local_D' => null,
            'espes_frontal_local_E' => null,
            'espes_parede_calcif_0' => null,
            'espes_parede_calcif_D' => null,
            'espes_parede_calcif_E' => null,
            'espes_frontal_calcif_0' => null,
            'espes_frontal_calcif_D' => null,
            'espes_frontal_calcif_E' => null,
            'espes_extensao_od_0' => null,
            'espes_extensao_od_D' => null,
            'espes_extensao_od_1' => null,
            'espes_extensao_od_2' => null,
            'espes_extensao_od_3' => null,
            'espes_extensao_oe_0' => null,
            'espes_extensao_oe_E' => null,
            'espes_extensao_oe_1' => null,
            'espes_extensao_oe_2' => null,
            'espes_extensao_oe_3' => null,
            'espes_largura_d_D' => null,
            'espes_largura_d_A' => null,
            'espes_largura_d_B' => null,
            'espes_largura_d_C' => null,
            'espes_largura_e_E' => null,
            'espes_largura_e_A' => null,
            'espes_largura_e_B' => null,
            'espes_largura_e_C' => null,
            'outras_anormalidades' => null,
            'simbolos_aa' => null,
            'simbolos_at' => null,
            'simbolos_ax' => null,
            'simbolos_bu' => null,
            'simbolos_ca' => null,
            'simbolos_cg' => null,
            'simbolos_cn' => null,
            'simbolos_co' => null,
            'simbolos_cp' => null,
            'simbolos_cv' => null,
            'simbolos_di' => null,
            'simbolos_ef' => null,
            'simbolos_em' => null,
            'simbolos_es' => null,
            'simbolos_fr' => null,
            'simbolos_hi' => null,
            'simbolos_ho' => null,
            'simbolos_id' => null,
            'simbolos_ih' => null,
            'simbolos_kl' => null,
            'simbolos_me' => null,
            'simbolos_pa' => null,
            'simbolos_pb' => null,
            'simbolos_pi' => null,
            'simbolos_px' => null,
            'simbolos_ra' => null,
            'simbolos_rp' => null,
            'simbolos_tb' => null,
            'simbolos_od' => null,
            'comentarios_laudo' => null
        ];

        $result = [];

        foreach ($fields as $key => $value) {
            if (!isset($request[$key])) {
                $result[$key] = null;
                continue;
            }
            $data = $request[$key];
            if ($data == 'true' || $data == 'false') {
                $result[$key] = $data == 'true' ? 'S' : 'N';
                continue;
            }
            $result[$key] = $data;
        }

        return $result;

    }

    function recusa(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $exame = Exame::where('id', $request['body']['exameId'])->first();
        if (!$exame) throw new ExameNaoEncontradoException();
        $this->verificaSePodeRecusar($exame, $empresa->matriz);
        $recusa = new DespachoRecusa();
        $recusa->medico_id = $exame->medico_id;
        $recusa->exame_id = $exame->id;
        $recusa->motivo = 'Recusado';
        $recusa->save();
        $exame->medico_id = 0;
        $exame->save();
        DespachoFila::where('medico_id', $recusa->medico_id)->delete();
        return [];
    }

    private function regraAplicavel($regra) {
        $diasDaSemana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];
        $dia = $diasDaSemana[date('w')];
        if (strpos($regra->dias, $dia) === false) return false;
        $horaAtual = time();
        $horaInicial = strtotime($regra->hora_inicial);
        $horaFinal = strtotime($regra->hora_final);
        return $horaAtual >= $horaInicial && $horaAtual <= $horaFinal;
    }

    private function verificaSePodeRecusar($exame, $matrizId) {
        if (!$exame) throw new ExameNaoEncontradoException();
        $medicos = ExameViewModel::getMedicosDoExame($exame->exame_id, $matrizId);
        if (count($medicos) == 1) throw new RecusaNaoPermitidaException('Recusa não permitida [UMC]');

        $regra = DespachoRegra::where([
            'ativa' => 1,
            'tipo' => Self::$REGRA_TIPO_EXCLUSIVIDADE,
            'medico_id' => $exame->medico_id,
            'tipo_exame_id' => $exame->exame_id,
            'cliente_id' => $exame->cliente_id // cliente específico
        ])->first();

        if ($regra && $this->regraAplicavel($regra)) { // cliente especifico e dentro do horário
            throw new RecusaNaoPermitidaException('Recusa não permitida [REG]');
        }

        $regra = DespachoRegra::where([
            'ativa' => 1,
            'tipo' => Self::$REGRA_TIPO_EXCLUSIVIDADE,
            'medico_id' => $exame->medico_id,
            'tipo_exame_id' => $exame->exame_id,
        ])->first();

        if (!$regra) return true;

        // REGRA NAO APLICAVEL POR HORARIO E DIA DA SEMANA?!
        if (!$this->regraAplicavel($regra)) return true;

        // REGRA DE EXCLUSIVIDADE PARA TODOS OS CLIENTES (REG)
        if ($regra->cliente_id == 0) throw new RecusaNaoPermitidaException('Recusa não permitida [REG]');

        return true;

    }

    function getSexo($exame) {
        if ($exame['sexo'] == "F") return "Feminino";
        if ($exame['sexo'] == "M") return "Masculino";
        return "";
    }

    function laudo(Request $request) {

        $request = $this->parseRequest($request);

        $exame = Exame::where('id', $request['id'])->first();
        if (!$exame) throw new ExameNaoEncontradoException();

        $medico = Medico::where('id', $exame->medico_id)->first();
        if (!$medico) throw new InclusaoDeLaudoException("Prazo para envio do laudo expirou!");

        $medico = $this->getMedicoByUsuario($request);
        if (!$medico) throw new InclusaoDeLaudoException(Self::$USUARIO_NAO_VINCULADO_A_MEDICO);

        if ($exame->medico_id != $medico->id) throw new InclusaoDeLaudoException(Self::$EXAME_DESPACHADO_PARA_OUTRO_MEDICO);

        $tipoExame = TipoExame::where('id', $exame->exame_id)->first();
        if (!$tipoExame) throw new TipoDeExameNaoEncontradoException();

        $cliente = Cliente::where('id', $exame->cliente_id)->first();
        if (!$cliente) throw new ClienteNaoEncontradoException();

        $exame->status = Self::$EXAME_LAUDADO;

        if (!$this->IsNullOrEmptyString($request['impossibilidades'])) {
            $exame->opcoes_impossibilitado = $request['impossibilidades'];
            $exame->status = Self::$LAUDO_IMPOSSIBILITADO;
            $exame->crc = $exame->crc . 'I';
        }

        $upload = $this->uploadArquivosLaudo($request);

        $exame->fill($request->all());

        $exame = $this->setRequestToDataFields($exame, $request);

        $exame->pausado = null;
        $exame->observacoes_medico = $request['observacoes'] ? $request['observacoes'] : null;
        $exame->modelo_content = $request['modelo'] ? $request['modelo'] : '';
        $exame->laudo_date = Carbon::now();
        $exame->laudo_imagem = $this->getLaudoImagem($exame, $cliente, $upload['arquivo_imagem']);
        $exame->laudo_anexo = $upload['arquivo_exame'];
        $exame->pagina_pdf_laudo = $request['pagina'];

        $motivo = MotivoExame::where('id', $exame->motivo_id)->first();
        $qrCode = $this->getQrCode($exame);
        $data = [
            'numero'       => $exame['id'],
            'exame_id'     => $exame['exame_id'],
            'nome'         => $tipoExame->nome,
            'paciente'     => $exame['paciente'],
            'contratante'  => $exame['contratante'],
            'idade'        => $this->getIdadeOuNascimento($exame['exame_id'], $exame['nascimento']),
            'sexo'         => $this->getSexo($exame),
            'documentos'   => $this->getDocumentos($exame),
            'data_exame'   => date_format(new \DateTime($exame->exame_date),"d/m/Y"),
            'data_laudo'   => date_format(new \DateTime($exame->laudo_date),"d/m/Y"),
            'medico_solicitante' => $exame['medico_solicitante'],
            'crm_solicitante'  => $exame['crm_solicitante'],
            'motivo'       => $motivo ? $motivo->nome : '-',
            'acuidade_longe_od' => $exame['acuidade_longe_od'],
            'acuidade_longe_oe' => $exame['acuidade_longe_oe'],
            'acuidade_perto_od' => $exame['acuidade_perto_od'],
            'acuidade_perto_oe' => $exame['acuidade_perto_oe'],
            'lente_corretiva' => $exame['lente_corretiva'],
            'senso_cromatico' => $exame['senso_cromatico'],
            'visao_noturna' => $exame['visao_noturna'],
            'visao_ofuscada' => $exame['visao_ofuscada'],
            'profundidade' => $exame['profundidade'],
            'dados_ecg' => '',
            'impossibilitado' => $this->getImpossibilidades($exame['opcoes_impossibilitado']),
            'cliente_mensagem' => '',
            'laudo_imagem' => $this->IsNullOrEmptyString($exame->laudo_imagem) ? null : $this->storePath($exame->laudo_imagem),
            'signer' => $this->storePath('/assets/signer.jpeg'),
            'protocolo' => $exame->protocolo,
            'assinatura' => $medico->assinatura ? $this->storePath($medico->assinatura) : null,
            'qrcode' => $qrCode,
            'cabecalho' => $cliente->cabecalho ? $this->storePath($cliente->cabecalho) : null,
            'rodape' => $cliente->rodape ? $this->storePath($cliente->rodape) : null,
            'modelo_content' => $tipoExame->desativar_modelo ? null : $this->cleanup($exame->modelo_content),
            'observacoes_medico' => $exame->observacoes_medico,
        ];

        $view = Self::$DEFAULT_LAUDO_VIEW;

        if ($this->isLaudoOit($exame)) {
            $view = Self::$OIT_LAUDO_VIEW;
            $data = $this->getOitFields($request);
            $data['idade'] = $this->getIdadeOuNascimento($exame['exame_id'], $exame['nascimento']);
            $data['data_exame'] = date_format(new \DateTime($exame->exame_date),"d/m/Y");
            $data['data_leitura'] = date_format(new \DateTime(),"d/m/Y");
            $data['paciente'] = $exame['paciente'];
            $data['contratante']  = $exame['contratante'];
            $data['medico_solicitante'] = $exame['medico_solicitante'] . '-'.$exame['crm_solicitante'];
            $data['motivo'] = $motivo ? $motivo->nome : '-';
            $data['sexo'] = $exame['sexo'] == "M" ? "Masculino" : "Feminino";
            $data['rg'] = $exame->rg ? $exame->rg : '-';
            $data['qrCode'] = $qrCode;
            $data['simbolos'] = $this->getSimbolos($exame);
            $data['logo'] = $cliente->logo_oit ? $this->storePath($cliente->logo_oit) : null;
            $data['sign'] = $medico->assinatura_oit ? $this->storePath($medico->assinatura_oit) : null;
            $data['signer'] = $this->storePath('/assets/signer.jpeg');
            $data['protocolo'] = $exame->protocolo;
            $data['yes'] = $this->storePath('/uploads/assets/yes.png');
            $data['no'] = $this->storePath('/uploads/assets/no.png');
        }

        $exame->arquivo_laudo = $exame['exame_id'] == Self::$HOLTER
            ? $this->geraLaudoHolter($view, $data, $medico, $upload, $exame)
            : $this->geraLaudo($view, $data, $medico);
        $exame->laudo_anexo = $exame['exame_id'] == Self::$HOLTER ? null :  $exame->laudo_anexo;

        $exame->arquivo_laudo = $this->compoeLaudoComAnexo($exame);

        $exame->save();

        DespachoFila::where('medico_id', $exame->medico_id)->delete();

        return ['id' => $exame->id];
    }

    function compoeLaudoComAnexo($exame) {
        if (!$exame->laudo_anexo) return $exame->arquivo_laudo;
        $arquivo_laudo = $this->storePath($exame->arquivo_laudo);
        $arquivo_anexo = $this->storePath($exame->laudo_anexo);
        if (!\File::exists($arquivo_laudo) || !\File::exists($arquivo_anexo)) {
            return $exame->arquivo_laudo;
        }
        $zipName = Self::$PATH_LAUDOS . \Str::random(40) . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($this->storePath($zipName), \ZipArchive::CREATE) === true) {
            $nome_laudo = Utils::getNomeLaudoParaDownload($exame);
            $nome_anexo = Utils::getNomeTracadoParaDownload($exame);
            $zip->addFile($arquivo_laudo, $nome_laudo);
            $zip->addFile($arquivo_anexo, $nome_anexo);
            $zip->close();
            return  $zipName;
        }
        return $exame->arquivo_laudo;
    }

    function geraLaudoHolter($view, $data, $medico, $upload, $exame) {
        if ($exame->status == Self::$LAUDO_IMPOSSIBILITADO) {
            return $this->geraLaudo($view, $data, $medico);
        }
        $this->makeDir($this->storePath(Self::$PATH_LAUDOS));
        $arquivo_exame = $upload['arquivo_pdf'];
        if (!$arquivo_exame) throw new InclusaoDeLaudoException("Arquivo PDF é necessário!");
        $arquivo_laudo = str_replace("/exames/", "/laudos/", $arquivo_exame);
        copy($this->storePath($arquivo_exame), $this->storePath($arquivo_laudo));
        $this->assinarLaudo($arquivo_laudo, $medico);
        return $arquivo_laudo;
    }

    function registerUserInSession($request, $usuario) {
        $this->registerSession(
            $request,
            'usuario',
            $usuario
        );
    }

    function lote(Request $request) {

        $request = $this->parseRequest($request);

        Self::$USUARIO_LOGADO = $this->getUsuarioLogado($request);

        if (!$request->hasFile('files')) {
            throw new InclusaoDeExameException(Self::$ANEXOS_NAO_INFORMADOS);
        }

        $cliente = $this->getClienteByUsuario($request);

        $key = $cliente == null ? null : $cliente->chave_transmissao;

        if ($key != null) $this->registerProperties($key, 'LOTE');

        $file_upload_list = array();

        foreach ($request->file('files') as $file) {

            $data = $this->doUpload($file);

            $orig_name = strtolower($data['orig_name']);
            if (preg_match('/dama_imagens.zip/', $orig_name ) == 1 && !$this->isLoginCliente($request)) {
				$this->insertIMAGENS($data);
                break;
            }

            if (!$cliente) throw new UsuarioNaoAssociadoAClienteException();

            if ($data['file_ext'] == '.zip') {
                $this->insertZip($data, $key);
                continue;
            }

            array_push($file_upload_list,
				array(
					'client_name' => $data['client_name'],
					'file_path' => $data['file_path'],
					'file_name' => $data['file_name'],
					'raw_name' => $data['raw_name']
				)
			);

        }

        if (count($file_upload_list) > 0) {
            $data = $this->doZipFiles($file_upload_list);
            if ($data == null) return;
            $this->insertZip($data, $key);
        }

        return [];

    }

    function upload(Request $request) {
        $key = $this->getQueryParam($request, 'key');
        $this->registerProperties($key, 'DAMA_DESKTOP');
        $request->validate([ 'file' => 'required|mimes:zip|max:512500' ]); // 50Mb
        if ($request->file()) {
            $data = $this->doUpload($request->file('file'));
            if ($data['file_ext'] == '.zip') {
                $this->insertZip($data, $key);
                return;
            }
            $this->doInsert($data['full_path'], $data['file_name'], $key);
        }
        return $key;
    }

    function download(Request $request) {
        $key = $this->getQueryParam($request, 'key');
        $cliente = Cliente::where('chave_transmissao', $key)->first();
        if (!$cliente) throw new ChaveDeTransmissaoNaoEncontradaException();
        $laudos = ExameViewModel::getLaudosParaDownload($cliente->id);
        if ($laudos) {
            $zipName = $this->geraZipParaDownload($laudos, $cliente);
            return response()->download($zipName);
        }
        return '';
    }

    function geraZipParaDownload($laudos, $cliente) {
        $tipoExame = array('*', 'ECG', 'EEG', 'ESPIRO', 'RAIOX', 'MAPA', 'ACUIDADE', 'EEG_CLINICO', 'MAPEAMENTO', 'RAIOX_OIT', 'HOLTER', 'ESPIRO_PNEUMO', 'ESPIRO_CLINICA', 'ISHIHARA');
        $path = '/temp';
        if (!is_dir($this->storePath($path))){
            mkdir($this->storePath($path), 0777);
        }
        $zip = new \ZipArchive();
        $zipName = $this->storePath($path . '/' . $cliente->id . '.zip');
        if (file_exists($zipName)) unlink($zipName);
        if ($zip->open($zipName, \ZipArchive::CREATE) === true) {
            foreach($laudos as $laudo) {
                $file = $this->storePath($laudo['arquivo_laudo']);
                if (!file_exists($file)) continue;
                $exame = Exame::where('id', $laudo['id'])->first();
                $exame->laudo_download_date = Carbon::now();
                $exame->save();
                $name = Utils::getNomeLaudoParaDownload($exame);
                $zip->addFile($file, $name);
            }
            $zip->close();
        }
        return $zipName;
    }

    // =============================================================================================

    private function insertZip($data, $dama_desktop_key = null) {

        if ( $dama_desktop_key == null) throw new ChaveDeTransmissaoNaoEncontradaException();

        set_time_limit(0);

        $cliente = $this->getClienteDoExame((object)array('DamaDesktopKey' => $dama_desktop_key));
        $CLIENTE_ID = $cliente == null ? null : $cliente->id;
        if ($CLIENTE_ID == null) throw new ClienteNaoEncontradoException();

        $path = $data['file_path'] . $CLIENTE_ID . '/';
        $path_images = $path . '/imagens/';
        $path_dcm = $data['file_path'] . '/dcm/';

        if (!is_dir($this->storePath($path))){
            mkdir($this->storePath($path), 0777);
        }

        if (!is_dir($this->storePath($path_images))){
            mkdir($this->storePath($path_images), 0777);
        }

        if (!is_dir($this->storePath($path_dcm))){
            mkdir($this->storePath($path_dcm), 0777);
        }

        self::normalizeFileNames($data);

        $files_in_zip = array();

        $zip  = new \ZipArchive();
        $zip2 = new \ZipArchive();

        if ($zip->open($this->storePath($data['full_path'])) === TRUE) {

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $files_in_zip = $this->addFileInList($files_in_zip, $name);
            }

            $files_in_zip = $this->setFileTarget($files_in_zip);
            $files_in_zip = $this->setImagemTarget($files_in_zip);

            for ($i = 0; $i < count($files_in_zip); $i++){

		        $params = array(
                    'imagem' => null,
                    'recepcionado' => $CLIENTE_ID
                );

                $zip->extractTo($this->storePath($path), $files_in_zip[$i]['files']);

                if ($files_in_zip[$i]['image']){
					$image_file_name = str_replace(' ', '_', $files_in_zip[$i]['image']);
                    $params['imagem'] = Self::$PATH_LOTES . $CLIENTE_ID . '/imagens/' . $image_file_name;
                    $zip->extractTo(
                        $this->storePath($path_images),
                        $files_in_zip[$i]['image']
                    );
					rename(
						$this->storePath($path_images.$files_in_zip[$i]['image']),
						$this->storePath($path_images.$image_file_name)
					);
                }

                $files_in_zip = $this->renameFile(
                    $files_in_zip,
                    $path,
                    $i,
                    $data['raw_name']
                );

				if (!$this->isFilesGroupIsValid($files_in_zip[$i])) continue;

                if (count($files_in_zip[$i]['files']) == 1) {
                    if (!$this->isOneFile($files_in_zip[$i]['files'][0])) continue;
                    $full_path = $path . $files_in_zip[$i]['files'][0];
                    $this->doInsert(
                        $full_path,
                        Self::$PATH_LOTES.$CLIENTE_ID.'/'.$files_in_zip[$i]['files'][0],
                        $dama_desktop_key,
                        $params
                    );
                    continue;
                }

                $raw_name = $data['raw_name'] . '_' . $files_in_zip[$i]['raw_name'] . '.zip';

                $zip_name = $path . $raw_name;

                if ($zip2->open($this->storePath($zip_name),  \ZipArchive::CREATE) === true) {

                    $this->doInsert(
                        $path.$files_in_zip[$i]['target'],
                        Self::$PATH_LOTES.$CLIENTE_ID . '/' . $raw_name,
                        $dama_desktop_key,
                        $params
                    );

                    for ($f = 0; $f < count($files_in_zip[$i]['files']); $f++) {
                        $name = $this->storePath($path.$files_in_zip[$i]['files'][$f]);
                        $zip2->addFile($name, $files_in_zip[$i]['files'][$f]);
                    }

                    $zip2->close();

                    for ($f = 0; $f < count($files_in_zip[$i]['files']); $f++) {
                        $name = $this->storePath($path.$files_in_zip[$i]['files'][$f]);
                        unlink($name);
                    }

                }

            }

            for ($f = 0; $f < count($files_in_zip); $f++) {
                $name = $this->storePath($path.$files_in_zip[$f]['name']);
                $this->delDir($name);
            }

			$dcm_files = array();
            for ($i = 0; $i < $zip->numFiles; $i++) {
				$name = $zip->getNameIndex($i);
                $dcm_files = $this->addDCMFileInList(
                    $dcm_files,
                    $zip,
                    $name,
                    $path_dcm
                );
            }

            foreach ($dcm_files as $key => $obj) {
                $clinica_id = $this->getClinicaIdDoDCM($obj, $dama_desktop_key);
                if (!$clinica_id) continue;

                if ($this->isSingleDCMInserted($obj, $clinica_id, $CLIENTE_ID, $path_dcm, $dama_desktop_key)) {
                    continue;
                }

                $dcm_count = count($obj['files']);

                if ($this->createDCMZip($obj, $path_dcm)) {
					$observacao = '';
					foreach($obj['exames'] as $e) {
						$observacao .= trim($e) . ' + ';
					}
                    $observacao = $obj['subtipo'] . ' - ' . $observacao;
					$params = array(
						'OIT' => $obj['OIT'],
						'imagem' => Self::$PATH_LOTES.'dcm/' . $obj['zip_name'] . '{{' . $dcm_count . '}}.jpg',
						'clinica_id' => $clinica_id,
						'observacao' => substr($observacao, 0, strlen($observacao)-3),
						'subtipo' => $obj['subtipo'],
                        'medico' => $obj['medico'],
                        'empresa' => $obj['empresa'],
                        'recepcionado' => $CLIENTE_ID
					);
					$this->insertExameDCM(
						$path_dcm.$obj['files'][0],
						Self::$PATH_LOTES.'dcm/'.$obj['zip_name'].'.zip',
						$dama_desktop_key,
						$params
					);
				}
			}

            $zip->close();

        }

    }

    private function isSingleDCMInserted($obj, $clinica_id, $CLIENTE_ID, $path_dcm, $dama_desktop_key) {
        $count = count($obj['files']);
        if ($count > 1) return false;
        $observacao = '';
        foreach($obj['exames'] as $e) {
            $observacao .= trim($e) . ' + ';
        }
        $observacao = $obj['subtipo'] . ' - ' . $observacao;
        $params = array(
            'OIT' => $obj['OIT'],
            'imagem' => Self::$PATH_LOTES.'dcm/' . $obj['zip_name'] . '{{' . $count . '}}.jpg',
            'clinica_id' => $clinica_id,
            'observacao' => substr($observacao, 0, strlen($observacao)-3),
            'subtipo' => $obj['subtipo'],
            'medico' => $obj['medico'],
            'empresa' => $obj['empresa'],
            'recepcionado' => $CLIENTE_ID
        );
        $this->insertExameDCM(
            $path_dcm.$obj['files'][0],
            Self::$PATH_LOTES.'dcm/'.$obj['files'][0],
            $dama_desktop_key,
            $params
        );
        return true;
    }

    private function doUpload($file) {
        $raw_name = \Str::random(40);
        $file_ext = '.' . strtolower($file->getClientOriginalExtension());
        $file_name = $raw_name . $file_ext;
        $file_path = Self::$PATH_LOTES;
        $full_path = $file_path . $file_name;
        $origin_name = $file->getClientOriginalName();
        $client_name = $file->getClientOriginalName();

        $file->move(
            $this->storePath($file_path),
            $file_name
        );

        return array(
            'file_name' => $file_name,
            'file_type' => 'application/zip',
            'file_path' => $file_path,
            'full_path' => $full_path,
            'raw_name' => $raw_name,
            'orig_name' => $origin_name,
            'client_name' => $client_name,
            'file_ext' => $file_ext,
            'file_size' => \File::size($this->storePath($full_path))
        );

    }

    private function addFileInList($files_in_zip, $name){

        $len = strpos($name, '/');
        if ($len === false) $len = strlen($name) - strlen(strrchr($name, '.'));
        $fname = substr($name, 0, $len);

		if (preg_match('/(\.dcm|\.oit)/', strtolower($name)) == 1) return $files_in_zip;

        for ($i = 0; $i < count($files_in_zip); $i++)
            if ($files_in_zip[$i]['name'] == $fname){
                array_push($files_in_zip[$i]['files'], $name);
                return $files_in_zip;
            }

        $files_in_zip[] = array(
            'name' => $fname,
            'target' => null,
            'raw_name' => crc32($fname),
            'files' => array($name)
        );

        return $files_in_zip;

    }

    private function isTarget($name){
        return preg_match(self::$TARGET, $name) == 1;
    }

    private function isHighPriority($name){
        return preg_match(self::$HIGH_PRIORITY, $name) == 1;
    }

    private function getFileTarget($files){
        $target = null;
        for ($i = 0; $i < count($files); $i++) {
            if ($this->isTarget($files[$i])) $target = $files[$i];
            if ($this->isHighPriority($target)) return $target;
        }
        return $target;
    }

    private function isImageFile($name, $mime = null) {
        if ($mime) return str_contains($mime, 'image');
        preg_match('/(jpg|jpeg|bmp|gif|png)/', strtolower($name), $match);
        return $match;
    }

    private function isDCMFile($name, $mime = null) {
        if ($mime) return str_contains($mime, 'image');
        preg_match('/(dcm)/', $name, $match);
        return $match;
    }

    private function getImagemTarget($files){
        for ($i = 0; $i < count($files); $i++) {
            if ($this->isImageFile($files[$i])) return $files[$i];
        }
        return null;
    }

    private function setFileTarget($files){
        for ($i = 0; $i < count($files); $i++) {
            $files[$i]['target'] = $this->getFileTarget($files[$i]['files']);
        }
        return $files;
    }

    private function setImagemTarget($files){
        for ($i = 0; $i < count($files); $i++) {
            $files[$i]['image'] = $this->getImagemTarget($files[$i]['files']);
        }
        return $files;
    }

    private function renameFile($files_in_zip, $path, $i, $raw_name){

		$name = str_replace("#", "_", $files_in_zip[$i]['name']);

		$files_in_zip[$i]['name'] = $name;
		$files_in_zip[$i]['target'] = str_replace("#", "_", $files_in_zip[$i]['target']);

		for ($f = 0; $f < count($files_in_zip[$i]['files']); $f++){
			$new_name = str_replace("#", "_", $files_in_zip[$i]['files'][$f]);
			rename(
                $this->storePath($path . $files_in_zip[$i]['files'][$f]),
                $this->storePath($path . $new_name)
            );
			$files_in_zip[$i]['files'][$f] = $new_name;
		}

		return $files_in_zip;

    }

	private function isFilesGroupIsValid($files){
		$target = strtolower($files['target']);
		if (preg_match('/(\.xml|\.txt)/', $target) == 1) {
			$ex0 = false; $fas = false;
			for ($i = 0; $i < count($files['files']); $i++) {
				$name = strtolower($files['files'][$i]);
				if (preg_match('/(\.ex0)/', $name) == 1) $ex0 = true;
				if (preg_match('/(\.fas)/', $name) == 1) $fas = true;
			}
			return ($ex0 && $fas);
		}
		return true;
	}

    private function isOneFile($name){
        $name = strtolower($name);
        if (preg_match('/(\.eeg)/',  $name) == 1) return true;
        if (preg_match('/(\.wxml)/', $name) == 1) return true;
        if (preg_match('/(\.mdt)/',  $name) == 1) return true;
        if (preg_match('/(\.dcm)/',  $name) == 1) return true;
        if (preg_match('/(\.oit)/',  $name) == 1) return true;
        if (preg_match('/(\.pdf)/',  $name) == 1) return true;
        return false;
    }

    private function delDir($dir) {
        if (!is_dir($dir)) {
            return true;
        }

        if (!file_exists($dir)) {
            return true;
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->delDir($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

	private function str_remove($data, $regexp){
		$str = utf8_encode($data);
		$str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		return preg_replace('/' . $regexp . '/i', "", $str);
	}

	private function remove_accents($data){
		$str = utf8_encode($data);
		$str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		return preg_replace("/[^A-Za-z0-9 \-\,]/i", "", $str);
	}

	private function isOIT($dicom){
        $dicom->parse(array('PatientComments')); // 0x0010,0x4000
        $dicom->parse(array('AcquisitionDeviceProcessingDescription')); // 0x0018,0x1400
        $dicom->parse(array('SeriesDescription')); // 0x0008,0x103E
		$patientComments = strtoupper($dicom->value(0x0010,0x4000));
		$acquisitionDevice = strtoupper($dicom->value(0x0018,0x1400));
		if (strlen(trim($acquisitionDevice)) == 0) $acquisitionDevice = trim($dicom->value(0x0008,0x103E));
		$patientComments = $this->str_remove($patientComments, "[^A-Z0-9 ]");
		$acquisitionDevice = $this->str_remove($acquisitionDevice, "[^A-Z0-9 ]");
		if (strpos($patientComments, ' OIT') === false) return false;
		if (strpos($acquisitionDevice, 'TORAX') === false) return false;
		return true;
	}

	private function DCM2JPG($name){
		$jpg  = str_replace('.dcm', '.jpg', $name);
		$path = $this->storePath('/utils/dcm4che/bin/');
        $app = substr(php_uname(), 0, 7) == "Windows" ? 'dcm2jpg.bat' : 'dcm2jpg';
		$cmd = $path . $app . ' ' . $this->storePath($name) . ' ' . $this->storePath($jpg);
		if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B ". $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
		return $jpg;
	}

    private function getNasc($data, $idade){
        $ano = (int)substr($data, strlen($data) - 4, 4);
        $ano = $ano - (int)$idade;
        return substr($data, 0, strlen($data) - 4) . $ano;
    }

    private function getCRJDICOMInfoCR($dicom) {
        $dicom->parse(array('InstitutionName'));   // 0x0008,0x0080
        $dicom->parse(array('StationName'));       // 0x0008,0x1010
        $dicom->parse(array('StudyDescription'));  // 0x0008,0x1030
        $dicom->parse(array('SeriesDescription')); // 0x0008,0x103E
        $dicom->parse(array('PerformedProcedureStepDescription')); // 0x0040, 0x0254
        $cliente = strtoupper($dicom->value(0x0008,0x0080));
        $studyDescription = trim(strtoupper($dicom->value(0x0008,0x1030)));
        $seriesDescription = $this->remove_accents(
            trim(
                strtoupper($dicom->value(0x0008,0x103E))
            )
        );
        $tipoExame = $this->remove_accents(
            trim(
                strtoupper($dicom->value(0x0040, 0x0254))
            )
        );
        $medico = trim(str_replace('^', ' ', $dicom->value(0x0008,0x0090)));
        $medico = $this->remove_accents(
            $this->getNomeSobrenome($medico)
        );
        $empresa = $this->remove_accents(
            trim(str_replace('^', ' ', $dicom->value(0x0038,0x4000)))
        );
        $id = $dicom->value(0x0020,0x000D) . '-' . $tipoExame; ;
        $isOit = $studyDescription == 'OIT' && $seriesDescription == 'PA PORTRAIT' &&
                 $tipoExame == 'PEITO' ? "S" : "N";
		$info = (object)array(
			'id' => $id,
			'cliente' => $cliente,
			'oit' => $isOit,
			'exame' => $seriesDescription,
			'subtipo' => $tipoExame,
            'medico' => $medico,
            'empresa' => $empresa
		);
        return $info;
    }

    private function getCRJDICOMInfoDR($dicom) { //Thais
        $dicom->parse(array('InstitutionName'));   // 0x0008,0x0080
        $dicom->parse(array('StationName'));     // 0x0008,0x1010
        $dicom->parse(array('StudyDescription'));  // 0x0008,0x1030
        $dicom->parse(array('SeriesDescription')); // 0x0008,0x103E
        $dicom->parse(array('PatientID')); //    0x0010,0x0020 CPF ou RG
        $dicom->parse(array('BodyPartExamined'));  // 0x0018,0015
        $dicom->parse(array('ProtocolName'));  // 0x0018,1030
        $dicom->parse(array('ReferringPhysiciansName'));  // 0x0008,0x0090
        $dicom->parse(array('ImageLaterality'));  // 0020,0062   lado direit ou esquerdo
        $dicom->parse(array('CodeMeaning'));  // 0008,0104   Paranasal sinus / (Seios da face)

        $cliente = strtoupper($dicom->value(0x0008,0x0080));  //CRJ - Centro Radiologico Jundiai
        $studyDescription = trim(strtoupper($dicom->value(0x0018,0x1030)));   //Mao EObliqua

        $seriesDescription = $this->remove_accents(
            trim(
                strtoupper($dicom->value(0x0008,0x103E)) //Obliqua
            )
        );
        $bodypartexamined = $this->remove_accents(
            trim(
                strtoupper($dicom->value(0x0018,0x0015))  //HAND
            )
        );
         $patientposition = $this->remove_accents(
            trim(
                strtoupper($dicom->value(0x0008,0x103E))   //Obliqua
            )
        );

        $ausencia_tipoExame = substr($dicom->value(0x0008,0x1030), 0, 5);  // 5 PRIMEIRASLETRAS  / Forearm bone //ANTEBRAÇO

        switch ($ausencia_tipoExame) {
            case "Seios":
                $ausencia_tipoExame = "SEIOS DA FACE";
                break;
            case "Forea":
                $ausencia_tipoExame = "ANTEBRAÇO";
                break;
            case "Cavum":
                $ausencia_tipoExame = "CAVUM";
                break;
        }

         $tipoExame = $this->remove_accents(trim(strtoupper($dicom->value(0x0018, 0x0015))));  //HAND

        if ($tipoExame == null) $tipoExame = $ausencia_tipoExame . " - "; // thais ver
         switch ($tipoExame) {
            case "HAND":
                $tipoExame = "MAO";
                break;
            case "ELBOW":
                $tipoExame = "COTOVELO";
                break;
            case "LSPINE":
                $tipoExame = "COLUNA LOMBAR";
                break;
            case "CSPINE":
                $tipoExame = "COLUNA CERVICAL";
                break;
            case "CHEST":
                $tipoExame = "TORAX";
                break;
            case "KNEE":
                $tipoExame = "JOELHO";
                break;
            case "WRIST":
                $tipoExame = "PUNHO";
                break;
            case "SHOULDER":
                $tipoExame = "OMBRO";
                break;
            case "HUMERUS":
                $tipoExame = "UMERO";
                break;
            case "SCAPULA":
                $tipoExame = "ESCAPULA";
                break;
            case "CLAVICLE":
                $tipoExame = "CLAVICULA";
                break;
            case "HIP":
                $tipoExame = "ART. QUADRIL";
                break;
        }

        $lateralidade = $this->remove_accents(trim(strtoupper($dicom->value(0x0020, 0x0062))));  //L ou R ou vazia
       	if ($lateralidade == "L") $tipoExame = $tipoExame ." ESQ";
       	if ($lateralidade == "R") $tipoExame = $tipoExame ." DIR";

        $medico = trim(str_replace('^', ' ', $dicom->value(0x0008,0x0090))); //GESSE^GOMES^BARBOSA
        $medico = $this->remove_accents($medico);

        $empresa = trim(str_replace('^', ' ', $dicom->value(0x0010,0x4000))); //METRA HOERBIGER BRASIL LTDA
       	$empresa = $this->remove_accents($empresa);

        $id = $dicom->value(0x0020,0x000D) . '-' . $tipoExame; //1.2.392.200036.9107.307.31235.113123521110900680

        $isOit = $studyDescription == 'TORAX PA/AP' && $bodypartexamined == 'CHEST' &&
                 $patientposition == 'OIT PA' ? "S" : "N";

        $info = (object)array(
            'id' => $id,
            'cliente' => $cliente,
            'oit' => $isOit,
            //'exame' => $seriesDescription,
            'exame' => $patientposition,
            'subtipo' => $tipoExame,
            'medico' => $medico,
            'empresa' => $empresa,
            'cpf' => '' // $cpf
        );

        return $info;

    }

    private function getBEMVIVERDICOMInfoDR($dicom) { //Thais
        $dicom->parse(array('InstitutionName'));   // 0x0008,0x0080
        $dicom->parse(array('StationName'));     // 0x0008,0x1010
        $dicom->parse(array('StudyDescription'));  // 0x0008,0x1030
        $dicom->parse(array('PatientID')); //    0x0010,0x0020 CPF ou RG
        $dicom->parse(array('BodyPartExamined'));  // 0x0018,0015
        $dicom->parse(array('ProtocolName'));  // 0x0018,1030
        $dicom->parse(array('ReferringPhysiciansName'));  // 0x0008,0x0090
        $cliente = strtoupper($dicom->value(0x0008,0x0080));  //BEM VIVER

        $bodypartexamined = $this->remove_accents(
            trim(
                strtoupper($dicom->value(0x0018,0x0015))  //CHEST
            )
        );

        $tipoExame = $this->remove_accents(trim(strtoupper($dicom->value(0x0018, 0x0015))));  //HAND

        $medico = trim(str_replace('^', ' ', $dicom->value(0x0008,0x0090))); //GESSE^GOMES^BARBOSA
        $medico = $this->remove_accents($medico);

        $empresa = trim(str_replace('^', ' ', $dicom->value(0x0008,0x1030))); //METRA HOERBIGER BRASIL LTDA
        $empresa = $this->remove_accents($empresa);

        $id = $dicom->value(0x0020,0x000D) . '-' . $tipoExame; //1.2.392.200036.9107.307.31235.113123521110900680

        $ProtocolName = $this->remove_accents(
            trim(
                strtoupper($dicom->value(0x0018,0x1030))  //TORAX OIT PA
            )
        );

        $isOit = $ProtocolName == 'TORAX OIT PA' && $bodypartexamined == 'CHEST' ? "S" : "N";

        $info = (object)array(
            'id' => $id,
            'cliente' => $cliente,
            'oit' => $isOit,
            'exame' => $bodypartexamined,
            'medico' => $medico,
            'empresa' => $empresa,
            'subtipo' => $tipoExame
        );

        return $info;

    }

    private function getDICOMInfo($file){

        $dicom = Dicom::getInstance($this->storePath($file));

        $dicom->parse(array('StudyInstanceUID'));  // 0x0020,0x000D
        $dicom->parse(array('InstitutionName'));   // 0x0008,0x0080
        $dicom->parse(array('SeriesDescription')); // 0x0008,0x103E
        $dicom->parse(array('PatientComments'));   // 0x0010,0x4000
        $dicom->parse(array('StationName'));       // 0x0008,0x1010
        $dicom->parse(array('AcquisitionDeviceProcessingDescription')); // 0x0018,0x1400

        if ($dicom->value(0x0008,0x1010) == 'XC_DICOM_CRJ') return $this->getCRJDICOMInfoCR($dicom);
        if ($dicom->value(0x0008,0x0070) == 'KONICA MINOLTA') return $this->getCRJDICOMInfoDR($dicom); //Thais
        if ($dicom->value(0x0008,0x0070) == 'Imex Medical Group') return $this->getBEMVIVERDICOMInfoDR($dicom); //Thais

        $cliente = $dicom->value(0x0008,0x0080);

        $seriesDescription = trim($dicom->value(0x0008,0x103E));
        if (strlen($seriesDescription) == 0) $seriesDescription = trim($dicom->value(0x0018,0x1400));
        $seriesDescription = strtoupper($seriesDescription);
        $seriesDescription = $this->str_remove($seriesDescription, "[^A-Z0-9 ]");

        $tipoExame = $this->str_remove($seriesDescription, "[^A-Z0-9]");

        $tipoExame = str_replace("LAT", "", $tipoExame);
        $tipoExame = str_replace("FRN", "", $tipoExame);
        $tipoExame = str_replace("AP", "", $tipoExame);

        if ($tipoExame == "PA") $tipoExame = "TORAX";

        $tipoExame = str_replace("PA", "", $tipoExame);
        $tipoExame = str_replace("OBL", "", $tipoExame);
        $tipoExame = str_replace("PERFIL", "", $tipoExame);
        $tipoExame = str_replace("MENTO", "", $tipoExame);
        $tipoExame = str_replace("FRONTO", "", $tipoExame);

        $id = $dicom->value(0x0020,0x000D) . '-' . $tipoExame;

        $isOit = $this->isOit($dicom) ? "S" : "N";

        $info = (object)array(
            'id' => $id,
            'cliente' => $cliente,
            'oit' => $isOit,
            'exame' => $seriesDescription,
            'subtipo' => $tipoExame,
            'medico' => null,
            'empresa' => null
        );

        return $info;
    }

    private function addDCMFileInList($dcm_files, $zip, $name, $path_dcm){
        if (preg_match('/(\.dcm|\.oit)/', strtolower($name)) !== 1) return $dcm_files;
        $isOit = (preg_match('/(\.oit)/', strtolower($name)) == 1) ? "S" : "N";
        $new_name = md5(uniqid(rand(), true)) . '.dcm';
        $zip->renameName($name, $new_name);
        $zip->extractTo(
            $this->storePath($path_dcm),
            $new_name
        );
        $info = $this->getDICOMInfo($path_dcm.$new_name);
        $isOit = ($isOit == "S") ? "S" : $info->oit;
        if (array_key_exists($info->id, $dcm_files)){
            $final_name = $dcm_files[$info->id]['zip_name'] . '_' . count($dcm_files[$info->id]['files']) . '.dcm';
            $dcm_files[$info->id]['files'][] = $final_name;
            $dcm_files[$info->id]['exames'][] = $info->exame;
            rename(
                $this->storePath($path_dcm.$new_name),
                $this->storePath($path_dcm.$final_name)
            );
            $this->DCM2JPG($path_dcm.$final_name);
            return $dcm_files;
        }
        $dcm_files[$info->id] = array(
            'cliente' => $info->cliente,
            'OIT' => $isOit,
            'zip_name' => md5(uniqid(rand(), true)),
            'files' => null,
            'exames' => array($info->exame),
            'subtipo' => $info->subtipo,
            'medico' => $info->medico,
            'empresa' => $info->empresa
        );
        $final_name = $dcm_files[$info->id]['zip_name'] . '_0.dcm';
        $dcm_files[$info->id]['files'] = array($final_name);
        rename(
            $this->storePath($path_dcm.$new_name),
            $this->storePath($path_dcm.$final_name)
        );
        $this->DCM2JPG($path_dcm.$final_name);
        return $dcm_files;

    }

    private function getClinicaIdDoDCM($obj, $dama_desktop_key){
        $usuario = $this->getSession('usuario');
        if ($usuario) return $usuario->conta_cliente;

        if (strlen(trim($dama_desktop_key)) > 0){
            $cliente = Cliente::where('chave_transmissao', $dama_desktop_key)->first();
            if ($cliente) return $cliente->id;
        }

        $cnpj = $obj['cliente'];
        if ($cnpj){
            $cliente = Cliente::where('cnpj', $cnpj)->first();
            if ($cliente) return $cliente->id;
        }
        $institutionName = $obj['cliente'];
        if ($institutionName){
            $cliente = Cliente::where('institution_name', $institutionName)->first();
            if ($cliente) return $clinica->id;
        }
        return null;
    }

    private function isAdmin($usuario){
        return $usuario->tipo == 'admin' || $usuario->tipo == 'auditor';
    }

    private function createDCMZip($obj, $path_dcm){
        $zip = new \ZipArchive();
        $name = $this->storePath($path_dcm.$obj['zip_name'].'.zip');
        if ($zip->open($name, \ZipArchive::CREATE) == true) {
            foreach($obj['files'] as $item){
                $zip->addFile(
                    $this->storePath($path_dcm.$item),
                    $item
                );
            }
            $zip->close();
            return true;
        }
        return false;
    }

    private function getObject(){
        return (object)array(
            'Tipo' => null,
            'Motivo' => null,
            'Data' => null,
            'Empresa' => null,
            'Paciente' => null,
            'DataNasc' => null,
            'Idade' => null,
            'Sexo' => null,
            'Altura' => null,
            'Peso' => null,
            'IMC' => null,
            'Medico' => null,
            'Observacao' => null,
            'RG' => null,
            'CPF' => null,
            'DamaDesktopKey' => null,
            'Imagem' => null,
            'SubTipo' => null,
            'Clinica_id' => null,
            'Funcao' => null
        );
    }

    private function insertExameDCM_ECG($file, $file_name, $dama_desktop_key, $params){

        $dicom = Dicom::getInstance($this->storePath($file));
        $dicom = Self::$DICOM->getInstance($this->getStore($file));

        $dicom->parse(array('PatientName'));        // 0x0010,0x0010
        $dicom->parse(array('AcquisitionDate'));	// 0x0008,0x0022
        $dicom->parse(array('PatientID'));			// 0x0010,0x0020
        $dicom->parse(array('PatientBirthDate'));   // 0x0010,0x0030
        $dicom->parse(array('PatientAge'));         // 0x0010,0x1010
        $dicom->parse(array('PatientSex'));         // 0x0010,0x0040
        $dicom->parse(array('PatientSize'));        // 0x0010,0x1020
        $dicom->parse(array('PatientWeight'));      // 0x0010,0x1030

        $exame = $this->getObject();
        $exame->Tipo        = 'ECG';
        $exame->Data        = $this->toDateAAAAMMDD($dicom->value(0x0008, 0x0022));
        $exame->Paciente    = $dicom->value(0x0010, 0x0010);
        $exame->DataNasc    = $this->toDateAAAAMMDD($dicom->value(0x0010, 0x0030));
        $exame->Idade       = $dicom->value(0x0010,0x1010);
        $exame->DataNasc    = $this->getNasc($exame->Data, $exame->Idade);
        $exame->Sexo        = $dicom->value(0x0010, 0x0040);
        $exame->Altura      = $dicom->value(0x0010, 0x1020) / 100;
        $exame->Peso        = $dicom->value(0x0010, 0x1030);
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);
        $exame->Empresa     = ($params && $params['empresa']) ? $params['empresa'] : null;
        $exame->Motivo      = null;
        $exame->Medico      = ($params && $params['medico']) ? $params['medico'] : null;
        $exame->Arquivo     = $file_name;
        $exame->RG          = null;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Observacao = $params['observacao'];

        $exame->Paciente   = trim(str_replace('^', ' ', $exame->Paciente));
        $exame->Paciente   = $this->str_remove($exame->Paciente, "[^A-Za-z0-9 \.\-]"); //$this->remove_accents($exame->Paciente);

        $exame->Clinica_id = $params['clinica_id'];

        $this->insertExame($exame, $params);

    }

    private function getIMC($peso, $altura) {
        if (!$peso && !$altura) return 0;
        try {
            $peso = (float)$peso; $altura = (float)$altura;
            $altura = $altura > 100 ? $altura / 100 : $altura;
            if ($altura == 0) return 0;
            $imc = round($peso / ($altura * $altura), 2);
            return $imc;
        } catch (Exception $e){}
        return 0;
    }


    function getArquivoIdv2($name) {
        $str =  basename($name);
        $fim = strpos($str, ".");
        if ($fim === false) return $str;
        return substr($str, 0, $fim);
    }

    private function getArquivoId($name){
        if ($name == null) return null;
        $str = strtolower($name);
        $fim = strpos($str, ".jpg");
        if ($fim === false) $fim = strpos($str, ".wxml");
        if ($fim === false) $fim = strpos($str, ".dcm");
        if ($fim === false) $fim = strpos($str, ".zip");
        if ($fim === false) return null;
        $inicio = strrpos($str, "/");
        if ($inicio === false) $inicio = 0;
        else $inicio++;
        return substr($str, $inicio, $fim - $inicio);
    }

    private function insertIMAGENS($data){
        $path = $data['file_path'] . 'imagens/';
        if (!is_dir($path)){
            mkdir($path, 0777);
        }

        $zip  = new \ZipArchive();
        if ($zip->open($this->storePath($data['full_path'])) == TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('/(\.jpg|\.JPG)/', $name) !== 1) continue;
                $zip->extractTo($this->storePath($path), $name);
                $this->insertIMAGEM($path, $name);
            }
        }
    }

    private function getExameByArquivoId($arquivo_id) {
        $exame = Exame::where(['arquivo_id' => $arquivo_id])->get()->sortByDesc('id')->first();
        if ($exame) {
            $exame->arquivo_id = $this->getArquivoIdv2($exame->arquivo_exame);
            $exame->save();
            return $exame;
        }
        $arquivo_id = strtolower($arquivo_id);
        $exame = Exame::where(['arquivo_id' => $arquivo_id])->get()->sortByDesc('id')->first();
        if ($exame) {
            $exame->arquivo_id = $this->getArquivoIdv2($exame->arquivo_exame);
            $exame->save();
            return $exame;
        }
        $query = "LOWER(`arquivo_exame`) LIKE ?";
        $search = '%' . $arquivo_id . '%';
        $exame = Exame::whereRaw($query, [$search])->get()->sortByDesc('id')->first();
        if ($exame) {
            $exame->arquivo_id = $this->getArquivoIdv2($exame->arquivo_exame);
            $exame->save();
            return $exame;
        }
        return null;
    }

    private function insertIMAGEM($path, $name) {
        $arquivo_id = $this->getArquivoId($name);
        $exame = $this->getExameByArquivoId($arquivo_id);
        if (!$exame) return;
        $exame->arquivo_imagem = Self::$PATH_LOTES . 'imagens/' . $name;
        $exame->imagem_date = date("Y-m-d H:i:s");
        $exame->updated_at = $exame->imagem_date;
        $exame->save();
    }

    private function toDate($v = null){
        if ($v == null) {
            $datetime = new \DateTime();
            return $datetime->format('d/m/Y');
        }
        $v = preg_replace('/[^0-9]/', '', $v);
        if (empty($v)) return  '';

        $date = strlen($v) == 8 ?
            substr($v, 0, 2) . '/' .  substr($v, 2, 2) . '/' . substr($v, 4) :
            substr($v, 0, 2) . '/' .  substr($v, 2, 2) . '/' . $this->getAno(substr($v, 4, 2));

        return $date;
    }

    private function getAno($ano) {
        $hoje = intval((new \DateTime())->format('Y'));
        return ( intval('20' . $ano ) > $hoje ? '19' . $ano : '20' . $ano);
    }

    private function toDateAAAAMMDD($date){
        $aa = substr($date, 0, 4);
        $mm = substr($date, 4, 2);
        $dd = substr($date, 6, 2);
        return $this->toDate($dd . $mm . $aa);
    }

    private function toMMDDAAAA($date){
        $dd = substr($date, 0, 2);
        $mm = substr($date, 3, 2);
        $aa = substr($date, 6, 4);
        return $mm . '/' . $dd . '/' . $aa;
    }

    private function getIdade($dt_nasc){
        $datetime1 = strtotime($this->toMMDDAAAA($dt_nasc));
        $datetime2 = strtotime($this->toMMDDAAAA($this->toDate()));
        $secs = $datetime2 - $datetime1; // == <seconds between the two times>
        return  intval($secs / 86400 / 360);
    }

    private function insertExameDCM($file, $file_name, $dama_desktop_key, $params){

        $dicom = Dicom::getInstance($this->storePath($file));

        $dicom->parse(array('PatientName'));        // 0x0010,0x0010
        $dicom->parse(array('AcquisitionDate'));	// 0x0008,0x0022
        $dicom->parse(array('PatientID'));			// 0x0010,0x0020
        $dicom->parse(array('PatientBirthDate'));   // 0x0010,0x0030
        $dicom->parse(array('PatientSex'));         // 0x0010,0x0040
        $dicom->parse(array('Modality'));           // 0x0008,0x0060

        $tipoExame = trim($dicom->value(0x0008,0x0060));

        if ($tipoExame == "EC") {
            $this->insertExameDCM_ECG($file, $file_name, $dama_desktop_key, $params);
            return;
        }

        $isOit = ($params && $params['OIT'] && $params['OIT'] == 'S') ? true : false;

        $exame = $this->getObject();

        $exame->Tipo        = $isOit ? "RAIOX_OIT" : ($this->isOIT($dicom) ? 'RAIOX_OIT' : 'RAIO');
        $exame->SubTipo     = $params['subtipo'];
        $exame->Data        = $this->toDateAAAAMMDD($dicom->value(0x0008, 0x0022));
        $exame->Paciente    = $dicom->value(0x0010, 0x0010);
        $exame->DataNasc    = $this->toDateAAAAMMDD($dicom->value(0x0010, 0x0030));
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = $dicom->value(0x0010, 0x0040);
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;
        $exame->Empresa     = ($params && $params['empresa']) ? $params['empresa'] : null;
        $exame->Motivo      = null;
        $exame->Medico      = ($params && $params['medico']) ? $params['medico'] : null;
        $exame->Arquivo     = $file_name;

        if ($dicom->value(0x0008,0x0070) == 'KONICA MINOLTA') {
            $exame->RG  = $this->onlyDigits($dicom->value(0x0010, 0x0020));
            $exame->CPF = $this->onlyDigits($dicom->value(0x0010, 0x0020));
        }

        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Observacao = $params['observacao'];

        $exame->Paciente   = trim(str_replace('^', ' ', $exame->Paciente));
        $exame->Paciente   = $this->str_remove($exame->Paciente, "[^A-Za-z0-9 \.\-]");

        $exame->Clinica_id = $params['clinica_id'];

        $this->insertExame($exame, $params);

    }

    private function getMotivoExameId($id) {
        if ($this->IsNullOrEmptyString($id)) return Self::$ID_MOTIVO_EXAME_PADRAO;
        $motivo = MotivoExame::where([
            ['id', '=', $id],
            ['empresa_id', '=', Self::$ID_EMPRESA_MATRIZ]
        ])->first();
        return $motivo ? $motivo->id : Self::$ID_MOTIVO_EXAME_PADRAO;
    }

    private function getImageFromDCM($nome) {
        if (preg_match('/(\.dcm)/', strtolower($nome)) == 1) {
            return $this->DCM2JPG($nome);
        }
    }

	private function getImageFromFile($nome, $pagina = 1){
        if (preg_match('/(\.pdf)/', strtolower($nome)) == 1) {
			if (self::PDF2PNG($nome, $pagina)){
                $nome_antigo = str_replace('.pdf', '.png', strtolower($nome));
				$nome_novo = str_replace(' ', '_', $nome_antigo);
				rename(
					$this->storePath($nome_antigo),
					$this->storePath($nome_novo)
				);
                self::SHORTPNG($nome_novo);
				return $nome_novo;
			}
			return null;
		}
		return null;
	}

    private function geraQrCode($url, $name) {
        if (!$name) return null;
        $app = $this->storePath('/utils/qrcode.jar');
        $name = '/uploads/assets/'.$name.".png";
        $cmd = "java -jar " . $app . " " . $url . " " . $this->storePath($name) . " 80";
        $ret = array();
        Log::info("======================= QRCODE ===========================");
        Log::info($cmd);
        Log::info("----------------------------------------------------------");
		exec($cmd, $ret);
        return $name;
    }

	private function PDF2PNG($name, $pagina = 1){
		if (!$name) return false;
        $app = $this->storePath('/utils/pdf2png.jar');
		$cmd = "java -jar " . $app . " \"" . $this->storePath($name) . "\"" . " " . $pagina;
        $ret = array();
		exec($cmd, $ret);
		foreach($ret as $r){
			if ($r == "SUCESSO") return true;
		}
		return false;
	}

    private function SHORTPNG($name) {
		if (!$name) return false;
        $temp = $name . "_short";
        $app = $this->storePath('/utils/pngshort.jar');
		$cmd = "java -jar " . $app . " \"" . $this->storePath($name) . "\"" . " \"" . $this->storePath($temp) . "\"";
        Log::Debug($cmd);
        $ret = array();
		exec($cmd, $ret);
        if (!file_exists($this->storePath($temp))) return;
        unlink($this->storePath($name));
        rename(
            $this->storePath($temp),
            $this->storePath($name)
        );
    }

    private function getCRCFields($lote){
        $dt_nasc = '';
        if ($lote->Tipo != 'ESPIRO') $dt_nasc = str_replace(' ', '', strtoupper(trim($lote->DataNasc))) . '#';
		if ($lote->Tipo == 'EEG') { $lote->Peso = 0; $lote->Altura = 0; }
		$sub_tipo = $lote->SubTipo ? strtoupper(trim($lote->SubTipo)) : "";
		$sub_tipo = strlen($sub_tipo) > 0 ? $sub_tipo . '#' : "";
        $clinica = $this->getClienteDoExame($lote);
		$clinica_id = $clinica = null ? "" : $clinica->id;
        $ret = str_replace(' ', '', strtoupper(trim($lote->Tipo))) . '#' .
			   $sub_tipo .
               str_replace(' ', '', strtoupper(trim($lote->Motivo))) . '#' .
               str_replace(' ', '', strtoupper(trim($lote->Paciente))) . '#' .
               $dt_nasc .
               str_replace(' ', '', strtoupper(trim($lote->Idade))) .  '#' .
               str_replace(' ', '', strtoupper(trim($lote->Sexo))) .  '#' .
               str_replace(' ', '', strtoupper(trim($lote->Altura))) .  '#' .
               str_replace(' ', '', strtoupper(trim($lote->Peso))) .  '#' .
               str_replace(' ', '', strtoupper(trim($lote->Empresa))) . '#' .
			   $clinica_id;
        return $ret;
    }

    private function getProtocolo($id) {
		$protocolo = "";
		for ($t=0; $t < 2; $t++) {
			$alfa = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$letras = "";
			for ($i = 0; $i < strlen($id); $i++) {
				$l = substr($alfa, rand(0, 25), 1);
				if (rand(0, 1) == 1) $l = strtolower($l);
				$letras .= $l;
			}
			for ($i = 0; $i < strlen($id); $i++) {
				if (rand(0, 1) == 1) $protocolo .= substr($id, $i, 1);
				else $protocolo .= substr($letras, $i, 1);
			}
		}
		return strtoupper($protocolo);
	}

    private function isChecked($value) {
        return $value == Self::$MARCADO;
    }

    private function getTipoExame($empresa_id, $tipo_exame_id) {
        $tipoExame = TipoExame::where([
            'empresa_id' => $empresa_id,
            'id' => $tipo_exame_id
        ])->first();
        return $tipoExame;
    }

    private function setLaudoRapidoOuEmergencia($cliente, $exame_id) {
        $tipoExame = $this->getTipoExame(Self::$ID_EMPRESA_MATRIZ, $exame_id);
        if (!$cliente || !$tipoExame) {
            return Self::$LAUDO_NORMAL;
        }
        $condicao = Self::$LAUDO_NORMAL;
        if ($this->isChecked($cliente->laudo_rapido) && $this->isChecked($tipoExame->laudo_rapido)) {
            $condicao = Self::$LAUDO_RAPIDO;
        }
        if ($this->isChecked($cliente->emergencia) && $this->isChecked($tipoExame->emergencia)) {
            $condicao = Self::$LAUDO_EMERGENCIA;
        }
        return $condicao;
    }

    private function IsNullOrEmptyString($str){
        return (!isset($str) || $str === null || trim($str) === '' || $str == 'null');
    }

    private function getDocumentoPaciente($lote) {
        if (!$this->IsNullOrEmptyString($lote->RG)) {
            return preg_replace( '/[^0-9]/', '', $lote->RG);
        }
        if (!$this->IsNullOrEmptyString($lote->CPF)) {
            return preg_replace( '/[^0-9]/', '', $lote->CPF);
        }
        return null;
    }

    private function parse_date_to_mysql($data){
        $_ = explode("/", $data);
        if (count($_) != 3){
            $_ = explode("-", $data);
        }
        if (count($_) != 3){
            return false;
        }

        $mysql_date = @implode("-", array_reverse($_));

        if (@checkdate($_[1], $_[0], $_[2])){
            return $mysql_date;
        }
        return false;
    }

    private function getDataNascPaciente($lote) {
        return $lote->DataNasc ?
            $this->parse_date_to_mysql($lote->DataNasc) :
            $this->parse_date_to_mysql($this->getNasc($lote->Data, $lote->Idade));
    }

    private function getPaciente($lote) {
        if ($this->IsNullOrEmptyString($lote->Paciente)) return null;
        $documento = $this->getDocumentoPaciente($lote);
    }

    private function registerPaciente($lote) {
        $documento = $this->getDocumentoPaciente($lote);
        if ($this->IsNullOrEmptyString($documento)) return;
        $paciente = Paciente::where('rg', $lote->RG)->orWhere('cpf', $lote->CPF)->first();
        if ($paciente) return;
        $paciente = new Paciente();
        $paciente->nome = mb_convert_case($lote->Paciente, MB_CASE_UPPER);
        $paciente->nascimento = $this->getDataNascPaciente($lote);
        $paciente->rg = $lote->RG;
        $paciente->cpf = $lote->CPF;
        $paciente->sexo = $lote->Sexo;
        $paciente->empresa_id = Self::$ID_EMPRESA_MATRIZ;
        $paciente->save();
    }

    private function getClienteByMedicoSolicitantePorNome($clienteId, $nome) {
        if (empty(trim($nome))) return $clienteId;
        $medico = Medico::where('nome', strtoupper($nome))->first();
        if (!$medico) return $clienteId;
        $cliente = Cliente::where('id', $medico->solicitante)->first();
        if (!$cliente) return $clienteId;
        return $cliente->id;
    }

    private function insertExame($lote, $params) {
        $cliente = $this->getClienteDoExame($lote);
        if(!$cliente) throw new ClienteNaoEncontradoException();

        if ($this->IsNullOrEmptyString($lote->Paciente)) return;

        $params = isset($params) ? $params : array('recepcionado' => $cliente->id);

        $exame = new Exame();

        $exame->cliente_id = $this->getClienteByMedicoSolicitantePorNome($cliente->id, $lote->Medico);

        $this->registerPaciente($lote);

        // Paciente ------
        $exame->paciente = mb_convert_case($lote->Paciente, MB_CASE_UPPER);
        $exame->rg = $this->onlyDigits($lote->RG);
        $exame->cpf = $this->onlyDigits($lote->CPF);
        $exame->nascimento = $this->getDataNascPaciente($lote);

        $exame->sexo = $lote->Sexo;
        $exame->funcao = $lote->Funcao;
        $exame->contratante = $lote->Empresa;
        // ---------------

        $exame->medico_solicitante = $lote->Medico;

        $exame->sub_tipo_exame = ($lote->SubTipo && strlen(trim($lote->SubTipo)) > 0) ?
                                  $lote->SubTipo :
                                  $lote->Tipo;

        $tipo_exame = array('ECG' => 1, 'EEG' => 2, 'ESPIRO' => 3, 'RAIO' => 4, 'RAIOX_OIT' => 9);
        $exame->exame_id = $tipo_exame[$lote->Tipo];
        $exame->empresa_id = Self::$ID_EMPRESA_DO_DOMINIO;

        $exame->arquivo_exame = $lote->Arquivo;
        $exame->arquivo_imagem = $lote->Imagem ? $lote->Imagem : $this->getImageFromFile($lote->Arquivo);
        $exame->imagem_date = $exame['arquivo_imagem'] ? date("Y-m-d H:i:s") : null;
        $exame->arquivo_id = $this->getArquivoId($lote->Arquivo);
        $exame->exame_date = $this->parse_date_to_mysql($lote->Data);
        $exame->observacoes = $lote->Observacao ? (string)$lote->Observacao : '';
        $exame->peso = (float)$lote->Peso;
        $exame->altura = (float)$lote->Altura;
        $exame->imc = (float)$lote->IMC;
        $exame->status = Self::$AGUARDANDO_LAUDO;
        $exame->rnd = rand(1, 1000000);
        $exame->crc = crc32($this->getCRCFields($lote));
        $exame->empresa = Self::$LOGIN_EMPRESA_DO_DOMINIO;
        $exame->enviado_por = Self::$TIPO_ENVIO;
        $exame->emergencia = $this->setLaudoRapidoOuEmergencia($cliente, $exame->exame_id);
        $exame->preco_exame = 0;
        $exame->preco_exame_medico = 0;

        $exame->recepcionado = $params['recepcionado'];
        $usuario = Self::$USUARIO_LOGADO;
        $exame->digitado = $usuario ? $usuario->id : 0;

        if ($this->isExameInserido($exame['crc'])) return;

        $exame = $this->setMotivoExameViaLote($lote, $exame);
        $exame->atendimento = Self::$ATENDIMENTO_PADRAO;

        $exame->save();
        $exame->protocolo = $this->getProtocolo($exame->id);
        $exame->save();

    }

    private function setMotivoExameViaLote($lote, $exame) {
        $motivo = MotivoExame::where('id', trim($lote->Motivo))->first();
        $exame->motivo_id = $motivo ? $motivo->id : Self::$NAO_INFORMADO;
        return $exame;
    }

    private function stripAccents($str) {
        $str = strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
        $str = str_replace('?', '_', $str);
        return $str;
    }

    private function renamePdf($name, $raw_name){
        $n = strtolower($name);
        $pos = strpos($n, '.pdf');
        if ($pos === false) return $name;
        return $raw_name.substr($name, $pos);
    }

    private function renameDCM($name){
        $n = strtolower($name);
        $pos = strpos($n, '#dcm');
        if ($pos === false) return $name;
        return str_replace('#dcm', '.dcm', $name);
    }

    private function normalizeFileNames($data) {
        $zipName = $data['full_path'];
        $raw_name = $data['raw_name'];
        $zip  = new \ZipArchive();
        if ($zip->open($this->storePath($zipName)) == TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {

                $zip->renameIndex($i,
                    $this->renamePdf(
                        $this->stripAccents($zip->getNameIndex($i)),
                        $raw_name . '_' . $i
                    )
                );

                $zip->renameIndex($i,
                    $this->renameDCM(
                        $zip->getNameIndex($i)
                    )
                );

            }
            $zip->close();
        }
    }

    private function toDateDDMMAAAA($dd, $mm, $aa){
        $dd = $dd < 10 ? '0' . $dd : $dd;
        $mm = $mm < 10 ? '0' . $mm : $mm;
        return $this->toDate($dd . $mm . $aa);
    }

    private function toDateDDMMAA($date){
        $dd = substr($date, 0, 2);
        $mm = substr($date, 2, 2);
        $aa = '20' . substr($date, 4, 2);
        return $this->toDate($dd . $mm . $aa);
    }

    private function getBytes($file){
        if (!file_exists($file)) return null;
        $handle = fopen($file, "rb");
        $data = fread($handle, filesize($file));
        fclose($handle);
        return $data;
    }

	private function getDICOMTipoExame($data){
		$tipoExame = $this->str_remove($data, "[^A-Z0-9]");
		$tipoExame = str_replace("LAT", "", $tipoExame);
		$tipoExame = str_replace("FRN", "", $tipoExame);
		$tipoExame = str_replace("AP", "", $tipoExame);
		if ($tipoExame == "PA") $tipoExame = "TORAX";
		$tipoExame = str_replace("PA", "", $tipoExame);
		$tipoExame = str_replace("PERFIL", "", $tipoExame);
		$tipoExame = str_replace("MENTO", "", $tipoExame);
		$tipoExame = str_replace("FRONTO", "", $tipoExame);
		$tipoExame = str_replace("OBL", "", $tipoExame);
		return $tipoExame;
    }

    private function getString($bytes, $start = 0, $stop = null){
        if ($bytes == null) return '';
        $str = '';
        for($i = $start; $i < strlen($bytes); $i++) {
            $char = $bytes[$i];
            if ($stop == null) {
                if (ord($char) == null) return $str;
            } else if ($i > $stop) return $str;
            $str .= $this->parse_char($char);
        }
        return $str;
    }

    private function parse_char($char){
        if (ord($char) == null) return '';
        return html_entity_decode('&#' . ord($char) . ';');
    }

    private function getAltura($v){
        $v = preg_replace('/[^0-9]/', '', $v);
        $v = (int)$v;
        return $v > 100 ? $v / 100 : $v;
    }

    private function getOuterLeft($target){
        $pos = strpos($target, '[');
        if (!$pos) return $target;
        return substr($target, 0, $pos);
    }

    private function getOuterRight($target){
        $pos = strpos($target, ']');
        if (!$pos) return "";
        return substr($target, $pos + 1);
    }

    private function getInner($target){
        $pos_a = strpos($target, '[');
        $pos_b = strpos($target, ']');
        if ($pos_a === false || $pos_b == false) return "";
        return substr($target, $pos_a + 1, $pos_b - $pos_a - 1);
    }

    private function getInlineText($file){
        $handle = fopen($file, "r");
        if (!$handle) return '';
        $ret = '';
        while (($line = fgets($handle)) !== false)
            $ret .= $this->getString(trim($line));
        fclose($handle);
        return $ret;
    }

	private function cutStringFromTo($str, $from, $to){
		$posA = strpos($str, $from);
		$posB = strpos($str, $to);
		if ($posA === false || $posB === false) return null;
		$posA += strlen($from);
		$ret = ltrim(rtrim(substr($str, $posA, $posB - $posA)));
		$ret = preg_replace('!\s+!', ' ', $ret);
		return $ret;
	}

    private function cutString($str, $to, $modo = 'alpha', $space = true){
        $pos = strpos($str, $to);
        if ($pos === false) return '';
        $pos += strlen($to);
        $cut = '';
        for ($i = $pos; $i < strlen($str); $i++){
            $char = $str[$i];
            if ($char == ' ') {
                if ($space) {
                    $cut .= $char;
                    continue;
                } else
                    break;
            }
            if ($modo == 'alpha' && preg_match('/^[0-9]$/', $char)) break;
            if ($modo == 'alphanum' && !ctype_alnum($char)) break; //&& !preg_match('/^[a-zA-Z0-9]$/', $char)) break;
            if ($modo == 'numeric') {
                if (!preg_match('/^[0-9]$/', $char) && $char !== '.' && $char !== ',') break;
            }
            $cut .= $char;
        }
        return trim($cut);
    }

	private function getDateAAAAMMDDHHMM($data){
		$data = preg_replace('/[^0-9]/', '', $data);
		$aa = substr($data, 0, 4);
		$mm = substr($data, 4, 2);
		$dd = substr($data, 6, 2);
		return $dd . '/' . $mm . '/' . $aa;
	}

    private function getNomePaciente($nome){
        $nome = ltrim(rtrim(str_replace('-', '', $nome)));
        $nome = str_replace('Nascimento:', '', $nome);
        return $nome;
    }

    private function getClienteByMedicoSolicitante($exame) {
        if (!property_exists($exame, 'Medico')) return null;
        preg_match('/-(.*[0-9]*)/', $exame->Medico, $matches);
        if (count($matches) < 2) return null;
        $id = trim($matches[1]);
        $medico = Medico::where('id', $id)->first();
        if (!$medico || $medico->solicitante == 0) return null;
        $clinica = Cliente::where('id', $medico->solicitante)->first();
        if (!$clinica) throw new ClienteNaoEncontradoException();
        return $clinica;
    }

    private function getClienteDoExame($exame) {
        $clinica = $this->getClienteByMedicoSolicitante($exame);
        if ($clinica) {
            \Log::Debug('cliente obtido por médico solicitante');
            return $clinica;
        }

        if (property_exists($exame, 'Clinica_id')) {
            $clinica = Cliente::where('id', $exame->Clinica_id)->first();
            if ($clinica) {
                \Log::Debug('cliente obtido por Clinica_id do exame');
                return $clinica;
            }
        }

        if(property_exists($exame, 'DamaDesktopKey') && !$this->isNullOrEmptyValue($exame->DamaDesktopKey)) {
            $clinica = Cliente::where('chave_transmissao', $exame->DamaDesktopKey)->first();
            if ($clinica) {
                \Log::Debug('cliente obtido por chave de transmissao');
                return $clinica;
            }
        }
        $usuario = Self::$USUARIO_LOGADO;
        if ($usuario == null) {
            \Log::Debug("cliente não encontrado pois usuario eh nulo na 'sessao'");
            return null;
        }
        $clinica = Cliente::where('id', $usuario->conta_cliente)->first();
        if ($clinica) {
            \Log::Debug('cliente encontrado pela "sessão" ativa');
            \Log::Debug(print_r($clinica, true));
            return $clinica;
        }
        \Log::Debug('cliente não encontrado login não pertence a conta cliente');
        return null;
    }

    private function isVersion($version, $txt){
        $ver = self::getVersion($txt);
        return ($version === $ver);
    }

    private function getVersion($str){
        $str = self::removeCRLF($str);
        $pos = strpos($str, 'Impresso por');
        if ($pos === false) return '';
        $pos += 12;
        $cut = '';
        for ($i = $pos; $i < strlen($str); $i++){
            $char = $str[$i];
            if ($char == '-') {
                break;
            }
            $cut .= $char;
        }
        return str_replace(' ','', $cut);
    }

    private function isEspiro($txt){
        return strpos($txt, "Resultados de Teste de Funções pulmonares") !== false;
    }

    private function removeCRLF($str) {
        $ret = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
			if (ord($char) == 10) $val = ' ';
			else if (ord($char) == 13) $val = '';
			else $val = $char;
            $ret .= $val;
        }
        return $ret;
    }

    private function isWinspiroPRO($txt) {
        $txt = self::removeCRLF($txt);
        $pos = strpos($txt, 'WinspiroPRO');
        return ($pos !== false);
    }

    private function getNome($str){
        $str = preg_replace('/[0-9]+/', '', $str);
        $pos = strpos($str, "\n");
        if ($pos === false) return $str;
        $sobrenome = preg_replace('/\n/', '', substr($str, 0, $pos));
        $nome      = preg_replace('/\n/', '', substr($str, $pos + 1));
        $sobrenome = preg_replace("/ {2,}/", " ", $sobrenome);
        $nome      = preg_replace("/ {2,}/", " ", $nome);
        return preg_replace('/(WSP)/', '', trim($nome) . ' ' . trim($sobrenome));
    }

    private function normalize($str) {
        $ret = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            $ret .= ord($char) == $this->LINEFEED ? ' ' : $char;
        }
        return $ret;
    }

    private function getNomeSobrenome($str) {
        $pos = strpos($str, " ");
        return substr($str, $pos + 1) . ' ' . substr($str, 0, $pos);
    }

    private function getPaciente_WinspiroPRO10501($txt){
        preg_match('/Nome?(.*)Idade/', $txt, $match);
        $nome = trim($match[1]);
        preg_match('/[0-9]{4}.*[0-9]{2}\/[0-9]{2}\/[0-9]{4} [Feminino|Masculino]/', $txt, $match);
        $sobrenome = trim($match[0]);
        $sobrenome = preg_replace('/[0-9]{2}\/[0-9]{2}\/[0-9]{4} [Feminino|Masculino]/', '', $sobrenome);
        $sobrenome = preg_replace('/[0-9]{4}\-\s\p{L}*|\s/', ' ', $sobrenome);
		$nome = $nome . $sobrenome;
		$nome = preg_replace('/[0-9]+/', '', $nome);
        return $nome;
    }

    private function getIdadeDDMMAAAA($dt_nasc){
		$dt_nasc = str_replace('/', '-', $dt_nasc);
		$dt_nasc = date('Y-m-d', strtotime($dt_nasc));
		$today = date('Y-m-d', time());
        return  $today - $dt_nasc;
    }

    function doInsert($full_path, $file_name, $dama_desktop_key, $params = null) {

        $f = strtolower($full_path);

        if (preg_match('/(\.dcm|\.oit)/', $f) == 1){
            $this->insertDCM($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.wxml)/', $f) == 1){
            $this->insertWXML($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.xml)/', $f) == 1){
            $this->insertXML($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.datest)/', $f) == 1){
            $this->insertDATEST($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.mdt)/', $f) == 1){
            $this->insertMDT($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.dte)/', $f) == 1){
            $this->insertDTE($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.dat)/', $f) == 1){
            $this->insertDAT($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.plg)/', $f) == 1){
            $this->insertPLG($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.tep)/', $f) == 1){
            $this->insertTEP($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.eeg)/', $f) == 1){
            $this->insertEEG($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.pdf)/', $f) == 1){
            $this->insertPDF($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }
        if (preg_match('/(\.txt)/', $f) == 1){
            $this->insertTXT($full_path, $file_name, $dama_desktop_key, $params);
            return true;
        }

        return false;

    }

    function insertDCM($file, $file_name, $dama_desktop_key, $params) {
        $isOit = preg_match('/(\.oit)/', strtolower($file)) == 1;
        if ($isOit) {
			$file_name = substr($file_name, 0, strlen($file_name) - 4) . '.dcm';
			$new_file = substr($file, 0, strlen($file) - 4) . '.dcm';
			rename($file, $new_file);
			$file = $new_file;
        }

        $this->DCM2JPG($file);
        $imagem = substr($file, 0, strlen($file) - 4) . '.jpg';

        $dicom = Dicom::getInstance($this->storePath($file));
        $dicom->parse(array('PatientName'));
		$dicom->parse(array('AcquisitionDate'));
		$dicom->parse(array('PatientID'));
		$dicom->parse(array('PatientBirthDate'));
		$dicom->parse(array('PatientSex'));
		$dicom->parse(array('StudyDescription'));
		$dicom->parse(array('SeriesDescription'));
		$dicom->parse(array('AcquisitionDeviceProcessingDescription'));
		$dicom->parse(array('Modality'));
		$dicom->parse(array('PatientComments'));
        $isOit = $isOit ? true : ($this->isOIT($dicom) ? true : false);
        $tipoExame = trim($dicom->value(0x0008,0x0060));
		if ($tipoExame == "EC") {
			$params['imagem'] = $imagem;
			$this->insertExameDCM_ECG($file, $file_name, $dama_desktop_key, $params);
			return;
		}

		$seriesDescription = trim($dicom->value(0x0008,0x103E));
		if (strlen($seriesDescription) == 0) $seriesDescription = trim($dicom->value(0x0018,0x1400));
		$seriesDescription = strtoupper($seriesDescription);
		$seriesDescription = $this->str_remove($seriesDescription, "[^A-Z0-9 ]");

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Tipo        = $isOit ? 'RAIOX_OIT' : 'RAIO';
		$exame->SubTipo     = $this->getDICOMTipoExame($seriesDescription);
        $exame->Data        = $this->toDateAAAAMMDD($dicom->value(0x0008, 0x0022));
        $exame->Paciente    = $dicom->value(0x0010, 0x0010);
        $exame->DataNasc    = $this->toDateAAAAMMDD($dicom->value(0x0010, 0x0030));
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = $dicom->value(0x0010, 0x0040);
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;
        $exame->Empresa     = null;
        $exame->Motivo      = null;
        $exame->Medico      = null;
        $exame->Observacao  = $seriesDescription;
        $exame->Arquivo     = $file;

        if ($dicom->value(0x0008,0x0070) == 'KONICA MINOLTA') {
            $exame->RG  = $dicom->value(0x0010, 0x0020);
            $exame->CPF = $dicom->value(0x0010, 0x0020);
        }
        $exame->Imagem = $imagem;
        $exame->DamaDesktopKey = $dama_desktop_key;
		$exame->Paciente   = trim(str_replace('^', ' ', $exame->Paciente));
		$exame->Paciente   = $this->str_remove($exame->Paciente, "[^A-Za-z0-9 \.\-]");
        $this->insertExame($exame, $params);
    }

    function insertWXML($file, $file_name, $dama_desktop_key, $params) {

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $xml = simplexml_load_file($this->storePath($file));

        $exame->Tipo        = 'ECG';
        $exame->Motivo      = (string)$xml->Paciente->RegistroClinico;
        $exame->Empresa     = (string)$xml->Paciente->Endereco;
        $exame->Data        = (string)$xml->Exame->Registros->Registro->Data;
        $exame->Paciente    = (string)$xml->Paciente->Nome;
        $exame->DataNasc    = $this->toDate($xml->Paciente->DataNascimento);
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = (string)$xml->Paciente->Sexo;
        $exame->Altura      = (string)$xml->Exame->Altura / 100;
        $exame->Peso        = (string)$xml->Exame->Peso;
        $exame->IMC         = (string)$xml->Exame->IMC;
        $exame->Medico      = (string)$xml->Exame->Medicos->Solicitante->Nome;
        $exame->CRM         = (string)$xml->Exame->Medicos->Solicitante->CRM;
        $exame->Observacao  = (string)$xml->Exame->Observacoes;
        $exame->RG          = (string)$xml->Paciente->Telefones->Residencial;
        $exame->Arquivo     = $file_name;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;
        $this->insertExame($exame, $params);
    }

    function insertXML($file, $file_name, $dama_desktop_key, $params) {

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $xml = simplexml_load_file($this->storePath($file));

        $peso = 0; $altura = 0;
        try { $altura = $xml->Paciente->Altura / 100; } catch (\Throwable $th) { $altura = 0; }
        try { $peso = $xml->Paciente->Peso; }  catch (\Throwable $th) { $peso = 0; }

        $exame->Tipo        = 'EEG';
        $exame->Data        = (string)$xml->Paciente->DataExame;
        $exame->Paciente    = (string)$xml->Paciente->Nome;
        $exame->DataNasc    = (string)$xml->Paciente->DataNasc;
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = substr($xml->Paciente->Sexo, 0, 1);
        $exame->Altura      = (string)$altura;
        $exame->Peso        = (string)$peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);
        $exame->Medico      = (string)$xml->Paciente->Medico;
        $exame->Observacao  = (string)$xml->Paciente->Obs;
        $exame->RG          = (string)$xml->Paciente->Fone;
        $exame->CPF         = (string)$xml->Paciente->Celular;
        $exame->Arquivo     = $file_name;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;
        $exame->Empresa = (string)$xml->Paciente->Email;
        $exame->Motivo  = (string)$xml->Paciente->Indicacao;

        $this->insertExame($exame, $params);

    }

    function insertDATEST($file, $file_name, $dama_desktop_key, $params) {
        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $xml = simplexml_load_file($this->storePath($file));

        $exame->Tipo        = 'EEG';
        $exame->Motivo      = (string)$xml->convenio;
        $exame->Data        = $this->toDateDDMMAAAA($xml->dia, $xml->mes, $xml->anio);
        $exame->Paciente    = $xml->nombres . ' ' . $xml->apellido;
        $exame->DataNasc    = null;
        $exame->Idade       = (string)$xml->edad;
        $exame->Sexo        = (string)$xml->sexo;
        $exame->Altura      = (string)$this->getAltura($xml->altura);
        $exame->Peso        = (string)$xml->peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);
        $exame->Medico      = (string)$xml->doctor;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;
        $exame->RG          = (string)$xml->rg;
        $exame->CPF         = (string)$xml->cpf;
        $exame->Empresa     = (string)$xml->empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

    }

    function insertMDT($file, $file_name, $dama_desktop_key, $params) {
        $xml = $this->getBytes($this->storePath($file));
        $start = strpos($xml, "<?xml");
        $stop  = strpos($xml, "</datest>");
        $xml = substr($xml, $start, $stop + 9 - $start);

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $xml=simplexml_load_string($xml);

        $exame->Tipo        = 'EEG';
        $exame->Motivo      = (string)$xml->convenio;
        $exame->Data        = $this->toDateDDMMAAAA($xml->dia, $xml->mes, $xml->anio);
        $exame->Paciente    = $xml->nombres . ' ' . $xml->apellido;
        $exame->DataNasc    = null;
        $exame->Idade       = (string)$xml->edad;
        $exame->Sexo        = (string)$xml->sexo;
        $exame->Altura      = (string)$this->getAltura($xml->altura);
        $exame->Peso        = (string)$xml->peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);
        $exame->Medico      = (string)$xml->doctor;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;
        $exame->RG          = (string)$xml->rg;
        $exame->CPF         = (string)$xml->cpf;
        $exame->Empresa     = (string)$xml->empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

    }

    function insertDTE($file, $file_name, $dama_desktop_key, $params) {
        $bytes = $this->getBytes($this->storePath($file));

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Tipo        = 'EEG';
        $sobrenome          = $this->getString($bytes, 15);
        $exame->Motivo      = $this->getString($bytes, 162, 162);
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $this->getString($bytes, 36) . ' ' . $this->getString($bytes, 15);
        $exame->DataNasc    = null;
        $exame->Idade       = $this->getString($bytes, 59);
        $exame->Sexo        = $this->getString($bytes, 57);
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;
        $exame->Medico      = $this->getString($bytes, 66);
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;
        $exame->RG          = $this->getString($bytes, 97);
        $exame->CPF         = $this->getString($bytes, 114);
        $exame->Empresa     = $this->getString($bytes, 131);
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

    }

    function insertDAT($file, $file_name, $dama_desktop_key, $params) {

        $bytes = $this->getBytes($this->storePath($file));

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'EEG';
        $exame->Data        = $this->getString($bytes, 172, 181); // offset 172 até 181 -> ele virá assim "04/11/2015"
        $exame->Paciente    = trim($this->getString($bytes, 50, 149)); // offset 50 até TAM 100 caracteres.
        $exame->DataNasc    = $this->getString($bytes, 385, 394); // offset 385 até 394 -> ele virá assim "01/09/1988"
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = $this->getString($bytes, 161, 161); // offset 57 -> ele virá assim "M".
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;
        $paciente           = $exame->Paciente;
        $exame->Paciente    = $this->getOuterLeft($paciente);
        $exame->Motivo      = $this->getOuterRight($paciente);
        $exame->Empresa     = $this->getInner($paciente);
        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

    }

    function insertPLG($file, $file_name, $dama_desktop_key, $params) {
        $bytes = $this->getBytes($this->storePath($file));

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'EEG';
        $exame->Motivo      = $this->getString($bytes, 100, 100);
        $exame->Data        = $this->toDateAAAAMMDD($this->getString($bytes, 206, 213)); // offset 206 até 213 -> ele virá assim "20160303".
        $exame->Paciente    = trim($this->getString($bytes, 12, 41)); //  offset 12 até 41.
        $exame->DataNasc    = $this->toDateAAAAMMDD($this->getString($bytes, 170, 177)); // offset 170 até 177 -> ele virá assim "19940505".
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = null;
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;
        $exame->Medico      = $this->getString($bytes, 224); // offset 224 até 00.
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;
        $exame->Empresa     = $this->getString($bytes, 69);
        $exame->Sexo        = $this->getString($bytes, 203, 203);
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

    }

    function insertTEP($file, $file_name, $dama_desktop_key, $params) {
        $bytes = $this->getBytes($this->storePath($file));

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'ECG';
        $exame->Motivo      = $this->getString($bytes, 1246);
        $exame->Data        = $this->toDateDDMMAA($this->getString($bytes, 10));
        $exame->Paciente    = $this->getString($bytes, 17);
        $exame->DataNasc    = $this->getString($bytes, 1045);
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = substr($this->getString($bytes, 107), 0, 1);
        $exame->Altura      = $this->getAltura($this->getString($bytes, 117));
        $exame->Peso        = $this->getString($bytes, 103);
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = $this->getString($bytes, 58);
        $exame->Observacao  = $this->getString($bytes, 1589) . ' ' . $this->getString($bytes, 860) . ' ' .
                              $this->getString($bytes, 891, 896) . ' ' . $this->getString($bytes, 1246);
        $exame->Arquivo     = $file_name;
        $exame->Empresa     = $this->getString($bytes, 1079);
        $exame->RG          = $this->getString($bytes, 977);
        $exame->CPF         = $this->getString($bytes, 998);
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;
		$exame->Funcao      = $this->getString($bytes, 1058);

        $this->insertExame($exame, $params);

    }

    function insertEEG($file, $file_name, $dama_desktop_key, $params) {

        $bytes = $this->getBytes($this->storePath($file));

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'EEG';
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $this->getString($bytes, 6); // . ' ' . $this->getString($bytes, 37);
        $exame->DataNasc    = $this->toDate($this->getString($bytes, 83, 92)); // offset 83 até 92. -> ela virá assim "04-04-1977".
        $exame->Idade       = $this->getIdade($exame->DataNasc);
        $exame->Sexo        = substr($this->getString($bytes, 108), 0, 1); // offset 108 até 00. -> virá assim "Masculino".
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;

        $sobrenome          = $this->getString($bytes, 37);

        $exame->Empresa     = $this->getInner($sobrenome);
        $exame->Motivo      = $this->getOuterRight($sobrenome);

        if (!$exame->Empresa) $exame->Paciente .= ' ' . $sobrenome;

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;
        $exame->RG          = $this->getString($bytes, 68);
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);
    }

    function insertTXT($file, $file_name, $dama_desktop_key, $params) {
        $txt = $this->getInlineText($file);
        $paciente = self::getNomePaciente(self::cutString($txt, 'Nome:'));
        $idade    = self::cutString($txt, 'Idade:', 'numeric');
        $sexo     = strpos($txt, 'Masculino') !== false ? 'M' : 'F';
        $indicacao = self::cutString($txt, 'Indicação:', 'numeric');
        $empresa   = self::cutString($txt, 'Email:');
        $empresa = str_replace('Indicação:', '', $empresa);
        $empresa = str_replace('Data Exame:', '', $empresa);

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'EEG';
        $exame->Motivo      = $indicacao;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;

        $this->insertExame($exame, $params);
    }

    function insertPDF($file, $file_name, $dama_desktop_key, $params) {

        error_reporting(E_ALL ^ E_DEPRECATED);

        //$txt = (string)(new Parser())->parseFile($file)->getText();

        $pdf = new PdfToText($this->storePath($file));
        $txt = (string)$pdf->Text;

        $file_name = $file;

        if (self::isVersion("WinspiroPRO7.5.1", $txt)){
            self::insertPDF_WinspiroPRO751($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion("WinspiroPRO1.05.8.1", $txt)){
            self::insertPDF_WinspiroPRO10581($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion('WinspiroPRO1.06.4.0', $txt)){
            self::insertPDF_WinspiroPRO10640($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion('WinspiroPRO1.05.9.0', $txt)){
            self::insertPDF_WinspiroPRO10590($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion('WinspiroPRO1.05.4.0', $txt)){
            self::insertPDF_WinspiroPRO10540($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion('WinspiroPRO1.05.0.1', $txt)){
            self::insertPDF_WinspiroPRO10501($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion("WinspiroPRO1.06.5.0", $txt)){
            self::insertPDF_WinspiroPRO10650($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion("WinspiroPRO1.06.7.0", $txt)){
            self::insertPDF_WinspiroPRO10670($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion("WinspiroPRO1.07.3.0", $txt)){
            self::insertPDF_WinspiroPRO10730($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isVersion("WinspiroPRO1.05.1.0", $txt)){
            self::insertPDF_WinspiroPRO10510($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::isEspiro($txt) || self::isWinspiroPRO($txt)){
            self::insertPDF_WinspiroPRO($txt, $file_name, $dama_desktop_key, $params);
            return;
        }
        if (self::insertECG_HFECG($txt, $file_name, $dama_desktop_key, $params)) return;
        if (self::insertECG_General_Hospital($txt, $file_name, $dama_desktop_key, $params)) return;
        if (self::insertECG($pdf, $file_name, $dama_desktop_key, $params)) return;
        if (self::insertPDF_Espiro($txt, $file_name, $dama_desktop_key, $params)) return;
        if (self::insertPDF_COSMED($txt, $file_name, $dama_desktop_key, $params)) return;
        if (self::insertPDF_EEG($txt, $file_name, $dama_desktop_key, $params)) return;
        if (self::insertPDF_CardioBrasil($txt, $file_name, $dama_desktop_key, $params)) return;
        if (self::insertPDF_HW_ECGV6_1036($txt, $file_name, $dama_desktop_key, $params)) return;

        $exame = $this->getObject();
        $this->insertExame($exame, $params);
    }

    private function insertPDF_WinspiroPRO751($txt, $file_name, $dama_desktop_key, $params){
        $this->insertPDF_WinspiroPRO10640($txt, $file_name, $dama_desktop_key, $params);
    }

    private function insertPDF_WinspiroPRO10581($txt, $file_name, $dama_desktop_key, $params){
        $this->insertPDF_WinspiroPRO10640($txt, $file_name, $dama_desktop_key, $params);
    }

    private function insertPDF_WinspiroPRO10510($txt, $file_name, $dama_desktop_key, $params){
        $this->insertPDF_WinspiroPRO10501($txt, $file_name, $dama_desktop_key, $params);
    }

    private function insertPDF_WinspiroPRO($txt, $file_name, $dama_desktop_key, $params){
        $builder = new WinspiroProBuilder();
		$exame = $builder->getExame($txt);
		if ($exame == null) return false;
        $exame->Arquivo = $file_name;
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Imagem  = ($params && $params['imagem']) ? $params['imagem'] : null;
        $this->insertExame($exame, $params);
        return true;
    }

    private function insertPDF_CardioBrasil($txt, $file_name, $dama_desktop_key, $params){
		$builder = new CardioBrasilBuilder();
		$exame = $builder->getExame($txt);
		if ($exame == null) return false;
        $exame->Arquivo = $file_name;
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Imagem  = ($params && $params['imagem']) ? $params['imagem'] : null;
		$this->insertExame($exame, $params);
    }

    private function insertPDF_WinspiroPRO10730($txt, $file_name, $dama_desktop_key, $params){
        $paciente = self::getNome(self::cutString($txt, 'Grupo Paciente'));
        $idade    = self::cutString($txt, 'Idade', 'numeric');
        $sexo     = strpos($txt, 'Masculino') !== false ? 'M' : 'F';
        $peso     = self::cutString($txt, 'kg', 'numeric');
        $altura   = self::cutString($txt, 'cm', 'numeric');

        $empresa = self::cutString($txt, 'EMPRESA:');

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'ESPIRO';
		$exame->SubTipo     = 'ESPIRO OCUPACIONAL';
        $exame->Motivo      = null;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = $this->getAltura($altura);
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);
    }

    private function insertPDF_WinspiroPRO10640($txt, $file_name, $dama_desktop_key, $params){
        $paciente = self::getNome(self::cutString($txt, 'Grupo Paciente'));
        $idade    = self::cutString($txt, 'Idade', 'numeric');
        $sexo     = strpos($txt, 'Masculino') !== false ? 'M' : 'F';
        $peso     = self::cutString($txt, 'kg', 'numeric');
        $altura   = self::cutString($txt, 'cm', 'numeric');

        $empresa = self::cutString($txt, 'EMPRESA:');

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'ESPIRO';
		$exame->SubTipo     = 'ESPIRO OCUPACIONAL';
        $exame->Motivo      = null;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = $this->getAltura($altura);
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

    }

    private function insertPDF_WinspiroPRO10590($txt, $file_name, $dama_desktop_key, $params){
		$_txt = str_replace(PHP_EOL, '', $txt);
        $paciente = self::getNome(self::cutString($txt, 'Grupo Paciente'));
        $idade    = self::cutString($_txt, 'Idade', 'numeric');
        $sexo     = strpos($txt, 'Masculino') !== false ? 'M' : 'F';
        $peso     = self::cutString($txt, 'kg', 'numeric');
        $altura   = self::cutString($txt, 'cm', 'numeric');

        $empresa = self::cutString($txt, 'EMPRESA:');

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'ESPIRO';
		$exame->SubTipo     = 'ESPIRO OCUPACIONAL';
        $exame->Motivo      = null;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = $this->getAltura($altura);
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);
    }

    private function insertPDF_WinspiroPRO10540($txt, $file_name, $dama_desktop_key, $params){

        $txt = self::normalize($txt);

        $sobrenome = self::cutString($txt, 'Paciente', 'alphanum');
        $nome      = self::cutString($txt, 'Nome', 'alpha', false);

        $paciente  = self::getNome($sobrenome . "\n" . $nome);

        $idade = self::cutString($txt, 'Idade', 'numeric');
        $sexo = strpos($txt, 'Masculino') !== false ? 'M' : 'F';

        $data = self::cutString($txt, 'cm', 'numeric');
        $peso  = substr($data, 0, 2);
        $altura  = substr($data, 2);

        $empresa = self::cutString($txt, 'EMPRESA:');
        $motivo =  null;

        $exame = $this->getObject();
        $exame->DamaDesktop_Key = $dama_desktop_key;

        $exame->Tipo        = 'ESPIRO';
		$exame->SubTipo     = 'ESPIRO OCUPACIONAL';
        $exame->Motivo      = $motivo;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = $this->getAltura($altura);
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);
    }

    private function insertPDF_WinspiroPRO10501($txt, $file_name, $dama_desktop_key, $params){

        $txt = self::removeCRLF($txt);

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $paciente = self::getPaciente_WinspiroPRO10501($txt);

        $data = self::cutString($txt, 'cm', 'numeric');
        $peso  = substr($data, 0, 2);
        $altura  = substr($data, 2);
        $idade = self::cutString($txt, 'Idade', 'numeric');

        $sexo = strpos($txt, 'Masculino') !== false ? 'M' : 'F';

        $empresa = self::cutString($txt, 'EMPRESA:');
        $motivo   = null;

        $exame->Tipo        = 'ESPIRO';
		$exame->SubTipo     = 'ESPIRO OCUPACIONAL';
        $exame->Motivo      = $motivo;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = $this->getAltura($altura);
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);
    }

    private function insertPDF_WinspiroPRO10650($txt, $file_name, $dama_desktop_key, $params){
        $this->insertPDF_WinspiroPRO10640($txt, $file_name, $dama_desktop_key);
    }

    private function insertPDF_WinspiroPRO10670($txt, $file_name, $dama_desktop_key, $params){

        $paciente = self::getNome(self::cutString($txt, 'Grupo Paciente'));
        $idade    = self::cutString($txt, 'Idade', 'numeric');
        $sexo     = strpos($txt, 'Masculino') !== false ? 'M' : 'F';
        $peso     = self::cutString($txt, 'kg', 'numeric');
        $altura   = self::cutString($txt, 'cm', 'numeric');

        $empresa = self::cutStringFromTo(self::removeCRLF($txt), 'EMPRESA:', 'FUNÇÃO');
        $motivo =  null;

        $exame = $this->getObject();

        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'ESPIRO';
		$exame->SubTipo     = 'ESPIRO OCUPACIONAL';
        $exame->Motivo      = $motivo;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = $this->getAltura($altura);
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

    }

    private function insertECG_HFECG($txt, $file_name, $dama_desktop_key, $params){

        if (strpos($txt, "HFECG") === false) return false;

		$paciente = $this->getNomeSobrenome(self::cutStringFromTo($txt, "Nome :", "ID :"));
        $idade    = trim(self::cutStringFromTo($txt, "Idade :", "Nasc :"));
        $sexo     = trim(self::cutStringFromTo($txt, "Sexo :", "Idade :"));
        $sexo     = strpos($sexo, 'Fem') === false ? 'M' : 'F';
        $nasc     = trim(self::cutStringFromTo($txt, "Nasc :", "Data do Teste :"));
        $nasc     = str_replace('-', '/', $nasc);

        $data = $this->toDate();
        $pos = strpos($txt, "Data do Teste :");
        if ($pos !== false) {
            $data = substr($txt, $pos+15, 10);
            $data = str_replace('-', '/', $data);
        }

        $exame = $this->getObject();

        $exame->Tipo        = 'ECG';
		$exame->Empresa     = null;
        $exame->Data        = $data;
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $nasc;
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;
        $exame->Medico      = null;
        $exame->Observacao  = null;

        $exame->Arquivo        = $file_name;
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Imagem         = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

        return true;

    }

    private function insertECG_General_Hospital($txt, $file_name, $dama_desktop_key, $params){

        if (strpos($txt, "General Hospital") === false) return false;

		$paciente = $this->getNomeSobrenome(self::cutStringFromTo($txt, "Nome :", "ID :"));
        $idade    = trim(self::cutStringFromTo($txt, "Idade :", "Nasc :"));
        $sexo     = trim(self::cutStringFromTo($txt, "Sexo :", "Idade :"));
        $sexo     = strpos($sexo, 'Fem') === false ? 'M' : 'F';
        $nasc     = trim(self::cutStringFromTo($txt, "Nasc :", "Data do Teste :"));
        $nasc     = str_replace('-', '/', $nasc);

        $data = $this->toDate();
        $pos = strpos($txt, "Data do Teste :");
        if ($pos !== false) {
            $data = substr($txt, $pos+15, 10);
            $data = str_replace('-', '/', $data);
        }

        $exame = $this->getObject();

        $exame->Tipo        = 'ECG';
		$exame->Empresa     = null;
        $exame->Data        = $data;
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $nasc;
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;
        $exame->Medico      = null;
        $exame->Observacao  = null;

        $exame->Arquivo        = $file_name;
        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Imagem         = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

        return true;

    }

    private function insertPDF_EEG($txt, $file_name, $dama_desktop_key, $params){
		$txt    = str_replace(array("\r\n", "\n", "\r"), '', $txt);
		$txt    = preg_replace("/[^A-Za-z0-9 :\/-]/i", "", $txt);

  		$paciente = null; $sexo = null; $idade = null; $altura = null; $peso = null;

		$ver = "EN";

		$id = self::cutStringFromTo($txt, "Name :", "Sex :");

		if (!$id) {
			$ver = "PT";
			$id = self::cutStringFromTo($txt, "Nome :", "Sexo :");
		}

		if (!$id) return false;

		if ($ver == "EN"){
			$paciente = self::cutStringFromTo($txt, "Name :", "Sex :");
			$sexo = self::cutStringFromTo($txt, "Sex :", "Age :");
			$sexo = strtolower($sexo);
			$sexo = $sexo == "male" ? "M" : "F";
			$idade = self::cutStringFromTo($txt, "Age :", "Date :");
			$dataExame = self::cutStringFromTo($txt, "Date :", "ID:");
		} else {
			$paciente = self::cutStringFromTo($txt, "Nome :", "Sexo :");
			$sexo = self::cutStringFromTo($txt, "Sexo :", "Idade:");
			$sexo = strtolower($sexo);
			$sexo = $sexo == "feminino" ? "F" : "M";
			$idade = self::cutStringFromTo($txt, "Idade:", "Data:");
			$dataExame = self::cutStringFromTo($txt, "Data:", "Exame:");
		}

		if (empty($paciente)) return false;

        $paciente = preg_replace('/[^a-zA-Z ]/', '', $paciente);

        $exame = $this->getObject();

        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Tipo        = 'EEG';
        $exame->Motivo      = null;
        $exame->Data        = $this->getDateAAAAMMDDHHMM($dataExame);
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($this->toDate(), $idade);
        $exame->Idade       = trim($idade);
        $exame->Sexo        = $sexo;
        $exame->Altura      = $altura / 100;
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = null;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

		return true;

    }

    private function insertPDF_COSMED($txt, $file_name, $dama_desktop_key, $params){
		$txt = str_replace(array("\r\n", "\n", "\r"), '', $txt);
		$id = strpos($txt, 'COSMED');
		if ($id === false) return false;
		$paciente = self::cutStringFromTo($txt, "Primeiro Nome:", "ID:") . ' ' .
					self::cutStringFromTo($txt, "Sobrenome:", "Primeiro Nome:");
		$dataExame = self::cutStringFromTo($txt, "Data:", "Previs");
		$dataNasc  = self::cutStringFromTo($txt, "Data de nascimento:", "Sexo :");
		$peso = self::cutStringFromTo($txt, "Peso (kg):", "Altura (cm):");
		$altura = self::cutStringFromTo($txt, "Altura (cm):", "BMI (");
		$sexo = self::cutStringFromTo($txt, "Sexo :", "Corre");
		$empresa = self::cutStringFromTo($txt, "Empresa:", "Idade:");

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'ESPIRO';
        $exame->Motivo      = null;
        $exame->Data        = $dataExame;
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $dataNasc;
        $exame->Idade       = $this->getIdadeDDMMAAAA($dataNasc);
        $exame->Sexo        = substr(trim($sexo), 0, 1);
        $exame->Altura      = $altura;
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

		$this->insertExame($exame, $params);

        return true;
    }

    private function insertPDF_Espiro($txt, $file_name, $dama_desktop_key, $params){
		$txt    = str_replace(array("\r\n", "\n", "\r"), '', $txt);
		$txt    = preg_replace("/[^A-Za-z0-9 :\/-]/i", "", $txt);

		$paciente = null; $sexo = null; $idade = null; $altura = null; $peso = null;

		$ver = "EN";

		$id = strpos($txt, 'vital capacity report');

		if ($id === false) {
			$ver = "PT";
			$id = strpos($txt, 'rio de capacidade vital');
		}

		if ($id === false) return false;

		if ($ver == "EN"){
			$paciente = self::cutStringFromTo($txt, "name: ", "Gender:");
			$sexo = self::cutStringFromTo($txt, "Gender:", "Age:");
			$sexo = $sexo == "male" ? "M" : "F";
			$idade = self::cutStringFromTo($txt, "Age:", "Nation:");
			$dataExame = self::cutStringFromTo($txt, "Time:", "FVC:");
			$altura = self::cutStringFromTo($txt, "Height:", "cmWeight:");
			$peso = self::cutStringFromTo($txt, "cmWeight:", "k gTime:");
		} else {
			$paciente = self::cutStringFromTo($txt, "Nome:", "Sexo:");
			$sexo = self::cutStringFromTo($txt, "Sexo:", "Idade:");
			$sexo = strtolower($sexo);
			$sexo = $sexo == "feminino" ? "F" : "M";
			$idade = self::cutStringFromTo($txt, "Idade:", "Nao:");
			$dataExame = self::cutStringFromTo($txt, "Tempo:", "CVF:");
			$altura = self::cutStringFromTo($txt, "Altura:", "cmPeso:");
			$peso = self::cutStringFromTo($txt, "cmPeso:", "kgTempo:");
		}

		if (empty($paciente)) return false;

		$paciente = preg_replace('/[^a-zA-Z ]/', '', $paciente);

        $exame = $this->getObject();

        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Tipo        = 'ESPIRO';
        $exame->Motivo      = null;
        $exame->Data        = $this->getDateAAAAMMDDHHMM($dataExame);
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($this->toDate(), $idade);
        $exame->Idade       = trim($idade);
        $exame->Sexo        = $sexo;
        $exame->Altura      = $altura / 100;
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = null;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

		return true;
    }

    private function insertECG($pdf, $file_name, $dama_desktop_key, $params){
        $ver = $pdf->PdfVersion;
        if ($ver == "1.3") return $this->insertECG13($pdf, $file_name, $dama_desktop_key, $params);
        if ($ver == "1.4") return $this->insertECG14($pdf, $file_name, $dama_desktop_key, $params);
    }

	private function insertECG13($pdf, $file_name, $dama_desktop_key, $params){
		$txt = preg_replace("/[\n\r]/","", $pdf);

        $data  = self::cutStringFromTo($txt, "Rhythm Report", "ID   :");
		$data = preg_replace('/[^0-9]/', '', $txt);
		$data = $this->toDateAAAAMMDD($data);

		$nome = self::cutStringFromTo($txt, "Name : ", "Surname : ") . ' ' .
				self::cutStringFromTo($txt, "Surname : ", "Age : ");

		$idade = self::cutStringFromTo($txt, "Age : ", "yrs.");

		$sexo = self::cutStringFromTo($txt, "Sex : ", "H : ");
		$sexo = substr(strtoupper($sexo),0,1);

		$altura = self::cutStringFromTo($txt, "H : ", "cm");
		$peso = self::cutStringFromTo($txt, "W : ", "kg");

        $exame = $this->getObject();

        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Tipo        = 'ECG';
        $exame->Motivo      = null;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $nome;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = trim($idade);
        $exame->Sexo        = trim($sexo);
        $exame->Altura      = $altura;
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = null;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

		return true;
	}

    private function insertECG14($pdf, $file_name, $dama_desktop_key, $params){
        $data = $pdf->Title;
        $paciente = ""; $idade = ""; $sexo  = ""; $motivo = 1; $empresa = ""; $flag = 0;
        for ($i = 0; $i < strlen($data); $i++){
            $c = substr($data, $i, 1);
            if ($c == '-') break;
            if ($flag == 0) {
                if (is_numeric($c)) $flag = 1;
                else $paciente .= $c;
            }
            if ($flag == 1) {
                if ($c == ' ') continue;
                if (!is_numeric($c)) $flag = 2;
                else $idade .= trim($c);
            }
            if ($flag == 2) {
                if ($c == ' ') continue;
                if (is_numeric($c)) $flag = 3;
                else $sexo .= $c;
            }
            if ($flag == 3) {
                if ($c == ' ') continue;
                $motivo = $c; $flag = 4; continue;
            }
            if ($flag == 4) { $empresa .= $c; }
        }
        if (!is_numeric($idade) || ($sexo !== "M" && $sexo !==  "F")) return false;

        $exame = $this->getObject();

        $exame->DamaDesktopKey = $dama_desktop_key;
        $exame->Tipo        = 'ECG';
        $exame->Motivo      = trim($motivo);
        $exame->Data        = $this->toDate();
        $exame->Paciente    = ltrim(rtrim($paciente));
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = trim($idade);
        $exame->Sexo        = trim($sexo);
        $exame->Altura      = null;
        $exame->Peso        = null;
        $exame->IMC         = null;

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = ltrim(rtrim($empresa));
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

		return true;

    }

    private function insertPDF_HW_ECGV6_1036($txt, $file_name, $dama_desktop_key, $params){
        $t = str_replace(' ', '', self::removeCRLF($txt));
        if (strpos($t, 'EcgV6v1.0.3.6') === false || strpos($t, 'Heartware') === false) return false;

        $txt = self::removeCRLF($txt);

        $pos = strpos($txt, '  -  ');
        if ($pos === false) return false;

        $paciente = substr($txt, 0, $pos);
        $idade    = self::cutString($txt, 'kg, ', 'numeric');
        $sexo     = '';
        $peso     = self::cutString($txt, 'm, ', 'numeric');
        $altura   = self::cutString($txt, '(', 'numeric');

        $empresa = null;
        $motivo =  null;

        $exame = $this->getObject();
        $exame->DamaDesktopKey = $dama_desktop_key;

        $exame->Tipo        = 'ECG';
        $exame->Motivo      = $motivo;
        $exame->Data        = $this->toDate();
        $exame->Paciente    = $paciente;
        $exame->DataNasc    = $this->getNasc($exame->Data, $idade);
        $exame->Idade       = $idade;
        $exame->Sexo        = $sexo;
        $exame->Altura      = $this->getAltura($altura);
        $exame->Peso        = $peso;
        $exame->IMC         = $this->getIMC($exame->Peso, $exame->Altura);

        $exame->Medico      = null;
        $exame->Observacao  = null;
        $exame->Arquivo     = $file_name;

        $exame->Empresa     = $empresa;
        $exame->Imagem      = ($params && $params['imagem']) ? $params['imagem'] : null;

        $this->insertExame($exame, $params);

        return true;
    }

    public function pausar(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $exame = Exame::where('id', $request['body']['exameId'])->first();
        if (!$exame) throw new ExameNaoEncontradoException();
        if ($exame->emergencia != Self::$LAUDO_NORMAL) throw new ExameNaoPodeSerPausadoException();
        $exame->despacho_prazo = $this->getUltimaHoraDoDiaParaLaudar();
        $exame->pausado = date('Y-m-d H:i:s');
        $exame->save();
        return [];

    }

}
