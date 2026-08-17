<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\TipoExameController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\MotivoExameController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\MedicoSolicitanteController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\ExameController;
use App\Http\Controllers\ProcedimentoController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\LaudoController;
use App\Http\Controllers\GestaoController;
use App\Http\Controllers\DespachoController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\RecadoController;
use App\Http\Controllers\PainelController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'device'], function() {
    Route::post('/h',  [DeviceController::class, 'getDeviceHash']);
    Route::post('/vr', [DeviceController::class, 'doValidateDevice']);
    Route::post('/lh', [DeviceController::class, 'doValidateRegister']);
    Route::post('/c',  [DeviceController::class, 'getValidateCode']);
});

Route::group(['prefix' => 'exame'], function() {
    Route::post('/getCampos', [ExameController::class, 'getCampos']);
    Route::post('/setProcedimento', [ProcedimentoController::class, 'setProcedimento']);
});

Route::group(['prefix' => 'gestao'], function() {
    Route::post('/upload', [GestaoController::class, 'upload']);
});

Route::group(['prefix' => 'sistema'], function() {
    Route::post('/exames/upload', [ExameController::class, 'upload']);
    Route::get('/laudos/download', [ExameController::class, 'download']);
});

Route::post('/auth', [LoginController::class, 'doAuthenticate']);

Route::post('/getUsuarioLogado', [UsuarioController::class, 'getUsuarioLogado']);
Route::post('/getUsuarios', [UsuarioController::class, 'getUsuarios']);
Route::post('/getUsuario', [UsuarioController::class, 'getUsuario']);
Route::post('/setUsuario', [UsuarioController::class, 'setUsuario']);

Route::post('/getEmpresas', [EmpresaController::class, 'getEmpresas']);
Route::post('/setEmpresa', [EmpresaController::class, 'setEmpresa']);
Route::post('/delEmpresa', [EmpresaController::class, 'delEmpresa']);
Route::post('/getEmpresa', [EmpresaController::class, 'getEmpresa']);

Route::post('/getPerfis', [PerfilController::class, 'getPerfis']);
Route::post('/getPerfil', [PerfilController::class, 'getPerfil']);
Route::post('/setPerfil', [PerfilController::class, 'setPerfil']);

Route::post('/getTipoExames', [TipoExameController::class, 'getTipoExames']);
Route::post('/getTipoExame', [TipoExameController::class, 'getTipoExame']);
Route::post('/setTipoExame', [TipoExameController::class, 'setTipoExame']);
Route::post('/delTipoExame', [TipoExameController::class, 'delTipoExame']);

Route::post('/setTipoExameCampo', [TipoExameController::class, 'setTipoExameCampo']);
Route::post('/delTipoExameCampo', [TipoExameController::class, 'delTipoExameCampo']);

Route::post('/setFicha', [FichaController::class, 'setFicha']);
Route::post('/getFicha', [FichaController::class, 'getFicha']);
Route::post('/getFichas', [FichaController::class, 'getFichas']);

Route::post('/getClientes', [ClienteController::class, 'getClientes']);
Route::post('/getCliente', [ClienteController::class, 'getCliente']);
Route::post('/setCliente', [ClienteController::class, 'setCliente']);
Route::post('/getSolicitantes', [ClienteController::class, 'getSolicitantes']);

Route::post('/getMedicos', [MedicoController::class, 'getMedicos']);
Route::post('/getMedico', [MedicoController::class, 'getMedico']);
Route::post('/setMedico', [MedicoController::class, 'setMedico']);
Route::post('/getMedicosCompartilhados', [MedicoController::class, 'getMedicosCompartilhados']);

Route::post('/getModelos', [MedicoController::class, 'getModelos']);
Route::post('/getModelo', [MedicoController::class, 'getModelo']);
Route::post('/setModelo', [MedicoController::class, 'setModelo']);

Route::post('/getMotivoExames', [MotivoExameController::class, 'getMotivoExames']);
Route::post('/getMotivoExame', [MotivoExameController::class, 'getMotivoExame']);
Route::post('/setMotivoExame', [MotivoExameController::class, 'setMotivoExame']);

Route::post('/getPaciente', [PacienteController::class, 'getPaciente']);

Route::post('/getMedicoSolicitante', [MedicoSolicitanteController::class, 'getMedico']);
Route::post('/getMedicosSolicitante', [MedicoSolicitanteController::class, 'getMedicos']);

Route::post('/getProcedimentos', [TipoExameController::class, 'getProcedimentos']);

Route::post('/getServicosFilial', [ServicoController::class, 'getServicosFilial']);
Route::post('/getServicosCliente', [ServicoController::class, 'getServicosCliente']);

Route::post('/getExames', [ExameController::class, 'getExames']);
Route::post('/getExame', [ExameController::class, 'getExame']);
Route::post('/setExame', [ExameController::class, 'setExame']);
Route::post('/lote', [ExameController::class, 'lote']);
Route::post('/laudo', [ExameController::class, 'laudo']);
Route::post('/recusa', [ExameController::class, 'recusa']);
Route::post('/pausar', [ExameController::class, 'pausar']);
Route::post('/setStatusExame', [ExameController::class, 'setStatusExame']);
Route::post('/setReciclaExame', [ExameController::class, 'setReciclaExame']);
Route::post('/getExameParaLaudar', [ExameController::class, 'getExameParaLaudar']);

Route::post('/getImpossibilidades', [LaudoController::class, 'getImpossibilidades']);
Route::post('/getImpossibilidade', [LaudoController::class, 'getImpossibilidade']);
Route::post('/setImpossibilidade', [LaudoController::class, 'setImpossibilidade']);

Route::post('/setRegraDespacho', [DespachoController::class, 'setRegraDespacho']);
Route::post('/getRegrasDespacho', [DespachoController::class, 'getRegrasDespacho']);
Route::post('/getRegraDespacho', [DespachoController::class, 'getRegraDespacho']);

Route::post('/setFilaDespacho', [DespachoController::class, 'setFilaDespacho']);
Route::post('/getFilaDespacho', [DespachoController::class, 'getFilaDespacho']);

Route::post('/setPrecoCliente', [FinanceiroController::class, 'setPrecoCliente']);
Route::post('/getPrecoCliente', [FinanceiroController::class, 'getPrecoCliente']);
Route::post('/getPrecoClientes', [FinanceiroController::class, 'getPrecoClientes']);

Route::post('/setPrecoMedico', [FinanceiroController::class, 'setPrecoMedico']);
Route::post('/getPrecoMedico', [FinanceiroController::class, 'getPrecoMedico']);
Route::post('/getPrecoMedicos', [FinanceiroController::class, 'getPrecoMedicos']);

Route::post('/financeiroClientes', [RelatorioController::class, 'financeiroClientes']);
Route::post('/financeiroMedicos', [RelatorioController::class, 'financeiroMedicos']);
Route::post('/faturamentoClientes', [RelatorioController::class, 'faturamentoClientes']);
Route::post('/faturamentoExportar', [RelatorioController::class, 'faturamentoExportar']);
Route::post('/faturamentoPacotes', [RelatorioController::class, 'faturamentoPacotes']);
Route::post('/pesquisaAvancada', [RelatorioController::class, 'pesquisaAvancada']);

Route::post('/setEquipamento', [EquipamentoController::class, 'setEquipamento']);
Route::post('/getEquipamento', [EquipamentoController::class, 'getEquipamento']);
Route::post('/getEquipamentos', [EquipamentoController::class, 'getEquipamentos']);

Route::post('/setConta', [ContaController::class, 'setConta']);
Route::post('/getConta', [ContaController::class, 'getConta']);
Route::post('/getContas', [ContaController::class, 'getContas']);
Route::post('/getSaldo', [ContaController::class, 'getSaldo']);
Route::post('/trocarSenha', [UsuarioController::class, 'trocarSenha']);

Route::get('/retirada', [ExameController::class, 'retirada']);

Route::post('/getRecados', [RecadoController::class, 'getRecados']);
Route::post('/setRecados', [RecadoController::class, 'setRecados']);
Route::post('/mostraRecados', [RecadoController::class, 'mostraRecados']);

Route::post('/getPainel', [PainelController::class, 'getPainel']);

Route::get('/log', function(Request $request) {
    $path = storage_path('logs/laravel.log');
    if (!File::exists($path)) {
        abort(404);
    }
    return response()->download($path);
});
