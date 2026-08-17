<?php

namespace App\Services\DateTime;

use Illuminate\Support\Facades\Log;

class DateTimeServiceBase implements DateTimeService {
    
    public function __construct()
    {
    }

    public function toDateFirstTime($data) {
        if (!$data) {
            return date('Y-m-d') . ' 00:00:00';
        }
        return substr($data, 6, 4) . '-' . 
            substr($data, 3, 2) . '-' . 
            substr($data, 0, 2) . ' 00:00:00';
    }

    public function toDateLastTime($data) {
        if (!$data) {
            return date('Y-m-d') . ' 23:59:59';
        }
        return substr($data, 6, 4) . '-' . 
            substr($data, 3, 2) . '-' . 
            substr($data, 0, 2) . ' 23:59:59';
    }

}