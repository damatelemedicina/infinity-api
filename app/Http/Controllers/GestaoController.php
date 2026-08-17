<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\InclusaoDeExameException;

use App\Models\Exame;

class GestaoController extends Controller
{

    private static $PATH_LOTES = '/uploads/exames/lotes/';

    private static $ARQUIVOS_NAO_INFORMADOS = 'Arquivos não informados!';

    public function upload(Request $request) {

        $request = $this->parseRequest($request);

        if (!$request->hasFile('files')) {
            throw new InclusaoDeExameException(Self::$ARQUIVOS_NAO_INFORMADOS);
        }

        foreach ($request->file('files') as $file) {
            $data = $this->doUpload($file);
            $orig_name = strtolower($data['orig_name']);
            if (preg_match('/dama_imagens.zip/', $orig_name ) == 1) {
				$this->insertIMAGENS($data);
            }
            if (preg_match('/logotipos.zip/', $orig_name ) == 1) {
                $this->insertLOGOTIPOS($data);
            }
        }

        return [];
    }

    private function insertLOGOTIPOS($data) {
        $path = $this->storePath('/uploads/logotipos');
        if (!is_dir($path)){
            mkdir($path, 0777);
        }
        $zip  = new \ZipArchive();
        if ($zip->open($this->storePath($data['full_path'])) == TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('/(\.png|\.PNG)/', $name) !== 1) continue;
                $zip->extractTo($path, $name);
            }
        }
    }

    private function insertIMAGENS($data) {

        $path = $this->storePath($data['file_path'] . 'imagens/');
        if (!is_dir($path)){
            mkdir($path, 0777);
        }

        $zip  = new \ZipArchive();
        if ($zip->open($this->storePath($data['full_path'])) == TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('/(\.jpg|\.JPG)/', $name) !== 1) continue;
                $zip->extractTo($path, $name);
                $this->insertIMAGEM($name);
            }
        }
    }

    private function getArquivoId($name){
        if ($name == null) return null;
        $str = strtolower($name);
        $fim = strpos($str, ".jpg");
        if ($fim === false) $fim = strpos($str, ".wxml");
        if ($fim === false) $fim = strpos($str, ".dcm");
        if ($fim === false) $fim = strpos($str, ".zip");
        if ($fim === false) return null;

        $inicio = strrpos($str, "/");
        if ($inicio === false) $inicio = 0;
        else $inicio++;

        return substr($str, $inicio, $fim - $inicio);
    }

    private function insertIMAGEM($name){
        $arquivo_id = $this->getArquivoId($name);
        $exame = Exame::where(['arquivo_id' => $arquivo_id])->get()->sortByDesc('id')->first();
        if (!$exame) return;
        $exame->arquivo_imagem = Self::$PATH_LOTES . 'imagens/' . $name;
        $exame->imagem_date = date("Y-m-d H:i:s");
        $exame->updated_at = $exame->imagem_date;
        $exame->save();
    }

    private function doUpload($file) {
        $raw_name = \Str::random(40);
        $file_ext = '.' . strtolower($file->getClientOriginalExtension());
        $file_name = $raw_name . $file_ext;
        $file_path = Self::$PATH_LOTES;
        $full_path = $file_path . $file_name;
        $origin_name = $file->getClientOriginalName();
        $client_name = $file->getClientOriginalName();

        $file->move(
            $this->storePath($file_path),
            $file_name
        );

        return array(
            'file_name' => $file_name,
            'file_type' => 'application/zip',
            'file_path' => $file_path,
            'full_path' => $full_path,
            'raw_name' => $raw_name,
            'orig_name' => $origin_name,
            'client_name' => $client_name,
            'file_ext' => $file_ext,
            'file_size' => \File::size($this->storePath($full_path))
        );

    }

}
