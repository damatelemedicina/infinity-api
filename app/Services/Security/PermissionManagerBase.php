<?php

namespace App\Services\Security;

use App\Services\Db\DbManager;
use Illuminate\Support\Facades\Log;

class PermissionManagerBase implements PermissionManager
{
    protected $db;

    public function __construct(DbManager $db)
    {
        $this->db = $db;
        //Log::Debug('PermissionManagerBase instanciado!');
    }    
}