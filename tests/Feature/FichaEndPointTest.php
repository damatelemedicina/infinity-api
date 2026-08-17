<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Ficha;

class FichaEndPointTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_testa_recuperacao_de_uma_ficha()
    {
        $response = $this->json('POST', '/getFicha', [
            'session' => [
                'login' => 'thais.lopes'
            ],
            'body' => [
                'id' => '1'
            ],            
            'origin' => 'http://dama.infinity.local',
        ], ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJson(['id' => 'carlos']);
    }
}
