<?php

namespace App\Utils;

use setasign\Fpdi\Fpdi;

class FPDIWatermark extends Fpdi
{
    protected $extgstates = array();

    // alpha: real value from 0 (transparent) to 1 (opaque)
    // bm:    blend mode, one of the following:
    //          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
    //          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
    function SetAlpha($alpha, $bm = 'Normal')
    {
        // set alpha for stroking (CA) and non-stroking (ca) operations
        $gs = $this->AddExtGState(array('ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm));
        $this->SetExtGState($gs);
    }

    function AddExtGState($parms)
    {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }

    function SetExtGState($gs)
    {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    function _enddoc()
    {
        if (!empty($this->extgstates) && $this->PDFVersion < '1.4')
            $this->PDFVersion = '1.4';
        parent::_enddoc();
    }

    function _putextgstates()
    {
        for ($i = 1; $i <= count($this->extgstates); $i++) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $parms = $this->extgstates[$i]['parms'];
            $this->_put(sprintf('/ca %.3F', $parms['ca']));
            $this->_put(sprintf('/CA %.3F', $parms['CA']));
            $this->_put('/BM ' . $parms['BM']);
            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    function _putresourcedict()
    {
        parent::_putresourcedict();
        $this->_put('/ExtGState <<');
        foreach ($this->extgstates as $k => $extgstate)
            $this->_put('/GS' . $k . ' ' . $extgstate['n'] . ' 0 R');
        $this->_put('>>');
    }

    function _putresources()
    {
        $this->_putextgstates();
        parent::_putresources();
    }

    function StartTransform(){
        //save the current graphic state
        $this->_out('q');
    }

    function ScaleX($s_x, $x='', $y=''){
        $this->Scale($s_x, 100, $x, $y);
    }
    function ScaleY($s_y, $x='', $y=''){
        $this->Scale(100, $s_y, $x, $y);
    }
    function ScaleXY($s, $x='', $y=''){
        $this->Scale($s, $s, $x, $y);
    }
    function Scale($s_x, $s_y, $x='', $y=''){
        if($x === '')
            $x=$this->x;
        if($y === '')
            $y=$this->y;
        if($s_x == 0 || $s_y == 0)
            $this->Error('Please use values unequal to zero for Scaling');
        $y=($this->h-$y)*$this->k;
        $x*=$this->k;
        //calculate elements of transformation matrix
        $s_x/=100;
        $s_y/=100;
        $tm[0]=$s_x;
        $tm[1]=0;
        $tm[2]=0;
        $tm[3]=$s_y;
        $tm[4]=$x*(1-$s_x);
        $tm[5]=$y*(1-$s_y);
        //scale the coordinate system
        $this->Transform($tm);
    }

    function MirrorH($x=''){
        $this->Scale(-100, 100, $x);
    }
    function MirrorV($y=''){
        $this->Scale(100, -100, '', $y);
    }
    function MirrorP($x='',$y=''){
        $this->Scale(-100, -100, $x, $y);
    }
    function MirrorL($angle=0, $x='',$y=''){
        $this->Scale(-100, 100, $x, $y);
        $this->Rotate(-2*($angle-90),$x,$y);
    }

    function TranslateX($t_x){
        $this->Translate($t_x, 0);
    }
    function TranslateY($t_y){
        $this->Translate(0, $t_y);
    }
    function Translate($t_x, $t_y){
        //calculate elements of transformation matrix
        $tm[0]=1;
        $tm[1]=0;
        $tm[2]=0;
        $tm[3]=1;
        $tm[4]=$t_x*$this->k;
        $tm[5]=-$t_y*$this->k;
        //translate the coordinate system
        $this->Transform($tm);
    }

    function Rotate($angle, $x='', $y=''){
        if($x === '')
            $x=$this->x;
        if($y === '')
            $y=$this->y;
        $y=($this->h-$y)*$this->k;
        $x*=$this->k;
        //calculate elements of transformation matrix
        $tm[0]=cos(deg2rad($angle));
        $tm[1]=sin(deg2rad($angle));
        $tm[2]=-$tm[1];
        $tm[3]=$tm[0];
        $tm[4]=$x+$tm[1]*$y-$tm[0]*$x;
        $tm[5]=$y-$tm[0]*$y-$tm[1]*$x;
        //rotate the coordinate system around ($x,$y)
        $this->Transform($tm);
    }

    function SkewX($angle_x, $x='', $y=''){
        $this->Skew($angle_x, 0, $x, $y);
    }
    function SkewY($angle_y, $x='', $y=''){
        $this->Skew(0, $angle_y, $x, $y);
    }
    function Skew($angle_x, $angle_y, $x='', $y=''){
        if($x === '')
            $x=$this->x;
        if($y === '')
            $y=$this->y;
        if($angle_x <= -90 || $angle_x >= 90 || $angle_y <= -90 || $angle_y >= 90)
            $this->Error('Please use values between -90° and 90° for skewing');
        $x*=$this->k;
        $y=($this->h-$y)*$this->k;
        //calculate elements of transformation matrix
        $tm[0]=1;
        $tm[1]=tan(deg2rad($angle_y));
        $tm[2]=tan(deg2rad($angle_x));
        $tm[3]=1;
        $tm[4]=-$tm[2]*$y;
        $tm[5]=-$tm[1]*$x;
        //skew the coordinate system
        $this->Transform($tm);
    }

    function Transform($tm){
        $this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F cm', $tm[0],$tm[1],$tm[2],$tm[3],$tm[4],$tm[5]));
    }

    function StopTransform(){
        //restore previous graphic state
        $this->_out('Q');
    }
}
