<?php

namespace App\Services\Db;
  
interface DbManager
{
    public function select($sql);
    public function columns($table);
    public function tables();
    public function childsOf($table);
    public function callStoredProcedure($sql, $error = NULL, $extract = false);
}