<?php

namespace App\Services\DateTime;
  
interface DateTimeService {
    public function toDateFirstTime($data);
    public function toDateLastTime($data);
}