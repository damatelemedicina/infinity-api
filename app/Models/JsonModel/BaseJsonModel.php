<?php

namespace App\Models\JsonModel;

use ReflectionClass;

abstract class BaseJsonModel
{
    public function __construct($obj, $json)
    {
        $possibles = array_column((new ReflectionClass($this))->getProperties(), 'name');
        foreach ($possibles as $key) {
            if (is_array($json) && array_key_exists($key, $json)) {
                $obj->$key = $json[$key];
            } else {
                $obj->$key = null;
            }
        }
    }

    /**
     * @return  string
     */
    public function toJson(): string
    {
        return json_encode(get_object_vars($this));
    }
}
