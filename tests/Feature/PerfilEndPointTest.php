<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Perfil;

class PerfilEndPointTest extends TestCase
{
    
    private function beforeEach() {
        $perfil = Perfil::where('nome', 'Gerente')->first();
        if ($perfil) $perfil->delete();
        $perfil = Perfil::where('nome', 'GerenteX')->first();
        if ($perfil) $perfil->delete();
    }

    public function test_teste_de_obtencao_de_perfis_proprio_com_sessao_valida()
    {
        
        $this->beforeEach();
        
        $response = $this->json('POST', '/getPerfis', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'origin' => 'http://infinity.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
                [
                    'PerfilId' => '1',
                    'PerfilNome' => 'Master',
                    'PerfilEmpresaId' => 1
                ],[
                    'PerfilId' => '2',
                    'PerfilNome' => 'Suporte',
                    'PerfilEmpresaId' => 1
                ],[
                    'PerfilId' => '3',
                    'PerfilNome' => 'Financeiro',
                    'PerfilEmpresaId' => 1
                ],[
                    'PerfilId' => '4',
                    'PerfilNome' => 'Vendas',
                    'PerfilEmpresaId' => 1
                ]
            ]);

    }

    public function test_teste_de_obtencao_de_perfis_proprio_com_sessao_invalida_sem_dominio()
    {
    }

    public function test_teste_de_obtencao_de_perfis_proprio_com_sessao_invalida_com_dominio_nulo()
    {
    }

    public function test_teste_de_obtencao_de_perfis_proprio_com_sessao_invalida_sem_sessao()
    {

    }

    public function test_teste_de_obtencao_de_perfis_proprio_com_sessao_invalida_com_sessao_nula()
    {

    }

    public function test_teste_de_obtencao_de_perfis_proprio_com_sessao_invalida_com_sessao_invalida()
    {

    }

    public function test_teste_de_obtencao_de_perfis_herdados_com_sessao_valida()
    {
        
        $this->beforeEach();
        
        $response = $this->json('POST', '/getPerfis', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
                [
                    'PerfilId' => '1',
                    'PerfilNome' => 'Master',
                    'PerfilEmpresaId' => 1
                ],[
                    'PerfilId' => '2',
                    'PerfilNome' => 'Suporte',
                    'PerfilEmpresaId' => 1
                ],[
                    'PerfilId' => '3',
                    'PerfilNome' => 'Financeiro',
                    'PerfilEmpresaId' => 1
                ],[
                    'PerfilId' => '4',
                    'PerfilNome' => 'Vendas',
                    'PerfilEmpresaId' => 1
                ]
            ]);

    }

    public function test_teste_de_obtencao_de_perfil_com_sessao_valida()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/getPerfil', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'id' => '1'
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson(
            [
                'PerfilId' => '1',
                'PerfilNome' => 'Master',
                'PerfilEmpresaId' => 1
            ]);

    }

    public function test_teste_de_inclusao_de_perfil_com_sessao_valida()
    {
        $this->beforeEach();

        $response = $this->json('POST', '/setPerfil', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'PerfilNome' => 'Gerente',
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            'PerfilNome' => 'Gerente',
            'PerfilEmpresaId' => 1,
        ]);
    
    }

    public function test_teste_de_alteracao_de_dados_do_perfil_com_sessao_valida()
    {
        $this->beforeEach();

        // Inclui o novo perfil
        $response = $this->json('POST', '/setPerfil', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'PerfilNome' => 'Gerente',
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            'PerfilNome' => 'Gerente',
            'PerfilEmpresaId' => 1,
        ]);

        $id = $response['PerfilId'];
        
        // Altera nome do novo perfil criado
        $response = $this->json('POST', '/setPerfil', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'PerfilId' => $id,
                'PerfilNome' => 'GerenteX',
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson([
            'PerfilId' => $id,
            'PerfilNome' => 'GerenteX',
            'PerfilEmpresaId' => 1,
        ]);

    }
    
}
