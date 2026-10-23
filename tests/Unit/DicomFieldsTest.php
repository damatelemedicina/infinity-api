<?php

namespace Tests\Unit;

use App\Utils\DicomFields;
use PHPUnit\Framework\TestCase;

class DicomFieldsTest extends TestCase
{
    public function testBodyPartCorrompidoDaFujiVoltaParaTorax()
    {
        $this->assertSame('TORAX', DicomFields::normalizeBodyPart("T\xD3RAX"));  // Latin-1 válido
        $this->assertSame('TORAX', DicomFields::normalizeBodyPart("T\xEF\xBF\xBDRAX")); // U+FFFD
    }

    public function testBodyPartVazioOuNormal()
    {
        $this->assertSame('', DicomFields::normalizeBodyPart(null));
        $this->assertSame('', DicomFields::normalizeBodyPart('  '));
        $this->assertSame('CHEST', DicomFields::normalizeBodyPart('CHEST '));
        $this->assertSame('HAND', DicomFields::normalizeBodyPart('hand'));
    }

    public function testToUtf8NuncaQuebra()
    {
        $this->assertSame('Mão', DicomFields::toUtf8("M\xE3o"));
        $this->assertSame('Mão', DicomFields::toUtf8('Mão'));
        $this->assertTrue(mb_check_encoding(DicomFields::toUtf8("\x81\x8D\xFF"), 'UTF-8'));
    }

    public function testToAsciiUpperDescricoesDaBraga()
    {
        $this->assertSame('TORAX OIT', DicomFields::toAsciiUpper('TORAX OIT'));
        $this->assertSame('COLUNA LOMBO SACRA', DicomFields::toAsciiUpper("Coluna  Lombo Sacra "));
    }

    public function testDocumentoComEEsemMascara()
    {
        $this->assertSame('12345678909', DicomFields::onlyDigits('123.456.789-09'));
        $this->assertSame('12345678909', DicomFields::onlyDigits('1 2 3 4 5 6 7 8 9 0 9'));
        $this->assertSame('002818', DicomFields::onlyDigits('DX002818'));
    }

    public function testPlaceholders()
    {
        foreach (['', '0', 'undefined', ' UNDEFINED ', ',,,'] as $v) {
            $this->assertTrue(DicomFields::isPlaceholder($v), $v);
        }
        $this->assertFalse(DicomFields::isPlaceholder('Torax OIT PA'));
    }
}
