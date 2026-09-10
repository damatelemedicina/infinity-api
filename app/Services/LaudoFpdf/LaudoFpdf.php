<?php

namespace App\Services\LaudoFpdf;

class LaudoFpdf
{
    protected $data;

    protected PDF $pdf;

    protected $left;
    protected $top;
    protected $w;
    protected $hLine;

    protected $B;
    protected $I;
    protected $U;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function laudoUS()
    {
        $data = $this->data;

        $pdf = new PDF();
        $pdf->AliasNbPages();
        $this->pdf = $pdf;

        $pdf->SetMargins(10, 10);
        $pdf->SetHeaderImage($data['cabecalho']);
        $pdf->SetDrawColor(0);
        $pdf->AddPage();

        /*==========================================================*/
        /* Quando tiver uma imagem no cabecalho, o top deve ser     */
        /* o height da imagem mais o pagebreak                      */
        /*==========================================================*/
        $top = $pdf->GetY();
        $left = 10;
        $w = $pdf->GetPageWidth() - 20;
        $hLine = 5;

        $this->left = $left;
        $this->top = $top;
        $this->w = $w;
        $this->hLine = $hLine;

        // $pdf->SetXY($left, $top + 10);
        $this->laudoTitle("LAUDO DE {$data["nome"]} DIGITAL - Nº {$data["numero"]}");
        $pdf->Ln($hLine);

        $data["paciente"] = mb_trim(mb_convert_case($data["paciente"], MB_CASE_UPPER));
        $data["contratante"] = mb_trim(mb_convert_case($data["contratante"], MB_CASE_UPPER));
        $this->row([
            "Nome: {$data["paciente"]}",
            "Empresa: {$data["contratante"]}",
        ]);

        $data["idade"] = mb_trim(mb_convert_case($data["idade"], MB_CASE_UPPER));
        $data["sexo"] = mb_trim(mb_convert_case($data["sexo"], MB_CASE_UPPER));
        $this->row([
            "{$data["idade"]}",
            "Sexo: {$data["sexo"]}",
        ]);

        $data["documentos"] = mb_trim(mb_convert_case($data["documentos"], MB_CASE_UPPER));
        $this->row([$data["documentos"]]);

        $pdf->Ln($hLine);
        $this->laudoTitle("EXAME");
        $pdf->Ln($hLine);

        $data["data_exame"] = mb_trim(mb_convert_case($data["data_exame"], MB_CASE_UPPER));
        $data["data_laudo"] = mb_trim(mb_convert_case($data["data_laudo"], MB_CASE_UPPER));
        $this->row([
            "Dt. Exame: {$data["data_exame"]}",
            "Dt. Laudo: {$data["data_laudo"]}",
        ]);

        $data["medico_solicitante"] = mb_trim(mb_convert_case($data["medico_solicitante"], MB_CASE_UPPER));
        $data["crm_solicitante"] = mb_trim(mb_convert_case($data["crm_solicitante"], MB_CASE_UPPER));
        $data["motivo"] = mb_trim(mb_convert_case($data["motivo"], MB_CASE_UPPER));
        $this->row([
            "Médico Solicitante: {$data["medico_solicitante"]}",
            "CRM: {$data["crm_solicitante"]}",
            "Motivo Exame: {$data["motivo"]}",
        ]);

        $pdf->Ln($hLine);
        $this->laudoTitle("ANÁLISE");
        $pdf->Ln($hLine);

        if (!$data['impossibilitado']) {
            $this->row([
                $data["modelo_content"],
            ]);
        } else {
            $this->pdf->SetFont('Arial', 'BI', 14);
            $pdf->MultiCell($w, $hLine, mb_convert_encoding("L A U D O  I M P O S S I B I L I T A D O", 'ISO-8859-1', 'UTF-8'), 0, 'C');

            $this->pdf->SetFont('Arial', '', 9);
            foreach ($data['impossibilitado'] as $value) {
                $pdf->SetX($left);
                $pdf->Cell(3, $hLine, '*', 0, 0);
                $pdf->SetX($left + 3);
                $pdf->MultiCell($w - 3, $hLine, mb_convert_encoding("{$value}", 'ISO-8859-1', 'UTF-8'), 0, 'L');
            }

            $pdf->Ln($hLine);
            $this->row([
                $data["observacoes_medico"],
            ]);
        }
        
        $pdf->Ln($hLine * 2);
        $this->row([
            $data["cliente_mensagem"],
        ]);

        if ($data["laudo_imagem"] && !$data["impossibilitado"]) {
            $pdf->Ln($hLine);
            $befPage = $pdf->PageNo();
            $y = $pdf->GetY();

            $wImage = ($w * .6);
            $wText = (($w - $wImage) - 3);

            $pdf->Image($data["laudo_imagem"], $left, null, $wImage);
            $aftPage = $pdf->PageNo();

            $y = ($befPage == $aftPage) ? $y : $top;

            $pdf->SetY($y);

            $nLeft = ($left + ($wImage + 3));

            if ($data["signer"]) {
                $xSigner = $nLeft + ($wText * .5) - 10;

                $pdf->Image($data["signer"], $xSigner, null, 20);

                $this->pdf->SetTextColor(255, 0, 0);
                $this->pdf->SetFont('Courier', 'B', 10);
                $pdf->SetXY($nLeft, $pdf->GetY());
                $pdf->MultiCell($wText, $hLine, mb_convert_encoding($data["protocolo"], 'ISO-8859-1', 'UTF-8'), 0, 'C');
                $this->pdf->SetFont('Arial', '', 9);
                $this->pdf->SetTextColor(0);
            }

            if ($data["assinatura"]) {
                $pdf->Ln($hLine);
                $pdf->Image($data["assinatura"], ($nLeft + ($wText * .5) - 8), null, 16);
            }

            if ($data["qrcode"]) {
                $pdf->Ln($hLine);
                $pdf->Image($data["qrcode"], ($nLeft + ($wText * .5) - 8), null, 16);
                $pdf->SetXY($nLeft, $pdf->GetY());
                $pdf->MultiCell($wText, $hLine, mb_convert_encoding("Aponte a câmera do seu celular para baixar seu laudo.", 'ISO-8859-1', 'UTF-8'), 0, 'C');
            }
        } else {
            $y = $pdf->GetY();
            $befPage = $pdf->PageNo();
            if ($data["signer"]) {
                $pdf->Image($data["signer"], ($left + (($w * .2 * .5) - 10)), null, 20);

                $this->pdf->SetTextColor(255, 0, 0);
                $this->pdf->SetFont('Courier', 'B', 10);
                $pdf->SetXY($left, $pdf->GetY());
                $pdf->MultiCell(($w * .2), $hLine, mb_convert_encoding($data["protocolo"], 'ISO-8859-1', 'UTF-8'), 0, 'C');
                $this->pdf->SetFont('Arial', '', 9);
                $this->pdf->SetTextColor(0);
            }

            $aftPage = $pdf->PageNo();
            $y = ($befPage == $aftPage) ? $y : $top;

            if ($data["assinatura"]) {
                $pdf->Image($data["assinatura"], ($left + ($w * .2) + ($w * .5 * .5) - 16), $y, 32);
            }

            if ($data["qrcode"]) {
                $pdf->Image($data["qrcode"], ($left + ($w * .7) + ($w * .3 * .5) - 12), $y, 24);
                
                $pdf->SetX(($left + ($w * .7)));
                $pdf->MultiCell(($w * .3), $hLine, mb_convert_encoding("Aponte a câmera do seu celular para baixar seu laudo.", 'ISO-8859-1', 'UTF-8'), 0, 'C');
            }
        }

        return $pdf->Output('S');
    }

    private function laudoTitle(string $title)
    {
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell($this->w, .25, '', 1, 1, '', 1);
        $this->pdf->Cell($this->w, $this->hLine * 2, mb_convert_encoding($title, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->pdf->Cell($this->w, .25, '', 1, 1, '', 1);
    }

    private function row($columns = [])
    {
        $cntCol = count($columns);

        if ($cntCol > 0) {
            $this->pdf->SetFont('Arial', '', 9);
            $w = $this->w / $cntCol;
            $y = $this->pdf->GetY();

            for ($i = 0; $i < $cntCol; $i++) {
                $column = $columns[$i];

                $x = $this->left + ($w * $i);

                $this->pdf->SetXY($x, $y);

                $html = trim(strip_tags($column, "<b><u><i><p><br><strong><em><tr><blockquote>"));
                $html = str_replace("&nbsp;", " ", $html);
                $html = str_replace("&amp;", "&", $html);
                $html = str_replace("&quot;", "\"", $html);
                $html = str_replace("\n;", " ", $html);
                $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

                if (count($a) > 1) {
                    foreach ($a as $i => $e) {
                        if ($i % 2 == 0) {
                            $this->pdf->Write($this->hLine, mb_convert_encoding($e, 'ISO-8859-1', 'UTF-8'));
                        } else {
                            if ($e[0] == '/') {
                                switch (strtoupper(substr($e, 1))) {
                                    case 'B':
                                    case 'STRONG':
                                        $this->SetStyle('B', false);
                                        break;
                                    case 'I':
                                    case 'EM':
                                        $this->SetStyle('I', false);
                                        break;
                                    case 'U':
                                        $this->SetStyle('U', false);
                                        break;
                                }
                            } else {
                                $a2 = explode(' ', $e);
                                $tag = strtoupper(array_shift($a2));

                                /*==========================================================*/
                                /* Evita enter desnecessario no comeco do texto             */
                                /*==========================================================*/
                                if ($i == 1 && $tag == 'P') continue;

                                switch ($tag) {
                                    case 'B':
                                    case 'STRONG':
                                        $this->SetStyle('B', true);
                                        break;
                                    case 'I':
                                    case 'EM':
                                        $this->SetStyle('I', true);
                                        break;
                                    case 'U':
                                        $this->SetStyle('U', true);
                                        break;
                                    case 'TR':
                                    case 'BLOCKQUOTE':
                                    case 'BR':
                                        $this->pdf->Ln($this->hLine);
                                        break;
                                    case 'P':
                                        $this->pdf->Ln($this->hLine * 2);
                                        break;
                                }
                            }
                        }
                    }
                } else {
                    $this->pdf->MultiCell($w, $this->hLine, mb_convert_encoding($column, 'ISO-8859-1', 'UTF-8'), 0, 'L');
                }
            }
        }
    }

    private function SetStyle($tag, $enable)
    {
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach (array('B', 'I', 'U') as $s) {
            if ($this->$s > 0)
                $style .= $s;
        }
        $this->pdf->SetFont('', $style);
    }
}
