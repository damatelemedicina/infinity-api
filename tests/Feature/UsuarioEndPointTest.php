<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Exceptions\Handler;
use App\Models\Usuario;

class UsuarioEndPointTest extends TestCase
{

    public function test_teste_de_obtencao_do_usuario_logado_com_sessao_valida()
    {

        // Força situação do usuário como ATIVO
        $usuario = Usuario::where('login', 'davi.silveira')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->save();
        
        $response = $this->json('POST', '/getUsuarioLogado', [
            'session' => [
                'login' => 'davi.silveira',
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            'UsuarioLogin' => 'davi.silveira',
            'UsuarioNome' => 'Davi Silveira',
            'EmpresaLogin' => 'DAMA',
            'EmpresaNome' => "Dama Telemedicina"
        ]);

    }

    public function test_teste_de_obtencao_do_usuario_logado_com_sessao_nula()
    {
        $response = $this->json('POST', '/getUsuarioLogado', [
            'session' => null,
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$AUTENTICACAO_REQUERIDA]);
    }

    public function test_teste_de_obtencao_do_usuario_logado_com_sessao_nao_fornecida()
    {
        $response = $this->json('POST', '/getUsuarioLogado', [
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$AUTENTICACAO_REQUERIDA]);
    }

    public function test_teste_de_obtencao_do_usuario_com_login_inexistente()
    {
        $response = $this->json('POST', '/getUsuarioLogado', [
            'session' => [
                'login' => 'xyz',
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);

    }

    public function test_teste_de_obtencao_do_usuario_logado_com_empresa_invalida_para_o_login()
    {
        $response = $this->json('POST', '/getUsuarioLogado', [
            'session' => [
                'login' => 'erika',
            ],
            'origin' => 'http://infinity.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }

    public function test_teste_de_obtencao_do_usuario_logado_porem_sem_dominio()
    {
        $response = $this->json('POST', '/getUsuarioLogado', [
            'session' => [
                'login' => 'erika',
            ]
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);
    }

    public function test_teste_de_obtencao_de_usuarios_com_login_valido()
    {
        $response = $this->json('POST', '/getUsuarios', [
            'session' => [
                'login' => 'thais.lopes',
            ],
            'origin' => 'http://infinity.infinity.local'            
        ], ['Accept' => 'application/json'])
        ->assertStatus(200);
    }

    public function test_teste_de_obtencao_de_usuario_com_login_valido()
    {
        $response = $this->json('POST', '/getUsuario', [
            'session' => [
                'login' => 'thais.lopes',
            ],
            'body' => [
                'id' => 3
            ],
            'origin' => 'http://infinity.infinity.local'            
        ], ['Accept' => 'application/json'])
        ->assertStatus(200);
    }

}
