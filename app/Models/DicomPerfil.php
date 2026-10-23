<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DicomPerfil extends Model
{
    protected $table = 'dicom_perfis';
    protected $guarded = [];

    protected $casts = [
        'ativo' => 'boolean',
        'usa_lateralidade' => 'boolean',
        'oit_por_texto' => 'boolean',
        'prioridade' => 'integer',
    ];

    /** @return string[] nomes de tag, na ordem de prioridade */
    public function tipoExameTags(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tipo_exame_tags))));
    }
}
