<?php

namespace App\ViewModels;

use Spatie\ViewModels\ViewModel;

use Illuminate\Support\Facades\Log;

use DB;
use Throwable;

class BaseViewModel extends ViewModel
{

    public static $ARRAY_EXTRACT_ONE = true;

    public function __construct()
    {
        //
    }

    protected static function callStoredProcedures($sql) {
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query($sql);
        $rs = [];
        do {
            $rows = $stmt->fetchAll($pdo::FETCH_ASSOC);
            $rs[] = $rows;
        } while ($stmt->nextRowset());
        return $rs;
    }

    protected static function callStoredProcedure($sql, $error = NULL, $extract = false) {

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

        if (!$rs) {
            return [];
        }

        $rs = ($extract && count($rs[0]) >= 1) ? $rs[0][0] : $rs[0];

        return $rs;

    }

}
