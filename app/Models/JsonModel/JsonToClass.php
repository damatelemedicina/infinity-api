<?php

namespace App\Models\JsonModel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class JsonToClass implements CastsAttributes
{
    protected $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return new $this->class();
        }

        return new $this->class(null, $value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof $this->class && method_exists($value, 'toJson')) {
            $value = ($value instanceof string ? new $this->class($attributes[$model->getKeyName()]) : $value)->toJson();
        }

        return $value;
    }
}
