<?php

namespace App\Http\Controllers;

use App\Exceptions\BloqueioException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\InclusaoDeExameException;
use App\Models\Documento;
use App\Models\DocumentoCliente;
use App\Models\DocumentoMedico;
use App\Models\Empresa;
use App\Models\Usuario;
use App\ViewModels\LoginViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class DocumentoController extends Controller
{
    const COMPARTILHAMENTO_FOLDER_NAME = 'compartilhamento';

    public function setDocumento(Request $request)
    {
        $request = $this->parseRequest($request);

        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['session']['login']
        );
        if (!$login) throw new EmpresaNaoEncontradaException();

        $isContaAdmin = ($login["isContaAdmin"] ?? 0) > 0;

        if (!$isContaAdmin && ($login["isContaMedico"] ?? 0) < 1) throw new BloqueioException('Você não tem permissão para subir/editar um documento!');

        $ContaMedicoId = empty($login["ContaMedicoId"]) ? null : [$login["ContaMedicoId"]];

        $atLeastMedicoOrCliente = function ($attribute, $value, $fail) use ($request, $isContaAdmin) {
            if (!$isContaAdmin && empty($request->cliente_id) && empty($request->medico_id)) {
                $fail('Um dos campos cliente ou medico deve ser informado!');
            }

            if (empty($request->cliente_id) == false && empty($request->medico_id) == false) {
                $fail('Os dois campos cliente e medico não podem ser informados ao mesmo tempo!');
            }
        };

        $id = $request->id;

        $request->validate([
            'tipos_documentos_id' => 'required',
            'nome' => (empty($id) ? '' : 'required'),
            'cliente_id' => $atLeastMedicoOrCliente,
            'medico_id' => $atLeastMedicoOrCliente,
            'files' => (empty($id) ? 'required' : '')
        ], [], [
            'tipos_documentos_id' => 'tipo de documento',
            'nome' => 'documento',
            'files' => 'arquivos'
        ]);

        if (empty($id) && !$request->hasFile('files')) {
            throw new InclusaoDeExameException('Arquivos não informados!');
        }

        $nome = $request->nome;
        $perfil_id = empty($request->perfil_id) ? null : $request->perfil_id;
        if (($login["isContaMedico"] ?? 0) > 0) {
            $perfil_id = 1; // Master
        }

        $funcSaveDocCliMed = function ($id) use ($request, $ContaMedicoId) {
            $cliente_id = \collect($request->cliente_id ?? []);

            DocumentoCliente::where("documento_id", $id)->delete();

            if ($cliente_id->isNotEmpty()) {
                $documento_id = $id;
                $setDocumentoCliente = $cliente_id
                    ->filter(function ($c_id) use ($documento_id) {
                        return DocumentoCliente::where('documento_id', $documento_id)->where('cliente_id', $c_id)->count() == 0;
                    })
                    ->reduce(function ($acc, $cur, $index) use ($documento_id) {
                        $acc[] = ['documento_id' => $documento_id, 'cliente_id' => $cur];
                        return $acc;
                    }, []);
                if (empty($setDocumentoCliente) == false) {
                    DocumentoCliente::insert($setDocumentoCliente);
                }
            }

            $medico_id = \collect($request->medico_id ?? $ContaMedicoId ?? []);

            DocumentoMedico::where("documento_id", $id)->delete();

            if ($medico_id->isNotEmpty()) {
                $documento_id = $id;
                $setDocumentoMedico = $medico_id
                    ->filter(function ($c_id) use ($documento_id) {
                        return DocumentoMedico::where('documento_id', $documento_id)->where('medico_id', $c_id)->count() == 0;
                    })
                    ->reduce(function ($acc, $cur, $index) use ($documento_id) {
                        $acc[] = ['documento_id' => $documento_id, 'medico_id' => $cur];
                        return $acc;
                    }, []);
                if (empty($setDocumentoMedico) == false) {
                    DocumentoMedico::insert($setDocumentoMedico);
                }
            }
        };

        $is_todas_empresas = $request->has('is_todas_empresas');
        $empresa_id = $is_todas_empresas ? null : $login["EmpresaId"];

        $usuario_id = $login['UsuarioId'];
        if (empty($id)) {
            /** @var \Illuminate\Http\UploadedFile $file */
            foreach ($request->file('files') as $key => $file) {
                DB::transaction(function () use ($request, $file, $nome, $key, $perfil_id, $funcSaveDocCliMed, $usuario_id, $empresa_id) {
                    $ext = $file->getClientOriginalExtension();
                    $nameNoExt = \str_replace(".{$ext}", "", $file->getClientOriginalName());

                    $post = new Documento();
                    $post->tipos_documentos_id = $request->tipos_documentos_id;
                    $post->empresa_id = $empresa_id;
                    $post->perfil_id = $perfil_id;
                    $post->usuario_id = $usuario_id;
                    $post->nome = empty($nome) ? $nameNoExt : "{$nome}_" . ($key + 1);
                    $post->filename = "{$post->nome}.{$ext}";
                    $post->uuid = \substr(Uuid::uuid4()->toString(), 0, 36);
                    $post->rawfilename = "{$post->uuid}.{$ext}";
                    $post->save();

                    $funcSaveDocCliMed($post->id);

                    if ($file->storeAs($this->getPath(Self::COMPARTILHAMENTO_FOLDER_NAME), $post->rawfilename, 'uploads') === false) {
                        throw new InclusaoDeExameException('Falha ao salvar arquivo');
                    }
                });
            }
        } else {
            DB::transaction(function () use ($request, $perfil_id, $funcSaveDocCliMed, $usuario_id, $empresa_id, $isContaAdmin) {
                $post = Documento::find($request->id);
                if ($isContaAdmin) {
                    $post->empresa_id = $empresa_id;
                    $post->usuario_id = $usuario_id;
                }
                $post->tipos_documentos_id = $request->tipos_documentos_id;
                $post->perfil_id = $perfil_id;
                $post->nome = $request->nome;
                $post->filename = "{$post->nome}" . substr($post->rawfilename, strlen($post->uuid));
                $post->save();

                $funcSaveDocCliMed($post->id);

                if ($request->hasFile('file')) {
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                    $disk = Storage::disk('uploads');
                    $success = $disk->delete($this->getPath(Self::COMPARTILHAMENTO_FOLDER_NAME) . \DIRECTORY_SEPARATOR . $post->rawfilename);
                    if (!$success) {
                        throw new InclusaoDeExameException('Falha ao excluir arquivo ' . $post->rawfilename);
                    }

                    /** @var \Illuminate\Http\UploadedFile $file */
                    $file = $request->file('file');
                    $ext = $file->getClientOriginalExtension();
                    $post->filename = "{$post->nome}.{$ext}";
                    $post->rawfilename = "{$post->uuid}.{$ext}";
                    $post->save();

                    if ($file->storeAs($this->getPath(Self::COMPARTILHAMENTO_FOLDER_NAME), $post->rawfilename, 'uploads') === false) {
                        throw new InclusaoDeExameException('Falha ao salvar arquivo');
                    }
                }
            });
        }

        return ['message' => 'Operação realizada com sucesso.'];
    }

    public function getDocumento(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $documento = Documento::find($request["body"]["id"])->toArray();
        $documento["clientes_id"] = DocumentoCliente::where('documento_id', $request["body"]["id"])->pluck('cliente_id')->toArray();
        $documento["medicos_id"] = DocumentoMedico::where('documento_id', $request["body"]["id"])->pluck('medico_id')->toArray();
        return $documento;
    }

    public function deleteDocumento(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['session']['login']
        );
        if (!$login) throw new EmpresaNaoEncontradaException();

        if (($login["isContaAdmin"] ?? 0) !== 1) throw new BloqueioException('Você não tem permissão para excluir um documento!');

        DB::transaction(function () use ($request) {
            $id = $request["body"]["id"];
            if (empty($id)) {
                throw new InclusaoDeExameException('ID do documento não informado!');
            }

            $documento = Documento::find($id);
            if (!$documento) throw new InclusaoDeExameException('Documento não encontrado!');

            $documento->delete();

            $success = Storage::disk('uploads')->delete($this->getPath(Self::COMPARTILHAMENTO_FOLDER_NAME) . \DIRECTORY_SEPARATOR . $documento->rawfilename);
            if (!$success) {
                throw new InclusaoDeExameException('Falha ao excluir arquivo ' . $documento->rawfilename);
            }
        });

        return ['message' => 'Operação realizada com sucesso.'];
    }

    function getTempURLDownload(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $data = $request["body"];
        $id = $data["id"];
        if (empty($id)) {
            throw new InclusaoDeExameException('ID do documento não informado!');
        }

        $documento = Documento::find($id);
        if (!$documento) throw new InclusaoDeExameException('Documento não encontrado!');

        $url = Storage::temporaryUrl(
            $documento->uuid,
            now()->addMinutes(5),
        );

        return $url;
    }

    public function serverProcessingDocumento(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['session']['login']
        );
        if (!$login) throw new EmpresaNaoEncontradaException();

        $usuario = Usuario::where('id', $login['UsuarioId'])->first();
        if (!$usuario) throw new BloqueioException('Usuário não encontrado!');

        return Documento::serverProcessing($login['isContaAdmin'], $login['EmpresaId'], $usuario->perfil_id, $usuario->conta_cliente, $usuario->conta_medico);
    }
}
