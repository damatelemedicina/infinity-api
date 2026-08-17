<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoteController extends Controller
{
    private static $PNG_SHORTER = false;
    private static $TARGET = '/^.*\.(wxml|xml|datest|dte|dat|plg|tep|eeg|pdf|txt|dcm|oit)$/i';
    private static $HIGH_PRIORITY = '/^.*\.(xml|datest|dte)$/i';

    private $UBERMED = 38;
    private $LINEFEED = 10;

    function __construct() {
        parent::__construct();
    }
}
