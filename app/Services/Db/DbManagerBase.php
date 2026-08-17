<?php

namespace App\Services\Db;

use Illuminate\Support\Facades\Log;

use DB;
use Exception;
use PDOException;
use Throwable;

use Illuminate\Support\Facades\Schema;

class DbManagerBase implements DbManager
{
    private static $CACHE = [ 
        'result' => null, 
        'hash' => null,
        'columns' => [],
    ];

    public function __construct()
    {
    }
    
    public function callStoredProcedure($sql, $error = NULL, $extract = false) {

        Log::Debug('================== REQUEST ==================');
        Log::Debug($sql);
        Log::Debug('================== REQUEST ==================');

        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query($sql);
        $rs = [];
        
        try {

            do {
                $rows = $stmt->fetchAll($pdo::FETCH_ASSOC);
                //$rows = $stmt->fetchAll($pdo::FETCH_NUM);
                if ($rows) $rs[] = $rows;
            } while ($stmt->nextRowset());
    
            if (count($rs) == 0 && $error) throw $error;
    
        } catch (Throwable $th) {

            throw $th;

        }

        if (!$rs) return [];
        
        return ($extract && count($rs[0]) == 1) ? $rs[0][0] : $rs[0];

    }

    public function select($sql) {
        
        try {

            $hash = hash('ripemd160', $sql);

            if (Self::$CACHE['hash'] == $hash) 
                return Self::$CACHE['result'];

            //Log::Debug('------------------- SQL -------------------');
            //Log::Debug('HASH: ' . $hash);
            //Log::Debug($sql);
            //Log::Debug('-------------------------------------------');
            
            Self::$CACHE['hash']   = $hash;
            Self::$CACHE['result'] = DB::select($sql);

            return Self::$CACHE['result'];
            //return $this->parse(Self::$CACHE['result']);

        } catch (Exception $e) {
           //abort($e instanceof PDOException ? 503 : 500);
        } 
    }

    public function tables() {

        $tables = [];
        
        try {

            foreach (DB::select('SHOW TABLES') as $table) {
                foreach ($table as $key => $value)
                    $tables[] = strtolower($value);
            }

        } catch (Exception $e) {
            //throw $th;
        }

        return $tables;

    }

    private function isChildOf($table, $target) 
    {
        $target = substr($target, 0, -1);
        foreach ($this->columns($table) as $key => $column)
            if ($column['name'] == $target . '_id') return true;
        return false;
    }

    public function childsOf($target) 
    {
        $childs = [];
        foreach ($this->tables() as $key => $table) {
            if ($this->isChildOf($table, $target)) $childs[] = strtolower($table);
        }
        return $childs;
    }

    public function columns($table)
    {
        try {
            if (array_key_exists($table, Self::$CACHE['columns'])) {
                return Self::$CACHE['columns'][$table];
            }
            $columns = [];

            $schema = Schema::getConnection()->getDoctrineSchemaManager();
            foreach($schema->listTableColumns($table) as $column) {
                $columns[] = array(
                    'name' => $column->getName(), 
                    'type' => $column->getType()->getName(),
                );
            }
            
            Self::$CACHE['columns'][$table] = $columns;
            return $columns;

        } catch (Exception $e) {

           //abort($e instanceof PDOException ? 503 : 500);

        } 
    }

}