<?php

namespace Tests\Unit;

use App\Services\Dicom\DicomProfileResolver;
use PHPUnit\Framework\TestCase;

class DicomProfileResolverTest extends TestCase
{
    private $fuji = ['fabricante' => 'FUJIFILM Corporation', 'modelo' => 'FDR Go PLUS', 'estacao' => 'CR01'];

    public function testCasaPorFabricanteSemDiferenciarMaiusculas()
    {
        $this->assertTrue(DicomProfileResolver::casa(['fabricante' => 'fuji'], $this->fuji));
    }

    public function testTodoCriterioPreenchidoPrecisaCasar()
    {
        $this->assertTrue(DicomProfileResolver::casa(['fabricante' => 'fuji', 'modelo' => 'go'], $this->fuji));
        $this->assertFalse(DicomProfileResolver::casa(['fabricante' => 'fuji', 'modelo' => 'smart'], $this->fuji));
    }

    public function testPerfilSemCriterioNuncaCasa()
    {
        $this->assertFalse(DicomProfileResolver::casa([], $this->fuji));
        $this->assertFalse(DicomProfileResolver::casa(['fabricante' => '', 'modelo' => ' '], $this->fuji));
    }

    public function testCasaPorEstacao()
    {
        $this->assertTrue(DicomProfileResolver::casa(['estacao' => 'cr0'], $this->fuji));
    }
}
