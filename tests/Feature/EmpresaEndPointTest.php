<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Exceptions\Handler;
use App\Models\Empresa;

class EmpresaEndPointTest extends TestCase
{

    private function beforeEach() {
        
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$ATIVA;
        $empresa->save();

        $empresa = Empresa::where('login', 'CRJ')->first();
        $empresa->nome = "CRJ";
        $empresa->situacao = Empresa::$ATIVA;
        $empresa->save();

        $empresa = Empresa::where('login', 'INFINITY')->first();
        $empresa->situacao = Empresa::$ATIVA;
        $empresa->save();

    }

    public function test_teste_de_obtencao_de_empresas_com_sessao_valida()
    {
        
        $this->beforeEach();

        $response = $this->json('POST', '/getEmpresas', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
                [
                    'EmpresaId' => '1',
                    'EmpresaLogin' => 'INFINITY',
                    'EmpresaNome' => 'Grupo Infinity',
                    'EmpresaSituacao' => Empresa::$ATIVA
                ],[
                    'EmpresaId' => '2',
                    'EmpresaLogin' => 'DAMA',
                    'EmpresaNome' => 'Dama Telemedicina',
                    'EmpresaSituacao' => Empresa::$ATIVA
                ],[
                    'EmpresaId' => '3',
                    'EmpresaLogin' => 'VICTHAMED',
                    'EmpresaNome' => 'VT Telemedicina',
                    'EmpresaSituacao' => Empresa::$ATIVA
                ],[
                    'EmpresaId' => '4',
                    'EmpresaLogin' => 'CRJ',
                    'EmpresaNome' => 'CRJ',
                    'EmpresaSituacao' => Empresa::$ATIVA
                ]
            ]);
    }

    public function test_teste_de_obtencao_de_empresas_com_sessao_nula() {

        $this->beforeEach();

        $response = $this->json('POST', '/getEmpresas', [
            'session' => null,
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$AUTENTICACAO_REQUERIDA]);
    }

    public function test_teste_de_obtencao_de_empresas_com_sessao_nao_fornecida() 
    {

        $this->beforeEach();

        $response = $this->json('POST', '/getEmpresas', [
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$AUTENTICACAO_REQUERIDA]);
    }

    public function test_teste_de_obtencao_de_empresas_com_login_inexistente()
    {
        $this->beforeEach();
        
        $response = $this->json('POST', '/getEmpresas', [
            'session' => [
                'login' => 'xyz',
            ],
            'origin' => 'http://dama.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }    

    public function test_teste_de_obtencao_de_empresas_com_empresa_invalida_para_o_login()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/getEmpresas', [
            'session' => [
                'login' => 'erika',
            ],
            'origin' => 'http://infinity.infinity.local'
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$LOGIN_INCORRETO]);
    }

    public function test_teste_de_obtencao_de_empresas_sem_dominio()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/getEmpresas', [
            'session' => [
                'login' => 'erika',
            ],
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);
    }

    public function test_teste_de_obtencao_de_empresas_com_empresa_bloqueada()
    {
        $this->beforeEach();

        // força bloqueio da empresa
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$BLOQUEADA;
        $empresa->save();

        $response = $this->json('POST', '/getEmpresas', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$EMPRESA_BLOQUEADA]);

    }

    public function test_teste_de_inclusao_de_empresa_novo_login()
    {
        $this->beforeEach();

        // força exclusão da empresa
        $empresa = Empresa::where('login', 'XYZ')->first();
        if ($empresa) $empresa->delete();
        
        $response = $this->json('POST', '/setEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'EmpresaLogin' => 'XYZ',
                'EmpresaNome' => 'XYZ Exames'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            'EmpresaLogin' => 'XYZ',
            'EmpresaNome' => 'XYZ Exames',
            'EmpresaMatriz' => 1,
            'EmpresaSituacao' => Empresa::$ATIVA
        ]);

    }

    public function test_teste_de_inclusao_de_empresa_login_existente()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/setEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'EmpresaLogin' => 'Dama',
                'EmpresaNome' => 'Dama'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$EMPRESA_JA_CADASTRADA]);        
    }

    public function test_teste_de_inclusao_de_empresa_sem_campo_obrigatorio()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/setEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'EmpresaLogin' => 'KLM',
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$CAMPOS_OBRIGATORIOS]);        
    }

    public function test_teste_de_alteracao_de_dados_da_empresa() 
    {
        $this->beforeEach();

        // força alteracao da descricao da empresa
        $empresa = Empresa::where('login', 'CRJ')->first();
        $empresa->nome = 'CRJ';
        $empresa->save();

        $response = $this->json('POST', '/setEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'EmpresaId' => "4",
                'EmpresaLogin' => 'CRJ',
                'EmpresaNome' => 'CRJ Exames'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            'EmpresaLogin' => 'CRJ',
            'EmpresaNome' => 'CRJ Exames',
            'EmpresaMatriz' => 1,
            'EmpresaSituacao' => Empresa::$ATIVA
        ]);
    }

    public function test_teste_de_bloqueio_da_empresa() 
    {
        $this->beforeEach();

        $response = $this->json('POST', '/setEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'EmpresaId' => 4,
                'EmpresaLogin' => 'CRJ',
                'EmpresaNome' => 'CRJ',
                'EmpresaBloquear' => true
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            'EmpresaLogin' => 'CRJ',
            'EmpresaNome' => 'CRJ',
            'EmpresaMatriz' => 1,
            'EmpresaSituacao' => Empresa::$BLOQUEADA
        ]);
    }

    public function test_teste_de_bloqueio_da_empresa_no_dominio() 
    {
        $this->beforeEach();

        $response = $this->json('POST', '/setEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'EmpresaId' => 2, // dama
                'EmpresaLogin' => 'DAMA',
                'EmpresaNome' => 'Dama Telemedicina',                
                'EmpresaBloquear' => true
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$BLOQUEIO_NAO_PERMITIDO]);        
    }

    public function test_teste_de_filial_bloqueando_matriz() 
    {
        $this->beforeEach();

        $response = $this->json('POST', '/setEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'EmpresaId' => 1, 
                'EmpresaLogin' => 'INFINITY',
                'EmpresaNome' => 'Grupo Infinity',                
                'EmpresaBloquear' => true
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$BLOQUEIO_NAO_PERMITIDO]);        
    }

    public function test_teste_de_exclusao_de_empresa_com_sessao_nula()
    {

        $this->beforeEach();

        $response = $this->json('POST', '/delEmpresa', [
            'session' => null,
            'body' => [
                'login' => 'xxxx'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$AUTENTICACAO_REQUERIDA]);

    }

    public function test_teste_de_exclusao_de_empresa_com_sessao_nao_fornecida()
    {

        $this->beforeEach();

        $response = $this->json('POST', '/delEmpresa', [
            'body' => [
                'login' => 'xxxx'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$UNAUTHORIZED)
        ->assertJson(['erro' => Handler::$AUTENTICACAO_REQUERIDA]);

    }

    public function test_teste_de_exclusao_de_empresa_inexistente()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/delEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'login' => 'xxxx'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$EMPRESA_NAO_ENCONTRADA]);
    }

    public function test_teste_de_exclusao_de_empresa_sem_dominio()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/delEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'login' => 'dama'
            ]
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST)
        ->assertJson(['erro' => Handler::$REQUISICAO_MAL_FORMADA]);

    }

    public function test_teste_de_exclusao_de_empresa_do_mesmo_dominio()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/delEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'login' => 'dama'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$FORBIDDEN)
        ->assertJson(['erro' => Handler::$EXCLUSAO_NAO_PERMITIDA]);

    }

    public function test_teste_de_exclusao_de_empresa_com_sessao_valida()
    {

        $this->beforeEach();

        $response = $this->json('POST', '/delEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'login' => 'dama'
            ],
            'origin' => 'http://infinity.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            [
                'EmpresaId' => '1',
                'EmpresaLogin' => 'INFINITY',
                'EmpresaNome' => 'Grupo Infinity'
            ],[
                'EmpresaId' => '3',
                'EmpresaLogin' => 'VICTHAMED',
                'EmpresaNome' => 'VT Telemedicina'
            ],[
                'EmpresaId' => '4',
                'EmpresaLogin' => 'CRJ',
                'EmpresaNome' => 'CRJ'
            ]
        ]);

        // restaura condicao da empresa "inativada"
        $empresa = Empresa::where('login', 'DAMA')->first();
        $empresa->situacao = Empresa::$ATIVA;
        $empresa->save();

    }

    public function test_testa_recuperacao_da_empresa(){

        $this->beforeEach();

        $response = $this->json('POST', '/getEmpresa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'login' => 'DAMA'
            ],
            'origin' => 'http://infinity.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson(
            [
                'EmpresaId' => 2,
                'EmpresaLogin' => 'DAMA',
                'EmpresaNome' => 'Dama Telemedicina',
                'EmpresaMatriz' => 1,
                'EmpresaSituacao' => Empresa::$ATIVA
            ]
        );

    }

}
