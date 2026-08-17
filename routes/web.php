<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\FichaController;
use Illuminate\Http\Request;

use Carbon\Carbon;

use App\Utils\Utils;
use App\Models\Exame;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'device'], function() {
    Route::post('/h',  [DeviceController::class, 'getDeviceHash']);
    Route::post('/vr', [DeviceController::class, 'doValidateDevice']);
    Route::post('/lh', [DeviceController::class, 'doValidateRegister']);
    Route::post('/c',  [DeviceController::class, 'getValidateCode']);
});

Route::post('/auth', [LoginController::class, 'doAuthenticate']);

Route::post('/getUsuarioLogado', [UsuarioController::class, 'getUsuarioLogado']);
Route::post('/getUsuarios', [UsuarioController::class, 'getUsuarios']);
Route::post('/getUsuario', [UsuarioController::class, 'getUsuario']);
Route::post('/setUsuario', [UsuarioController::class, 'setUsuario']);

Route::post('/getEmpresas', [EmpresaController::class, 'getEmpresas']);
Route::post('/setEmpresa', [EmpresaController::class, 'setEmpresa']);
Route::post('/getEmpresa', [EmpresaController::class, 'getEmpresa']);
Route::post('/delEmpresa', [EmpresaController::class, 'delEmpresa']);

Route::post('/getPerfis', [PerfilController::class, 'getPerfis']);
Route::post('/getPerfil', [PerfilController::class, 'getPerfil']);
Route::post('/setPerfil', [PerfilController::class, 'setPerfil']);

Route::post('/getFicha', [FichaController::class, 'getFicha']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/relatorio', function(Request $request) {
    $path = storage_path('/app/' . $request->name);
    if (!File::exists($path)) {
        abort(404);
    }
    return \Response::download($path);
});

Route::get('/exportar', function(Request $request) {
    $path = storage_path($request->name);
    if (!File::exists($path)) {
        abort(404);
    }
    return \Response::download($path);
});

Route::get('/download', function(Request $request) {
    $path = storage_path($request->name);
    if (!File::exists($path)) {
        abort(404);
    }
    $exame = Exame::where('id', $request->id)->first();
    if (!$exame) {
        abort(404);
    }
    $exame->laudo_download_date = Carbon::now();
    $exame->save();
    if ($request->laudo) {
        $name = Utils::getNomeLaudoParaDownload($exame);
        return \Response::download($path, $name);
    }
    return \Response::download($path);
});

Route::get('/retirada', function(Request $request) {
    $exame = Exame::where('protocolo', $request->id)->first();
    if (!$exame) {
        abort(404);
    }
    $path = storage_path($exame->arquivo_laudo);
    if (!File::exists($path)) {
        abort(404);
    }
    $exame->laudo_download_date = Carbon::now();
    $exame->save();
    $name = Utils::getNomeLaudoParaDownload($exame);
    return response()->download($path, $name);
});

Route::get('/image', function(Request $request) {

    $path = storage_path($request->name);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;

});

Route::get('/sound', function(Request $request) {

    $path = storage_path($request->name);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;

});

