<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\Empresa;
use App\Models\MotivoExame;
use App\Models\Cliente;
use App\Models\Exame;
use App\Models\OrthancSyncState;
use App\Services\Orthanc\OrthancClient;
use App\Services\Orthanc\OrthancExameImporter;

use Illuminate\Support\Facades\Artisan;
use Mockery;

/**
 * Test-only subclass that stubs institution-name resolution so the
 * "client found" path can be exercised without needing a real DICOM file
 * that carries a matching InstitutionName tag. Everything downstream
 * (setPropertiesByInstitutionName -> insertDCM -> insertExame) is the real,
 * already-in-production code path.
 */
class StubbedOrthancExameImporter extends OrthancExameImporter
{
    public ?string $stubInstitutionNameId = null;

    protected function computeInstitutionNameId(?string $institutionNameRaw): ?string
    {
        return $this->stubInstitutionNameId;
    }
}

class OrthancSyncExamesTest extends TestCase
{
    private Empresa $empresa;
    private Cliente $cliente;
    private string $institutionNameId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = new Empresa();
        $this->empresa->nome = 'Empresa Teste Orthanc';
        $this->empresa->login = 'ORTT' . substr(uniqid(), -8);
        $this->empresa->matriz = 0;
        $this->empresa->situacao = Empresa::$ATIVA;
        $this->empresa->save();
        $this->empresa->matriz = $this->empresa->id;
        $this->empresa->save();

        $motivo = new MotivoExame();
        $motivo->nome = 'Motivo Padrao Teste';
        $motivo->atendimento = 'OCUPACIONAL';
        $motivo->padrao = 1;
        $motivo->empresa_id = $this->empresa->id;
        $motivo->save();

        $this->cliente = new Cliente();
        $this->cliente->nome = 'Cliente Teste Orthanc';
        $this->cliente->cnpj = '00000000000000';
        $this->cliente->empresa_id = $this->empresa->id;
        $this->cliente->situacao = 0;
        $this->cliente->inativo = 0;
        $this->cliente->chave_transmissao = 'CHAVE-TESTE-' . uniqid();
        $this->institutionNameId = 'stub-' . uniqid();
        $this->cliente->institution_name_id = $this->institutionNameId;
        $this->cliente->save();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function sampleDcmBytes(): string
    {
        return file_get_contents(base_path('tests/fixtures/sample.dcm'));
    }

    public function test_importer_skips_instance_with_unknown_institution_name()
    {
        $importer = new OrthancExameImporter();
        $countBefore = Exame::count();

        $result = $importer->importInstance($this->sampleDcmBytes(), 'fake-instance-unknown');

        $this->assertFalse($result);
        $this->assertEquals($countBefore, Exame::count());
    }

    public function test_importer_creates_exame_when_institution_resolves()
    {
        $importer = new StubbedOrthancExameImporter();
        $importer->stubInstitutionNameId = $this->institutionNameId;

        $countBefore = Exame::count();

        $result = $importer->importInstance($this->sampleDcmBytes(), 'fake-instance-known');

        $this->assertTrue($result);
        $this->assertEquals($countBefore + 1, Exame::count());

        $exame = Exame::orderBy('id', 'desc')->first();
        $this->assertEquals($this->empresa->id, $exame->empresa_id);
        $this->assertEquals('ORTHANC', $exame->enviado_por);
    }

    public function test_importer_is_idempotent_for_the_same_instance_content()
    {
        $importer = new StubbedOrthancExameImporter();
        $importer->stubInstitutionNameId = $this->institutionNameId;

        $bytes = $this->sampleDcmBytes();

        $importer->importInstance($bytes, 'fake-instance-dup-1');
        $countAfterFirst = Exame::count();

        // Same DICOM content again, simulating Orthanc re-sending a change:
        // insertExame()'s existing CRC dedupe must prevent a second row.
        $importer->importInstance($bytes, 'fake-instance-dup-2');
        $countAfterSecond = Exame::count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    public function test_importer_skips_when_cliente_has_no_chave_transmissao()
    {
        $this->cliente->chave_transmissao = null;
        $this->cliente->save();

        $importer = new StubbedOrthancExameImporter();
        $importer->stubInstitutionNameId = $this->institutionNameId;

        $countBefore = Exame::count();
        $result = $importer->importInstance($this->sampleDcmBytes(), 'fake-instance-no-key');

        $this->assertFalse($result);
        $this->assertEquals($countBefore, Exame::count());
    }

    public function test_command_advances_cursor_and_imports_new_instances_only()
    {
        OrthancSyncState::query()->update(['last_change_id' => 0]);

        $client = Mockery::mock(OrthancClient::class);
        $client->shouldReceive('getChangesSince')->once()->with(0, 50)->andReturn([
            'Changes' => [
                ['ChangeType' => 'NewInstance', 'ID' => 'instance-a'],
                ['ChangeType' => 'StableStudy', 'ID' => 'study-a'],
                ['ChangeType' => 'NewInstance', 'ID' => 'instance-b'],
            ],
            'Done' => true,
            'Last' => 42,
        ]);
        $client->shouldReceive('downloadInstanceFile')->twice()->andReturn('fake-bytes-irrelevant');
        $this->app->instance(OrthancClient::class, $client);

        $importer = Mockery::mock(OrthancExameImporter::class);
        $importer->shouldReceive('importInstance')->twice()->andReturn(true, false);
        $this->app->instance(OrthancExameImporter::class, $importer);

        Artisan::call('orthanc:sync-exames');

        $state = OrthancSyncState::first();
        $this->assertEquals(42, $state->last_change_id);
    }

    public function test_command_keeps_polling_until_orthanc_reports_done()
    {
        OrthancSyncState::query()->update(['last_change_id' => 0]);

        $client = Mockery::mock(OrthancClient::class);
        $client->shouldReceive('getChangesSince')->once()->with(0, 50)->andReturn([
            'Changes' => [],
            'Done' => false,
            'Last' => 10,
        ]);
        $client->shouldReceive('getChangesSince')->once()->with(10, 50)->andReturn([
            'Changes' => [],
            'Done' => true,
            'Last' => 10,
        ]);
        $this->app->instance(OrthancClient::class, $client);

        $importer = Mockery::mock(OrthancExameImporter::class);
        $this->app->instance(OrthancExameImporter::class, $importer);

        Artisan::call('orthanc:sync-exames');

        $state = OrthancSyncState::first();
        $this->assertEquals(10, $state->last_change_id);
    }
}
