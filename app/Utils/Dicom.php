<?php

namespace App\Utils;

require_once app_path()
    .DIRECTORY_SEPARATOR
    .'..'
    .DIRECTORY_SEPARATOR
    .'library'
    .DIRECTORY_SEPARATOR
    .'nanodicom.php';

class Dicom {
    static function getInstance($filename) {
		return \Nanodicom::factory($filename);
	}
}
