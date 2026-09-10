<?php

namespace App\Http\Controllers;

use App\Exceptions\BloqueioException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Models\Empresa;
use App\Models\TipoDocumento;
use App\ViewModels\LoginViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoDocumentoController extends Controller
{

    public function getTiposDocumentos(Request $request)
    {
        $this->validarRequisicao($request);
        return TipoDocumento::orderBy("nome")->get(["id", "nome"])->toJson();
    }

    public function getTipoDocumento(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        return TipoDocumento::find($request["body"]["id"])->toArray();
    }

    public function setTipoDocumento(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['session']['login']
        );
        if (!$login) throw new EmpresaNaoEncontradaException();

        if (($login["isContaAdmin"] ?? 0) !== 1) throw new BloqueioException('Você não tem permissão para subir/editar um documento!');

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $data = $request["body"];
        $id = empty($data["id"]) ? null : $data["id"];

        \validator($request['body'], (["id" => (empty($id) ? "" : "integer"), "nome" => "required|string|max:255|min:3|unique:tipos_documentos,nome,{$id}"]))
            ->validate();

        // DB::enableQueryLog();
        DB::transaction(function () use (&$data) {
            /** @var TipoDocumento $post */
            $post = empty($data["id"]) ? new TipoDocumento() : TipoDocumento::find($data["id"]);
            $post->nome = mb_convert_case($data['nome'], MB_CASE_UPPER, "UTF-8");
            $post->save();
            // dd(DB::getQueryLog());
        });

        return ["message" => "Tipo de documento criado com sucesso"];
    }

    public function serverProcessingTipoDocumento(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        return TipoDocumento::serverProcessing();
    }
}
