<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoExame extends Model
{
    use HasFactory;

    public function campos() {
        return TipoExameCampo::where('tipo_exame_id', $this->id)->get();
    }

    static function upsert($array) {
        $tipo = Self::where('id', $array['id'])->first();
        if ($tipo) {
            foreach ($array as $key => $value) {
                $tipo->{$key} = $value;
            }
            $tipo->save();
            return;
        }
        Self::insert($array);
    }

    public function delete() {
        //$this->campos()->delete();
        TipoExameCampo::where('tipo_exame_id', $this->id)->delete();
        return parent::delete();
    }

}
