<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Exceptions\Handler;

use App\Models\Usuario;
use App\Models\Empresa;

class DeviceEndPointTest extends TestCase
{
    private function beforeEach() {
        // força empresa em condição normal
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$ATIVA;
        $empresa->save();
    }

    private function forcaRegistroPrioritario($login = 'thais.lopes')
    {
        
        $this->beforeEach();

        // Força registro prioritário do dispositivo
        $usuario = Usuario::where('login', $login)->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->v2 = Usuario::$V2_ATIVO;
        $usuario->device_id = null;
        $usuario->save();

        // Força registro de hash(H) na base de dados, retornando-o
        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => $login,
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        // Simula login para registro do aparelho, 
        // recebendo hash(R) registrado no passo anterior
        $response = $this->json('POST', '/device/lh', [
            'body' => [
                'H' => $response['H'],
                'usuario' => $login,
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        return $response;

    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_com_login_bloqueado()
    {
        
        $this->beforeEach();

        // Força situacao de usuario bloqueado
        $usuario = Usuario::where('login', 'davi.silveira')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();
        
        //$this->withoutExceptionHandling();
        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'davi.silveira',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$LOGIN_BLOQUEADO]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_com_login_correto()
    {
        
        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->v2 = Usuario::$V2_ATIVO;
        $usuario->save();
        
        $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'Thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_com_login_incorreto()
    {
        
        $this->beforeEach();

        // Força situacao de usuario ativo
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->save();
        
        $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'thais',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_com_senha_incorreta()
    {
        
        $this->beforeEach();

        // Força situacao de usuario ativo
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->save();
        
        $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => 'X'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$SENHA_INCORRETA]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_sem_dominio()
    {
        
        $this->beforeEach();

        // Força situacao de usuario bloqueado
        $usuario = Usuario::where('login', 'davi.silveira')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();
        
        //$this->withoutExceptionHandling();
        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'davi.silveira',
                'senha' => '12345'
            ]
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_com_empresa_incorreta()
    {
        
        $this->beforeEach();

        // Força situacao de usuario ativo
        $usuario = Usuario::where('login', 'vitor.aragone')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->save();
        
        $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'vitor.aragone',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_com_empresa_bloqueada()
    {
        
        $this->beforeEach();

        // Força situacao de empresa bloqueada
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$BLOQUEADA;
        $empresa->save();
        
        $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$EMPRESA_BLOQUEADA]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_com_empresa_inativa()
    {
        
        $this->beforeEach();

        // Força situacao de empresa inativa
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$INATIVA;
        $empresa->save();
        
        $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$EMPRESA_INATIVA]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_prioritario_com_login_bloqueado()
    {
        
        $this->beforeEach();

        $usuario = Usuario::where('login', 'davi.silveira')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->device_id = null;
        $usuario->save();
        
        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'davi.silveira',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$LOGIN_BLOQUEADO]);

    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_prioritario_com_login_correto()
    {
        
        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->v2 = Usuario::$V2_ATIVO;
        $usuario->device_id = null;
        $usuario->save();

        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'Thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        $this->assertGreaterThan(60, strlen($response['H']));

    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_prioritario_com_login_incorreto()
    {
        
        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->device_id = null;
        $usuario->save();
        
        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'thais',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_prioritario_com_senha_incorreta()
    {

        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->device_id = null;
        $usuario->save();

        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => 'X'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$SENHA_INCORRETA]);
    }

    public function test_teste_de_recuperacao_de_qrcode_de_validacao_de_acesso_prioritario_com_sessao_expirada()
    {

        $this->beforeEach();

        // Força registro prioritário do dispositivo
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->device_id = null;
        $usuario->save();

        // Força registro de hash(H) na base de dados, retornando-o
        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'Thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        // Simula login para registro do aparelho sem hash de validacao
        $response = $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$SESSAO_DE_VALIDACAO_EXPIRADA]);

        // Simula login para registro do aparelho com hash(H) diferente da anteriormente registrada
        $response = $this->json('POST', '/device/lh', [
            'body' => [
                'H' => 'X',
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$SESSAO_DE_VALIDACAO_EXPIRADA]);

    }

    public function test_teste_de_validacao_de_acesso_em_duas_etapas_desativado() 
    {
        $this->beforeEach();

        // Força usuário ativo e validação em duas etapas desativada
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->v2 = Usuario::$V2_INATIVO;
        $usuario->save();

        $response = $this->json('POST', '/device/h', [
            'body' => [
                'usuario' => 'Thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson(['H' => false]);

    }

    public function test_teste_de_validacao_de_registro_de_aparelho_com_login_bloqueado()
    {
        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();
        
        $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$LOGIN_BLOQUEADO]);
    }

    public function test_teste_de_validacao_de_registro_do_aparelho_com_login_correto()
    {
        
        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        $this->json('POST', '/device/lh', [
            'body' => [
                'H' => $response['R'],
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200);
    }

    public function test_teste_de_validacao_de_registro_de_aparelho_com_login_incorreto()
    {
        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->save();

        $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'thais',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }

    public function test_teste_de_validacao_de_registro_de_aparelho_com_senha_incorreta()
    {
        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->save();
        
        $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345x'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$SENHA_INCORRETA]);
    }

    public function test_teste_de_validacao_de_registro_de_aparelho_sem_dominio()
    {
        $this->beforeEach();

        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();
        
        $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);
    }

    public function test_teste_de_validacao_de_registro_de_aparelho_com_empresa_incorreta()
    {
        $this->beforeEach();

        $usuario = Usuario::where('login', 'vitor.aragone')->first();
        $usuario->situacao = Usuario::$ATIVO;
        $usuario->save();
        
        $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'vitor.aragone',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }

    public function test_teste_de_validacao_de_registro_de_aparelho_com_empresa_bloqueada()
    {
        $this->beforeEach();

        // Força situacao de empresa bloqueada
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$BLOQUEADA;
        $empresa->save();

        $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$EMPRESA_BLOQUEADA]);
    }

    public function test_teste_de_validacao_de_registro_de_aparelho_com_empresa_inativa()
    {
        $this->beforeEach();

        // Força situacao de empresa inativa
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$INATIVA;
        $empresa->save();

        $this->json('POST', '/device/lh', [
            'body' => [
                'usuario' => 'thais.lopes',
                'senha' => '12345'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$EMPRESA_INATIVA]);

    }

    public function test_teste_de_aparelho_devidamente_registrado()
    {
        
        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        // Verifica se hash(R) recebida foi devidamente registrada
        $this->json('POST', '/device/vr', [
            'body' => [
                'R' => $response['R']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200);

        // Força bloqueio do login de registro do aparelho
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();

        // Verifica se hash(R) recebida, mesmo devidamente registrada, sua conta está bloqueada
        $this->json('POST', '/device/vr', [
            'body' => [
                'R' => $response['R']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$LOGIN_BLOQUEADO]);
        
    }

    public function test_teste_de_aparelho_devidamente_registrado_porem_com_login_bloqueado()
    {

        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        // Verifica se hash(R) recebida foi devidamente registrada
        $this->json('POST', '/device/vr', [
            'body' => [
                'R' => $response['R']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200);

        // Força bloqueio do login de registro do aparelho
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();

        // Verifica se hash(R) recebida, mesmo devidamente registrada, sua conta está bloqueada
        $this->json('POST', '/device/vr', [
            'body' => [
                'R' => $response['R']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$LOGIN_BLOQUEADO]);
        
    }

    public function test_teste_de_aparelho_devidamente_registrado_porem_sem_dominio()
    {

        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        // Verifica se hash(R) recebida, mesmo devidamente registrada, sua conta está bloqueada
        $this->json('POST', '/device/vr', [
            'body' => [
                'R' => $response['R']
            ]
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);
        
    }

    public function test_teste_de_aparelho_nao_registrado_por_perda_do_registro()
    {
        
        $this->beforeEach();

        // Força registro prioritário
        $response = $this->forcaRegistroPrioritario();
        // captura hash(R) de registro
        $R = $response['R'];
        // Força novamente um registro prioritário a fim de perder registro anterior
        $response = $this->forcaRegistroPrioritario();
        // Testa se registro anterior foi realmente perdido
        $this->json('POST', '/device/vr', [
            'body' => [
                'R' => $R
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$DISPOSITIVO_NAO_REGISTRADO]);
    }

    public function test_teste_de_recuperacao_de_codigo_de_validacao_de_aparelho_registrado()
    {
        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        // Verifica se condição de codigo de login recebido é atendido!
        $response = $this->json('POST', '/device/c', [
            'body' => [
                'R' => $response['R']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        // verifica intervalo de codigo gerado
        $this->assertGreaterThanOrEqual(10000, $response['C']);
        $this->assertLessThanOrEqual(99999, $response['C']);

        // verifica se codigo devidamente registrado na base
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $this->assertEquals($usuario->device_code, $response['C']);
    }
       
    public function test_teste_de_recuperacao_de_codigo_de_validacao_de_aparelho_registrado_porem_com_login_bloqueado()
    {
        
        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        // Força bloqueio do login de registro do aparelho
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();

        // Verifica se condição de login bloqueado é atendido!
        $this->json('POST', '/device/c', [
            'body' => [
                'R' => $response['R']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$LOGIN_BLOQUEADO]);
    }

    public function test_teste_de_recuperacao_de_codigo_de_validacao_de_aparelho_registrado_porem_sem_dominio()
    {
        
        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        // Força bloqueio do login de registro do aparelho
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();

        // Verifica se condição de login bloqueado é atendido!
        $this->json('POST', '/device/c', [
            'body' => [
                'R' => $response['R']
            ]
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);
    }

    public function test_teste_de_recuperacao_de_codigo_de_validacao_de_aparelho_nao_registrado()
    {
        $this->beforeEach();

        $this->json('POST', '/device/c', [
            'body' => [
                'R' => 'X'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$DISPOSITIVO_NAO_REGISTRADO]);

    }

    public function test_teste_de_confirmacao_correta_de_codigo_de_validacao_de_aparelho()
    {
        
        $this->beforeEach();

        $response = $this->forcaRegistroPrioritario();

        $R = $response['R'];

        // Recupera codigo de verificacao
        $response = $this->json('POST', '/device/c', [
            'body' => [
                'R' => $R
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        // Valida código de verificação recebido
        $response = $this->json('POST', '/auth', [
            'body' => [
                'U' => 'thais.lopes',
                'code' => $response['C']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson(['login' => 'thais.lopes']);
    }

    public function test_teste_de_confirmacao_correta_de_codigo_de_validacao_de_aparelho_porem_com_login_bloqueado()
    {
       
        $this->beforeEach();
        
        $response = $this->forcaRegistroPrioritario();

        $R = $response['R'];

        // Recupera codigo de verificacao
        $response = $this->json('POST', '/device/c', [
            'body' => [
                'R' => $R
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        // Força bloqueio da conta 
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();

        // Valida código de verificação recebido
        $response = $this->json('POST', '/auth', [
            'body' => [
                'U' => 'thais.lopes',
                'code' => $response['C']
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$LOGIN_BLOQUEADO]);

    }

    public function test_teste_de_confirmacao_correta_de_codigo_de_validacao_de_aparelho_porem_sem_dominio()
    {
       
        $this->beforeEach();
        
        $response = $this->forcaRegistroPrioritario();

        $R = $response['R'];

        // Recupera codigo de verificacao
        $response = $this->json('POST', '/device/c', [
            'body' => [
                'R' => $R
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        // Força bloqueio da conta 
        $usuario = Usuario::where('login', 'thais.lopes')->first();
        $usuario->situacao = Usuario::$BLOQUEADO;
        $usuario->save();

        // Valida código de verificação recebido
        $response = $this->json('POST', '/auth', [
            'body' => [
                'U' => 'thais.lopes',
                'code' => $response['C']
            ]
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);

    }

    public function test_teste_de_confirmacao_incorreta_de_codigo_de_validacao_de_aparelho()
    {
        
        $this->beforeEach();
                
        $response = $this->forcaRegistroPrioritario();

        $R = $response['R'];

        // Recupera codigo de verificacao
        $response = $this->json('POST', '/device/c', [
            'body' => [
                'R' => $R
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->decodeResponseJson();

        // Valida código de verificação recebido contra um código incorreto
        $response = $this->json('POST', '/auth', [
            'body' => [
                'U' => 'thais.lopes',
                'code' => '00000'
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$CODIGO_DE_VALIDACAO_INCORRETO]);

    }

}
