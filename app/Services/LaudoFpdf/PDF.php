<?php

namespace App\Services\LaudoFpdf;

use FPDF;

class PDF extends FPDF
{
    public $headerImage;
    public $footerImage;

    // Page footer
    function Header()
    {
        if (empty($this->headerImage) === false) {
            $wimage = ($this->GetPageWidth() - $this->lMargin - $this->rMargin);
            $this->SetY(0);
            $this->Image($this->headerImage, $this->lMargin, null, $wimage);
        }
    }

    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, mb_convert_encoding('Página', 'ISO-8859-1', 'UTF-8') . ' ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }

    function SetHeaderImage($path)
    {
        $this->headerImage = $path;
    }

    function SetFooterImage($path)
    {
        $this->footerImage = $path;
    }
}
