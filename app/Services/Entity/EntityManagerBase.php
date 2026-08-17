<?php

namespace App\Services\Entity;

use Illuminate\Support\Facades\Log;

use App\Services\Db\DbManager;

use Carbon\Carbon;

class EntityManagerBase implements EntityManager
{
    protected $db;

    public function __construct(DbManager $db)
    {
        $this->db = $db;
    }

    private function parseSchema($schema, $key, $value) {
        preg_match_all('/[A-Z][a-z0-9]*/', $key, $matches);

        return $schema;

    }

    private function parse($bundle) {
        $schema = [];
        foreach ($bundle as $key => $value) {
            $schema = $this->parseSchema($schema, $key, $value);
        }
        //Log::debug($schema);
    }

    public function save($entity, $bundle) {
        //Log::Debug("EntityManagerBase.save >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
        //Log::Debug($bundle);
        $this->parse($bundle);
        //Log::Debug("EntityManagerBase.save <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<");
    }

    public function normalize($tableName, $data) {
        $data = is_object($data) ? $data->toArray() : $data;
        //$table = $this->normalizeTableName($tableName);

        foreach($data as &$item) {
            foreach($item as $key => $value)
            {
                $name = $this->normalizeColumnName($key);
                $item[$tableName . $name] = $value;
                unset($item[$key]);
            }
        }
        return $data;
    }

    private function normalizeTableName($name) {
        $name = strtolower($name);
        $result = "";

        foreach(explode("_", $name) as $value)
        {
            $result .= ucfirst($value);
        }
        $name = $result;
        $name = substr($name, -1) == 's' ? substr($name, 0, -1) : $name;
        return ucfirst($name);
    }

    private function normalizeColumnName($name) {
        $result = "";
        foreach(explode("_", $name) as $value)
        {
            $result .= ucfirst(strtolower($value));
        }
        return $result;
    }

    private function alias($entity, $field) {
        return $this->normalizeTableName($entity) . $this->normalizeColumnName($field);
    }

    private function getTableNameByField($field) {
        return str_replace('_id', '', strtolower($field)) . 's';
    }

    private function isRelateTo($field) {
        $table = $this->getTableNameByField($field);
        foreach($this->db->tables() as $key => $value) {
            if ($table == $value) return true;
        }
        return false;
    }

    private function updateTableList($table, &$Map) {
        foreach($Map['tables'] as $key => $value)
            if ($value == $table) return;
        $Map['tables'][] = $table;
    }

    private function notWhereOptions($options) {
        return !array_key_exists("where", $options) || count($options["where"]) % 2 > 0;
    }

    private function where($options, $Map) {

        if ($this->notWhereOptions($options))
            return $Map['where'] ? ' WHERE ' . substr($Map['where'],0,-4) : '';

        $where = $Map['where'] ? $Map['where'] : '';

        $aspas = false;

        foreach($options["where"] as $value)
        {
            $where .= $aspas ? "'" . $value . "'" : $value;
            $aspas = !$aspas;
        }

        return " WHERE " . $where;

    }

    private function insertColumn($obj, $name, $value)
    {
        try {

            $obj->{$name} = $value;

        } catch (\Throwable $e) {

            $obj[$name] = $value;

        }

        return $obj;

    }

    private function getWhere($entity, $child, $result)
    {
        $entity = substr($entity, 0, -1);

        $cond = $child . '.' . $entity . '_id = ';

        $id = $this->normalizeTableName($entity) . 'Id';

        try {

            $value = $result[$id];

        } catch (\Throwable $th) {

            $value = $result->{$id};

        }

        return [ 'where' => [ $cond, $value ] ];

    }

    private function getInsertedFieldName($entity, $child)
    {
        return $this->normalizeTableName($entity) . $this->normalizeColumnName($child);
        return ucfirst(substr($entity,0,-1)) . ucfirst($child);
    }

    private function getOneToManyMapping($entity, $result, $options) {

        if (!$result) return;

        $childs = $this->db->childsOf($entity);

        foreach($result as $key => $value)
        {
            foreach($childs as $child) {

                $name = $this->getInsertedFieldName($entity, $child);

                if (gettype($key) == 'string') {

                    $options = array_merge(
                        $options,
                        $this->getWhere($entity, $child, $result)
                    );

                    $result = $this->insertColumn(
                        $result,
                        $name,
                        $this->findAll(
                            $child,
                            $options,
                            false
                        )
                    );

                    continue;

                }

                $options = array_merge(
                    $options,
                    $this->getWhere($entity, $child, $value)
                );

                $value = $this->insertColumn(
                    $value,
                    $name,
                    $this->findAll(
                        $child,
                        $options,
                        false
                    )
                );

            }
        }

        //Log::Debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>');
        //Log::Debug(print_r($result, true));
        //Log::Debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>');

        return $result;

    }

    private function addColumnsToMap($entity, $Map)
    {
        $columns = $this->db->columns($entity);
        foreach($columns as $column) {
            $alias = $this->alias($entity, $column['name']);
            $Map['columns'][] =
                $entity . '.' . $column['name'] . " AS " . $alias;
            $Map['column_type'][$alias] = $column['type'];
        }
        return $Map;
    }

    private function addRelatesColumnsToMap($entity, $Map)
    {
        $columns = $this->db->columns($entity);
        foreach($columns as $column)
        {
            if (!$this->isRelateTo($column['name'])) continue;
            $table = $this->getTableNameByField($column['name']);
            $Map['tables'][] = $table;
            $Map['where'] .= $entity . '.' . $column['name'] . '=' . $table . '.id AND ';
            $Map = $this->addColumnsToMap($table, $Map);
        }
        return $Map;
    }

    private function getManyToOneMapping($entity, &$Map = null)
    {

        $Map = !$Map ? [
                    'mainTable' => $entity,
                    'columns' => [],
                    'tables' => [ $entity ],
                    'where' => ''
                ] : $Map;

        $Map = $this->addColumnsToMap($entity, $Map);
        $Map = $this->addRelatesColumnsToMap($entity, $Map);

        return $Map;

    }

    private function columns($Map)
    {
        $columns = '';
        foreach($Map['columns'] as $key => $value)
            $columns .= $value . ',';
        return substr($columns,0,-1);
    }

    private function tables($Map)
    {
        $tables = '';
        foreach($Map['tables'] as $key => $value)
            $tables .= $value . ',';
        return substr($tables,0,-1);
    }

    public function findOne($entity, $options = null, $findChilds = false)
    {
        $result = $this->findAll($entity, $options, $findChilds);
        return $result ? json_decode(json_encode($result[0]), FALSE) : null;
    }

    private function doFormat($Map, $result) {
        if (!$result || !$Map) return $result;
        foreach ($result as $data) {
            foreach ($Map['column_type'] as $name => $type) {
                switch ($type) {
                    case 'datetime':
                        $data->{$name} = Carbon::parse($data->{$name})->format('d/m/Y h:i:s');
                        break;
                    default:
                        break;
                }
            }
        }
        return $result;
    }

    private function orderBy($entity, $options) {
        if (!$options) return '';
        if (!array_key_exists("orderBy", $options)) return '';
        if (!array_key_exists($entity, $options['orderBy'])) return '';
        return ' ORDER BY ' . $options['orderBy'][$entity];
    }

    public function findAll($entity, $options = null, $findChilds = false)
    {

        $entity = strtolower($entity);

        $Map = $this->getManyToOneMapping($entity);

        // Log::Debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>');
        // Log::Debug($Map);
        // Log::Debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>');

        $sql = "SELECT " . $this->columns($Map) .
            " FROM " . $this->tables($Map) .
            $this->where($options, $Map) .
            $this->orderBy($entity, $options);

        //Log::Debug('SQL:'.$sql);

        $result = $this->db->select($sql); // Verificar não encontrar nada!

        $result = $this->doFormat($Map, $result);

        $result = $findChilds ? $this->getOneToManyMapping($entity, $result, $options) : $result;

        //if ($result) Log::Debug(print_r($result, true));

        return $result ? json_decode(json_encode($result), FALSE) : null;

    }

}
