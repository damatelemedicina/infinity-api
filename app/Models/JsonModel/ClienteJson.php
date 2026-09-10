<?php

namespace App\Models\JsonModel;

use App\Models\Cliente;

class ClienteJson extends BaseJsonModel
{

    /**
     * Mensagem para cliente urgência/emergência
     * 
     * @var null|string
     */
    public ?string $mensagem_urgencia_emergencia = null;

    public function __construct($id = null, string $jsonString = null)
    {
        if (empty($jsonString) && empty($id) == false) {
            $jsonString = Cliente::select('json')->where('id', $id)->first()->json ?? '[]';
            if (!empty($jsonString) && $jsonString instanceof BaseJsonModel)
                $jsonString = $jsonString->toJson();
        }
        $json = json_decode($jsonString ?? '[]', JSON_OBJECT_AS_ARRAY);
        parent::__construct($this, $json);
    }
}
