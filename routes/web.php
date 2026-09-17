<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\FichaController;
use App\Models\Documento;
use Illuminate\Http\Request;

use Carbon\Carbon;

use App\Utils\Utils;
use App\Models\Exame;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

Route::group(['prefix' => 'device'], function () {
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

if (!function_exists('getProtocol')) {
    function getProtocol()
    {
        return strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === false ? 'http://' : 'https://';
    }
}

if (!function_exists('getWebPort')) {
    function getWebPort()
    {
        return getenv('WEB_PORT') ? ':' . getenv('WEB_PORT') : '';
    }
}

if (!function_exists('getWebFullUrl')) {
    function getWebFullUrl()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        if ($host == null) abort(401);
        $port =  isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null;
        $host = $port == null ? $host : str_replace(':' . $port, '', $host);
        return getProtocol() . $host . getWebPort();
    }
}

Route::get('/', function (Request $request) {
    return \Redirect::to(getWebFullUrl());
});

Route::get('/relatorio', function (Request $request) {
    $path = storage_path('/app/' . $request->name);
    if (!File::exists($path)) {
        abort(404);
    }
    return \Response::download($path);
});

Route::get('/exportar', function (Request $request) {
    $path = storage_path($request->name);
    if (!File::exists($path)) {
        abort(404);
    }
    return \Response::download($path);
});

Route::get('/download', function (Request $request) {
    $path = storage_path($request->name);
    if (!File::exists($path)) {
        abort(404);
    }
    $exame = Exame::where('id', $request->id)->first();
    if (!$exame) {
        $exame = DB::table('exames_antigos')
            ->where('id', $request->id)
            ->first();

        if (!$exame) {
            abort(404);
        } else {
            DB::table('exames_antigos')
                ->where('id', $request->id)
                ->update([
                    'laudo_download_date' => Carbon::now(),
                ]);
        }
    } else {
        $exame->laudo_download_date = Carbon::now();
        $exame->save();
    }

    if ($request->laudo) {
        $name = Utils::getNomeLaudoParaDownload($exame);
        return \Response::download($path, $name, ['Access-Control-Expose-Headers' => 'Content-Disposition']);
    }
    return \Response::download($path);
});

Route::get('/retirada', function (Request $request) {
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

Route::get('/image', function (Request $request) {

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

Route::get('/sound', function (Request $request) {

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

/**
 * Proxy pro Orthanc — repassa qualquer caminho pro Orthanc real, injetando a
 * credencial (orthanc.user/password) no servidor. Existe porque o Orthanc
 * exige login em TODA requisição, até pra carregar a página do Stone Web
 * Viewer, e o navegador do usuário não tem (nem deveria ter) essa senha.
 * O frontend usa "/orthanc-viewer/stone-webviewer/index.html?study=<id>"
 * (ver ExameController::getOrthancViewerUrl) num iframe; o viewer, uma vez
 * carregado, faz suas próprias chamadas de API relativas a essa mesma rota,
 * então isso precisa aceitar qualquer sub-caminho, não só o index.html.
 *
 * Sem checagem de sessão de propósito — mesmo padrão já usado pelas rotas de
 * download acima (/download, /image, etc), que também não exigem login.
 *
 * ATENÇÃO: isso expõe todo o REST API do Orthanc (não só o viewer) pra
 * qualquer um que descubra essa URL, sem exigir login na DAMA — é uma troca
 * consciente de simplicidade por segurança, igual as rotas de download acima.
 *
 * Precisa aceitar qualquer verbo HTTP (Route::any), não só GET: o viewer usa
 * POST pra consultar dados (ex: /tools/find, a API de busca do Orthanc) —
 * com a rota só em GET isso batia 405 e a busca falhava silenciosamente (tela
 * do viewer carregava, mas ficava vazia, sem erro visível). Também precisa
 * estar isenta de CSRF (ver VerifyCsrfToken::$except) porque esses POSTs vêm
 * do JS do próprio Orthanc, que não tem — nem tem como ter — nosso token.
 *
 * index.html recebe uma "ponte" de postMessage injetada no fim do <body>
 * (ver $bridgeScript abaixo) — é o que permite @page-script.js (tela de
 * exame) mandar carregar séries adicionais no viewer depois que ele já abriu
 * (ex: "MAO ESQ" junto com "MAO DIR" — ver
 * ExameController::getOrthancSeriesIdsDoGrupoAnatomico). Não dá pra fazer
 * isso chamando iframe.contentWindow.stone.FetchSeries() direto do
 * @page-script.js porque infinity-web e infinity-api rodam em portas
 * diferentes — origens diferentes pro navegador, mesmo no mesmo host — e
 * acesso direto a um iframe de outra origem é bloqueado. postMessage é o
 * mecanismo padrão pra isso, funciona entre origens diferentes sem exceção
 * de segurança nenhuma.
 */
Route::any('/orthanc-viewer/{path?}', function (Request $request, $path = '') {
    $url = rtrim(config('orthanc.url'), '/') . '/' . $path;
    $query = $request->getQueryString();
    if ($query) $url .= '?' . $query;

    $http = Http::withBasicAuth(config('orthanc.user'), config('orthanc.password'))
        ->withHeaders(array_filter(['Range' => $request->header('Range')]));

    $method = strtolower($request->method());

    $response = in_array($method, ['post', 'put', 'patch', 'delete'])
        ? $http->withBody($request->getContent(), $request->header('Content-Type', 'application/json'))->send($method, $url)
        : $http->get($url);

    $body = $response->body();

    if ($path === 'stone-webviewer/index.html' && str_contains($body, '</body>')) {
        // Fica ouvindo por { type: 'orthancViewerFetchSeries', study, series }
        // vindo de fora (postMessage) e chama window.stone.FetchSeries() — a
        // mesma função que o próprio app.js do viewer usa pro parâmetro
        // "series=" da URL, só que chamável a qualquer momento depois. Guarda
        // numa fila (queue) o que chegar antes do StoneInitialized disparar
        // (o WASM do viewer demora um pouco pra inicializar), e descarrega a
        // fila assim que ele fica pronto.
        $bridgeScript = '<script>(function(){'
            . 'var queue=[];var ready=false;'
            . 'window.addEventListener("StoneInitialized",function(){'
            . 'ready=true;'
            . 'queue.forEach(function(m){if(window.stone&&window.stone.FetchSeries)window.stone.FetchSeries(m.study,m.series);});'
            . 'queue=[];'
            . '});'
            . 'window.addEventListener("message",function(ev){'
            . 'var d=ev.data;'
            . 'if(!d||d.type!=="orthancViewerFetchSeries")return;'
            . 'if(ready&&window.stone&&window.stone.FetchSeries){window.stone.FetchSeries(d.study,d.series);}'
            . 'else{queue.push(d);}'
            . '});'
            . '})();</script>';
        $body = str_replace('</body>', $bridgeScript . '</body>', $body);
    }

    return response($body, $response->status())
        ->header('Content-Type', $response->header('Content-Type'));
})->where('path', '.*');

Route::get('uploads/temp/{uuid}', function (string $uuid) {
    if (!\request()->hasValidSignature(false)) {
        abort(401);
    }

    $decodedUuid = urldecode($uuid);

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = \Illuminate\Support\Facades\Storage::disk('uploads');

    /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query */
    $query = Documento::query();

    $documento = $query->where('uuid', $decodedUuid)
        ->first(["rawfilename", "filename"]);

    // $query->dd();
    if (!$documento || $disk->exists(DocumentoController::COMPARTILHAMENTO_FOLDER_NAME . '/' . $documento->rawfilename) == false) {
        abort(404);
    }

    // se for imagem || pdf: response
    if (str_contains($documento->rawfilename, '.pdf') || str_contains($documento->rawfilename, '.jpg') || str_contains($documento->rawfilename, '.jpeg') || str_contains($documento->rawfilename, '.png'))
        return $disk->response(DocumentoController::COMPARTILHAMENTO_FOLDER_NAME . '/' . $documento->rawfilename, $documento->filename);
    return $disk->download(DocumentoController::COMPARTILHAMENTO_FOLDER_NAME . '/' . $documento->rawfilename, $documento->filename);
})->name('uploads.temp');