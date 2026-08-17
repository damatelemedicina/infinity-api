<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{

    public static $UNAUTHORIZED = 401; // Não autenticado
    public static $FORBIDDEN    = 403; // Não autorizado
    public static $BAD_REQUEST  = 400; // Erro de sintaxe na requisição

    public static $EMPRESA_NAO_ENCONTRADA = 'Empresa não encontrada!';
    public static $EMPRESA_BLOQUEADA = 'Empresa bloqueada!';
    public static $EMPRESA_INATIVA = 'Empresa inativa!';
    public static $EMPRESA_JA_CADASTRADA = 'Empresa já cadastrada!';
    public static $LOGIN_BLOQUEADO = 'Login bloqueado!';
    public static $LOGIN_INCORRETO = 'Login incorreto!';
    public static $SENHA_INCORRETA = 'Senha incorreta!';
    public static $DISPOSITIVO_NAO_REGISTRADO = 'Dispositivo não registrado!';
    public static $CODIGO_DE_VALIDACAO_INCORRETO = 'Código de validação incorreto!';
    public static $SESSAO_DE_VALIDACAO_EXPIRADA = 'Sessão de validação expirada!';
    public static $SESSAO_NAO_ENCONTRADA = 'Sessão não encontrada!';
    public static $SESSAO_MAL_FORMADA = 'Sessão mal formada!';
    public static $REQUISICAO_MAL_FORMADA = 'Requisição mal formada!';
    public static $SESSAO_INVALIDA = 'Sessão inválida!';
    public static $EXCLUSAO_NAO_PERMITIDA = 'Exclusão não permitida!';
    public static $BLOQUEIO_NAO_PERMITIDO = 'Bloqueio não permitido!';
    public static $CAMPOS_OBRIGATORIOS = 'Campos obrigatórios!';
    public static $PERFIL_NAO_ENCONTRADO = 'Perfil não encontrado!';
    public static $AUTENTICACAO_REQUERIDA = 'Autenticacao requerida!';
    public static $USUARIO_NAO_ENCONTRADO = 'Usuário não encontrado!';
    public static $FICHA_NAO_ENCONTRADA = 'Ficha não encontrada!';
    public static $TIPO_EXAME_NAO_ENCONTRADO = 'Tipo de exame não encontrado!';
    public static $CAMPO_TIPO_EXAME_NAO_ENCONTRADO = 'Campo tipo de exame não encontrado!';
    public static $CLIENTE_NAO_ENCONTRADO = 'Cliente não encontrado!';
    public static $REGISTRO_NAO_ENCONTRADO = 'Registro não encontrado!';
    public static $MEDICO_NAO_ENCONTRADO = 'Médico não encontrado!';
    public static $CHAVE_DE_TRANSMISSAO_NAO_ENCONTRADA = 'Chave de transmissão não encontrada!';
    public static $MOTIVO_DE_EXAME_PADRAO_NAO_ENCONTRADO = 'Motivo de exame padrao não encontrado!';
    public static $EXAME_NAO_ENCONTRADO = 'Exame não encontrado!';
    public static $USUARIO_NAO_ASSOCIADO_A_CLIENTE = 'Usuário não associado a nenhum cliente!';
    public static $USUARIO_NAO_ASSOCIADO_A_MEDICO = 'Usuário não associado a nenhum medico!';
    public static $MEDICO_NAO_ASSOCIADO_A_CLIENTE = 'Médico não associado a nenhum cliente!';
    public static $MOTIVO_DE_IMPOSSIBILIDADE_NAO_ENCONTRADO = 'Motivo de impossibilidade não encontrado!';
    public static $MODELO_NAO_ENCONTRADO = 'Modelo não encontrado!';
    public static $EXAME_NAO_PODE_SER_PAUSADO = 'Exame não pode ser pausado!';
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (EmpresaNaoEncontradaException $e, $request) {
            return response(['erro' => Self::$EMPRESA_NAO_ENCONTRADA], Self::$BAD_REQUEST);
        });

        $this->renderable(function (EmpresaBloqueadaException $e, $request) {
            return response(['erro' => Self::$EMPRESA_BLOQUEADA], Self::$FORBIDDEN);
        });

        $this->renderable(function (EmpresaInativaException $e, $request) {
            return response(['erro' => Self::$EMPRESA_INATIVA], Self::$FORBIDDEN);
        });

        $this->renderable(function (LoginInvalidoException $e, $request) {
            return response(['erro' => Self::$LOGIN_INCORRETO], Self::$UNAUTHORIZED);
        });

        $this->renderable(function (SenhaInvalidaException $e, $request) {
            return response(['erro' => Self::$SENHA_INCORRETA], Self::$UNAUTHORIZED);
        });

        $this->renderable(function (DispositivoNaoRegistradoException $e, $request) {
            return response(['erro' => Self::$DISPOSITIVO_NAO_REGISTRADO], Self::$UNAUTHORIZED);
        });

        $this->renderable(function (LoginBloqueadoException $e, $request) {
            return response(['erro' => Self::$LOGIN_BLOQUEADO], Self::$FORBIDDEN);
        });

        $this->renderable(function (CodigoDeValidacaoIncorretoException $e, $request) {
            return response(['erro' => Self::$CODIGO_DE_VALIDACAO_INCORRETO], Self::$FORBIDDEN);
        });

        $this->renderable(function (SessaoDeValidacaoExpiradaException $e, $request) {
            return response(['erro' => Self::$SESSAO_DE_VALIDACAO_EXPIRADA], Self::$FORBIDDEN);
        });

        $this->renderable(function (SessaoNaoEncontradaException $e, $request) {
            return response(['erro' => Self::$SESSAO_NAO_ENCONTRADA], Self::$BAD_REQUEST);
        });

        $this->renderable(function (SessaoMalFormadaException $e, $request) {
            return response(['erro' => Self::$SESSAO_MAL_FORMADA], Self::$BAD_REQUEST);
        });

        $this->renderable(function (SessaoInvalidaException $e, $request) {
            return response(['erro' => Self::$SESSAO_INVALIDA], Self::$BAD_REQUEST);
        });

        $this->renderable(function (RequisicaoMalFormadaException $e, $request) {
            return response(['erro' => Self::$REQUISICAO_MAL_FORMADA], Self::$BAD_REQUEST);
        });

        $this->renderable(function (ExclusaoNaoPermitidaException $e, $request) {
            return response([ 'erro' => Self::$EXCLUSAO_NAO_PERMITIDA], Self::$FORBIDDEN);
        });

        $this->renderable(function (BloqueioNaoPermitidoException $e, $request) {
            return response([ 'erro' => Self::$BLOQUEIO_NAO_PERMITIDO], Self::$FORBIDDEN);
        });

        $this->renderable(function (EmpresaJaCadastradaException $e, $request) {
            return response([ 'erro' => Self::$EMPRESA_JA_CADASTRADA], Self::$FORBIDDEN);
        });

        $this->renderable(function (CamposObrigatoriosException $e, $request) {
            return response([ 'erro' => Self::$CAMPOS_OBRIGATORIOS], Self::$FORBIDDEN);
        });

        $this->renderable(function (PerfilNaoEncontradoException $e, $request) {
            return response([ 'erro' => Self::$PERFIL_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (UsuarioNaoEncontradoException $e, $request) {
            return response([ 'erro' => Self::$USUARIO_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (TipoExameNaoEncontradoException $e, $request) {
            return response([ 'erro' => Self::$TIPO_EXAME_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (CampoTipoExameNaoEncontradoException $e, $request) {
            return response([ 'erro' => Self::$CAMPO_TIPO_EXAME_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (AutenticacaoRequeridaException $e, $request) {
            return response([ 'erro' => Self::$AUTENTICACAO_REQUERIDA], Self::$UNAUTHORIZED);
        });

        $this->renderable(function (FichaNaoEncontradaException $e, $request) {
            return response([ 'erro' => Self::$FICHA_NAO_ENCONTRADA], Self::$BAD_REQUEST);
        });

        $this->renderable(function (ClienteNaoEncontradoException $e, $request) {
            return response(['erro' => Self::$CLIENTE_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (RegistroNaoEncontradoException $e, $request) {
            return response(['erro' => Self::$REGISTRO_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (MedicoNaoEncontradoException $e, $request) {
            return response(['erro' => Self::$MEDICO_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (ChaveDeTransmissaoNaoEncontradaException $e, $request) {
            return response(['erro' => Self::$CHAVE_DE_TRANSMISSAO_NAO_ENCONTRADA], Self::$BAD_REQUEST);
        });

        $this->renderable(function (MotivoDeExamePadraoNaoEncontradoException $e, $request) {
            return response(['erro' => Self::$MOTIVO_DE_EXAME_PADRAO_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (ExameNaoEncontradoException $e, $request) {
            return response(['erro' => Self::$EXAME_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (UsuarioNaoAssociadoAClienteException $e, $request) {
            return response(['erro' => Self::$USUARIO_NAO_ASSOCIADO_A_CLIENTE], Self::$BAD_REQUEST);
        });

        $this->renderable(function (UsuarioNaoAssociadoAMedicoException $e, $request) {
            return response(['erro' => Self::$USUARIO_NAO_ASSOCIADO_A_MEDICO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (InclusaoDeExameException $e, $request) {
            return response(['erro' => $e->getMessage() ], Self::$BAD_REQUEST);
        });

        $this->renderable(function (MedicoNaoAssociadoAClienteException $e, $request) {
            return response(['erro' => Self::$MEDICO_NAO_ASSOCIADO_A_CLIENTE], Self::$BAD_REQUEST);
        });

        $this->renderable(function (MotivoDeImpossibilidadeNaoEncontradoException $e, $request) {
            return response(['erro' => Self::$MOTIVO_DE_IMPOSSIBILIDADE_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (ModeloNaoEncontradoException $e, $request) {
            return response(['erro' => Self::$MODELO_NAO_ENCONTRADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (ExameNaoPodeSerPausadoException $e, $request) {
            return response(['erro' => Self::$EXAME_NAO_PODE_SER_PAUSADO], Self::$BAD_REQUEST);
        });

        $this->renderable(function (InclusaoDeLaudoException $e, $request) {
            return response(['erro' => $e->getMessage() ], Self::$BAD_REQUEST);
        });

        $this->renderable(function (DespachoException $e, $request) {
            return response(['erro' => $e->getMessage() ], Self::$BAD_REQUEST);
        });

        $this->renderable(function (FinanceiroException $e, $request) {
            return response(['erro' => $e->getMessage() ], Self::$BAD_REQUEST);
        });

        $this->renderable(function (FaturamentoException $e, $request) {
            return response(['erro' => $e->getMessage() ], Self::$BAD_REQUEST);
        });

        $this->renderable(function (CadastroException $e, $request) {
            return response(['erro' => $e->getMessage() ], Self::$BAD_REQUEST);
        });

        $this->renderable(function (RecusaNaoPermitidaException $e, $request) {
            return response(['erro' => $e->getMessage()], Self::$BAD_REQUEST);
        });

        $this->renderable(function (LaudoException $e, $request) {
            return response([ 'erro' => $e->getMessage()], Self::$BAD_REQUEST);
        });

    }
}
