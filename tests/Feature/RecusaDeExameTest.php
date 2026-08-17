<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Mockery\MockInterface;

use Tests\TestCase;

use App\Http\Controllers\ExameController;

use App\Exceptions\Handler;

use App\Models\MedicoExame;
use App\Models\DespachoRegra;

class RecusaDeExameTest extends TestCase {

    public function test_teste_de_recusa_por_medico_ser_unico() {

        //$this->beforeEach();

        $mock1 = $this->mock(Exame::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('where')
                ->andReturn([
                    1
                ]);
        });

        $mock2 = $this->mock(MedicoExame::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('where')
                ->andReturn([
                    1
                ]);
        });

        $response = $this->json('POST', '/api/recusa', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'exameId' => 1
            ],
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(Handler::$BAD_REQUEST);

    }

/*
    public function test_teste_de_recusa_por_regra_de_exclusividade_geral() {

    }

    public function test_teste_de_recusa_por_regra_de_exclusividade_especifica() {

    }
*/
}


