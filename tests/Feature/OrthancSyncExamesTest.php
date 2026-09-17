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

    public function test_command_advances_cursor_and_imports_stable_studies_only()
    {
        OrthancSyncState::query()->update(['last_change_id' => 0]);

        $client = Mockery::mock(OrthancClient::class);
        $client->shouldReceive('getChangesSince')->once()->with(0, 50)->andReturn([
            'Changes' => [
                ['ChangeType' => 'NewInstance', 'ID' => 'instance-a'],
                ['ChangeType' => 'StableStudy', 'ID' => 'study-a'],
                ['ChangeType' => 'StableStudy', 'ID' => 'study-b'],
            ],
            'Done' => true,
            'Last' => 42,
        ]);
        $this->app->instance(OrthancClient::class, $client);

        $importer = Mockery::mock(OrthancExameImporter::class);
        $importer->shouldReceive('importStudy')->with($client, 'study-a')->once()->andReturn(true);
        $importer->shouldReceive('importStudy')->with($client, 'study-b')->once()->andReturn(false);
        $this->app->instance(OrthancExameImporter::class, $importer);

        Artisan::call('orthanc:sync-exames');

        $state = OrthancSyncState::first();
        $this->assertEquals(42, $state->last_change_id);
    }

    public function test_import_study_skips_when_orthanc_reports_no_instances()
    {
        $client = Mockery::mock(OrthancClient::class);
        $client->shouldReceive('getStudyInstanceIds')->once()->with('study-empty')->andReturn([]);

        $importer = new StubbedOrthancExameImporter();
        $importer->stubInstitutionNameId = $this->institutionNameId;

        $countBefore = Exame::count();
        $result = $importer->importStudy($client, 'study-empty');

        $this->assertFalse($result);
        $this->assertEquals($countBefore, Exame::count());
    }

    public function test_import_study_skips_when_institution_is_unknown()
    {
        $client = Mockery::mock(OrthancClient::class);
        $client->shouldReceive('getStudyInstanceIds')->once()->with('study-unknown')->andReturn(['instance-x']);
        $client->shouldReceive('downloadInstanceFile')->once()->with('instance-x')->andReturn($this->sampleDcmBytes());

        $importer = new OrthancExameImporter();

        $countBefore = Exame::count();
        $result = $importer->importStudy($client, 'study-unknown');

        $this->assertFalse($result);
        $this->assertEquals($countBefore, Exame::count());
    }

    public function test_import_study_groups_instances_of_the_same_type_into_one_exame()
    {
        $client = Mockery::mock(OrthancClient::class);
        $client->shouldReceive('getStudyInstanceIds')->once()->with('study-grouped')->andReturn(['instance-1', 'instance-2']);
        $client->shouldReceive('downloadInstanceFile')->twice()->andReturn($this->sampleDcmBytes());

        $importer = new StubbedOrthancExameImporter();
        $importer->stubInstitutionNameId = $this->institutionNameId;

        $countBefore = Exame::count();
        $result = $importer->importStudy($client, 'study-grouped');

        $this->assertTrue($result);
        // Both instances share the same StudyInstanceUID + tipoExame (identical
        // fixture), so they must land in a single exame — not one each — same as
        // a lote zip containing two images of the same type.
        $this->assertEquals($countBefore + 1, Exame::count());

        // orthanc_study_id guarda o StudyInstanceUID do DICOM (não o "study-grouped"
        // passado como $studyId), porque é isso que o parâmetro "study=" do Stone Web
        // Viewer espera — ver OrthancExameImporter::parseStudyInstanceUid().
        // orthanc_series_id guarda o SeriesInstanceUID do grupo (ver getDICOMInfo/
        // groupDCMFile), pra apontar só pra série desse exame, não o estudo inteiro.
        $exame = Exame::orderBy('id', 'desc')->first();
        $this->assertEquals('1.113654.3.13.1026', $exame->orthanc_study_id);
        $this->assertEquals('1.113654.5.14.1035', $exame->orthanc_series_id);
        // Essa fixture não tem a tag BodyPartExamined preenchida — exatamente o
        // caso (comum na prática) que normalizeParteCorpoExaminada() trata
        // devolvendo null, em vez de quebrar ou inventar um valor.
        $this->assertNull($exame->parte_corpo_examinada);
    }

    public function test_normalize_parte_corpo_examinada_translates_known_values_and_passes_through_unknown()
    {
        $controller = new \App\Http\Controllers\ExameController();
        $method = new \ReflectionMethod(\App\Http\Controllers\ExameController::class, 'normalizeParteCorpoExaminada');
        $method->setAccessible(true);

        $this->assertEquals('MAO', $method->invoke($controller, 'HAND'));
        $this->assertEquals('TORAX', $method->invoke($controller, 'chest')); // case-insensitive
        $this->assertEquals('COLUNA LOMBAR', $method->invoke($controller, 'LSPINE'));
        // Valor não mapeado: devolve em maiúsculas em vez de descartar a informação.
        $this->assertEquals('LARYNX', $method->invoke($controller, 'larynx'));
        // Tag vazia (comum na prática, nem todo equipamento preenche): null.
        $this->assertNull($method->invoke($controller, ''));
        $this->assertNull($method->invoke($controller, null));
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
