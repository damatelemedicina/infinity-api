<style>
    div {
        margin: 0px;
        border: 0px;
        padding: 0px;
        font-size: 10px;
        font-family: "Helvetica";
    }
    .box {
        border-bottom: 1px dashed black;
        margin-bottom: 5px;
    }
    .topic {
        font-weight: bold;
    }
    .p9 {
        font-size: 9px;
        font-style: italic;
    }
    .qrcode {
        transform: scale(0.8);
    }
</style>

<div>
    <div style="height:30px;">
        <img src="{{$logo}}">
    </div>
    <div style="font-size:10px;padding-top:10px;"><b>FOLHA DE LEITURA RADIOLÓGICA - CLASSIFICAÇÃO INTERNACIONAL DE RADIOGRAFIAS DE PNEUMOCONIOSE - OIT 2011</b></div>
    <hr>

    <!-- Dados paciente --->
    <div class="box" style="height: 40px;">
        <div style="float: left;width:50%;">
            <span>Nome:</span> <span>{{$paciente}}</span><br />
            <span>Médico:</span> <span>{{$medico_solicitante}}</span><br />
            <span>Empresa:</span> <span>{{$contratante}}</span>
        </div>
        <div style="float: right; width:50%;">
            <span>Motivo Exame:</span> <span>{{$motivo}}</span><br />
            <span>{{$idade}}</span> Sexo: <span>{{$sexo}}</span><br />
            <span>RG:</span> <span>{{$rg}}</span>
        </div>
    </div>
    <!-- Dados exame -->
    <div class="box" style="height: 40px;">
        <div style="float: left; width: 50%;">
            <span>Data do Exame:</span> <span>{{$data_exame}}</span><br />
            <span>RX Digital:</span>
            <span>
                @if ($rx_digital == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>SIM</span>
            <span>
                @if ($rx_digital == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>NAO</span>
        </div>
        <div style="float: right; width: 50%;">
            <span>Data da Leitura:</span><span>{{$data_leitura}}</span><br />
            <span>Leitura em Negatoscópio:</span>
            <span>
                @if ($negatoscopio == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>SIM</span>
            <span>
                @if ($negatoscopio == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>NAO</span>
        </div>
    </div>
    <!-- 1A e 1B -->
    <div class="box">
        <div style="height: 35px;">
            <div style="float: left;width: 270px;padding-right: 20px; border-right: 1px dashed black">
                <span class="topic">1A) Qualidade técnica:</span>
                <span>
                    @if ($qualidade == '1') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                    </span><span>1</span>
                <span>
                    @if ($qualidade == '2') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                    </span><span>2</span>
                <span>
                    @if ($qualidade == '3') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                    </span><span>3</span>
                <span>
                    @if ($qualidade == '4') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                    </span><span>4</span>
            </div>
            <div>
                <span class="topic">&nbsp;&nbsp;&nbsp;1B) Radiografia Normal:</span>
                <span>
                    @if ($normal == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                    </span><span>SIM</span>
                <span>
                    @if ($normal == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                    </span><span>NAO</span>
            </div>
        </div>
        <div>
            <span>Comentários:</span>
            <span>{{$comentarios_qualidade}}</span>
        </div>
    </div>
    <!-- 2A -->
    <div class="box">
        <div>
            <span class="topic">2A) Alguma anormalidade de parênquima consistente com pneumoconiose:</span>
            <span>
                @if ($anormalidade_parenquima == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>SIM</span>
            <span>
                @if ($anormalidade_parenquima == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>NAO</span>
        </div>
    </div>

    <!-- 2B e 2C -->
    <div class="box" style="height: 100px;">
        <div style="width:60%;">
            <div>
                <span class="topic">2B) Pequenas Opacidades</span>
            </div>
            <div>
                <div style="float:left;width:40%;">
                    <div>
                        <span>A) Formas e Tamanhos</span><br>
                    </div>
                    <div>
                        <div style="float:left;width:50%;">
                            <span>Primária</span><br>
                            <span>
                                @if ($primarias == 'P') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>p</span>
                            <span>
                                @if ($primarias == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>s</span><br>
                            <span>
                                @if ($primarias == 'Q') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>q</span>
                            <span>
                                @if ($primarias == 'T') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>t</span><br>
                            <span>
                                @if ($primarias == 'R') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>r</span>
                            <span>
                                @if ($primarias == 'U') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>u</span><br><br>
                        </div>
                        <div style="float:right;width:50%;">
                            <span>Secundária</span><br>
                            <span>
                                @if ($secundarias == 'P') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>p</span>
                            <span>
                                @if ($secundarias == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>s</span><br>
                            <span>
                                @if ($secundarias == 'Q') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>q</span>
                            <span>
                                @if ($secundarias == 'T') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>t</span><br>
                            <span>
                                @if ($secundarias == 'R') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>r</span>
                            <span>
                                @if ($secundarias == 'U') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                            </span><span>u</span><br><br>
                        </div>
                    </div>
                </div>
                <div style="float:left;width:30%;">
                    <div>
                        <span>B) Zonas</span>
                    </div>
                    <div>
                        <span>
                            @if ($zonas_d1 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>D</span>
                        <span>
                            @if ($zonas_e1 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>E</span><br>
                        <span>
                            @if ($zonas_d2 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>D</span>
                        <span>
                            @if ($zonas_e2 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>E</span><br>
                        <span>
                            @if ($zonas_d3 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>D</span>
                        <span>
                            @if ($zonas_e3 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>E</span><br>
                    </div>
                </div>
                <div style="float:left;width:30%;">
                    <div>
                        <span>C) Profusão</span>
                    </div>
                    <div>
                        <span>
                            @if ($profusao == '0/-') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>0/-</span>
                        <span>
                            @if ($profusao == '0/0') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>0/0</span>
                        <span>
                            @if ($profusao == '0/1') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>0/1</span><br>
                        <span>
                            @if ($profusao == '1/0') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>1/0</span>
                        <span>
                            @if ($profusao == '1/1') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>1/1</span>
                        <span>
                            @if ($profusao == '1/2') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>1/2</span><br>
                        <span>
                            @if ($profusao == '2/1') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>2/1</span>
                        <span>
                            @if ($profusao == '2/2') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>2/2</span>
                        <span>
                            @if ($profusao == '2/3') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>2/3</span><br>
                        <span>
                            @if ($profusao == '3/2') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>3/2</span>
                        <span>
                            @if ($profusao == '3/3') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>3/3</span>
                        <span>
                            @if ($profusao == '3+') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>3+</span><br>
                    </div>
                </div>
            </div>
        </div>
        <div style="float:left;width:40%">
            <div>
                <span class="topic">2C) Grandes Opacidades</span>
            </div>
            <div>
                <span>
                    @if ($grd_opacidade == '0') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0</span>
                <span>
                    @if ($grd_opacidade == 'A') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>A</span>
                <span>
                    @if ($grd_opacidade == 'B') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>B</span>
                <span>
                    @if ($grd_opacidade == 'C') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>C</span>
            </div>
        </div>
    </div>

    <!-- 3A -->
    <div class="box">
        <span class="topic">3A) Alguma anormalidade pleural consistente com pneumoconiose:</span>
        <span>
            @if ($anormalidade_pleural == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif            </span><span>SIM</span>
        <span>
            @if ($anormalidade_pleural == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif            </span><span>NAO</span>
    </div>
    <!-- 3B -->
    <div class="box">
        <div>
            <span class="topic">3B) Placas Pleurais:</span>
            <span>
                @if ($placas_pleurais == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>SIM</span>
            <span>
                @if ($placas_pleurais == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>NAO</span>
        </div>
        <div style="height:90px;">
            <div style="float:left;width:40%">
                <br>
                <span>Parede em perfil</span><br>
                <span>Frontal</span><br>
                <span>Diafragma</span><br>
                <span>Outros locais</span>
            </div>
            <div style="float:left;width:30%">
                <span>Local</span><br>
                <span>
                    @if ($placas_parede_local_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_parede_local_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_parede_local_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($placas_frontal_local_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_frontal_local_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_frontal_local_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($placas_diafrag_local_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_diafrag_local_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_diafrag_local_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($placas_outros_local_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                        @if ($placas_outros_local_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_outros_local_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
            </div>
            <div style="float:left;width:30%">
                <span>Calcificação</span><br>
                <span>
                    @if ($placas_parede_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_parede_calcif_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_parede_calcif_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($placas_frontal_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_frontal_calcif_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_frontal_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($placas_diafrag_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_diafrag_calcif_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_diafrag_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($placas_outros_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_outros_calcif_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_outros_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
            </div>
        </div>
        <div style="height:90px;">
            <div style="float:left;width:50%">
                <span>Extensão da parede(combinado perfil e frontal)</span><br>
                <span>
                    @if ($placas_extensao_od_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_extensao_od_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_extensao_od_1 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>1</span>
                <span>
                    @if ($placas_extensao_od_2 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>2</span>
                <span>
                    @if ($placas_extensao_od_3 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>3</span><br>
                <span>
                    @if ($placas_extensao_oe_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($placas_extensao_oe_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
                <span>
                    @if ($placas_extensao_oe_1 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>1</span>
                <span>
                    @if ($placas_extensao_oe_2 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>2</span>
                <span>
                    @if ($placas_extensao_oe_3 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>3</span><br>
                <span class="p9">
                    Até 1/4 da parede lateral = 1<br>
                    1/4 à 1/2 da parede lateral = 2<br>
                    1/2 da parede lateral = 3
                </span>
            </div>
            <div style="float:left;width:50%;">
                <span>Largura(opcional) (mínimo de 3mm para marcação)</span><br>
                <span>
                    @if ($placas_largura_d_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($placas_largura_d_A == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>a</span>
                <span>
                    @if ($placas_largura_d_B == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>b</span>
                <span>
                    @if ($placas_largura_d_C == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>c</span><br>
                <span>
                    @if ($placas_largura_e_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
                <span>
                    @if ($placas_largura_e_A == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>a</span>
                <span>
                    @if ($placas_largura_e_B == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>b</span>
                <span>
                    @if ($placas_largura_e_C == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>c</span><br>
                <span class="p9">
                    3 à 5mm = A<br>
                    5 à 10mm = B<br>
                    > 10mm = C
                </span>
            </div>
        </div>
    </div>
    <!-- 3C -->
    <div class="box">
        <span class="topic">3C) Obliteração do seio costofrênico</span><br>
        <span>
            @if ($obliteracao_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
        </span><span>0 </span>
        <span>
            @if ($obliteracao_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
        </span><span>D</span>
        <span>
            @if ($obliteracao_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
        </span><span>E</span>
    </div>
    <!-- 3D -->
    <div class="box">
        <div>
            <span class="topic">3D) Espessamento pleural difuso:</span>
            <span>
                @if ($espessamento_pleural == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>SIM</span>
            <span>
                @if ($espessamento_pleural == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
            </span><span>NAO</span>
        </div>
        <div style="height: 50px;">
            <div style="float:left;width:40%">
                <br>
                <span>Parede em Perfil:</span><br>
                <span>Frontal:</span>
            </div>
            <div style="float:left;width:30%">
                <span>Local</span><br>
                <span>
                    @if ($espes_parede_local_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($espes_parede_local_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($espes_parede_local_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($espes_frontal_local_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($espes_frontal_local_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($espes_frontal_local_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
            </div>
            <div style="float:left;width:30%">
                <span>Calcificação</span><br>
                <span>
                    @if ($espes_parede_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($espes_parede_calcif_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($espes_parede_calcif_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span><br>
                <span>
                    @if ($espes_frontal_calcif_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($espes_frontal_calcif_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($espes_frontal_calcif_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
            </div>
        </div>
        <div style="height: 80px;">
            <div style="float:left;width:50%;">
                <span>Extensão da parede(combinado perfil e frontal)</span><br>
                <span>
                    @if ($espes_extensao_od_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>0 </span>
                <span>
                    @if ($espes_extensao_od_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($espes_extensao_od_1 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>1</span>
                <span>
                    @if ($espes_extensao_od_2 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>2</span>
                <span>
                    @if ($espes_extensao_od_3 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>3</span><br>
                <span>
                    @if ($espes_extensao_oe_0 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                        </span><span>0 </span>
                <span>
                    @if ($espes_extensao_oe_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
                <span>
                    @if ($espes_extensao_oe_1 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>1</span>
                <span>
                    @if ($espes_extensao_oe_2 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>2</span>
                <span>
                    @if ($espes_extensao_oe_3 == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>3</span><br>
                <span class="p9">
                    Até 1/4 da parede lateral = 1<br>
                    1/4 à 1/2 da parede lateral = 2<br>
                    1/2 da parede lateral = 3
                </span>
            </div>
            <div style="float:left;width:50%;">
                <span>Largura(opcional) (mínimo de 3mm para marcação)</span><br>
                <span>
                    @if ($espes_largura_d_D == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>D</span>
                <span>
                    @if ($espes_largura_d_A == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>a</span>
                <span>
                    @if ($espes_largura_d_B == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>b</span>
                <span>
                    @if ($espes_largura_d_C == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>c</span><br>
                <span>
                    @if ($espes_largura_e_E == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>E</span>
                <span>
                    @if ($espes_largura_e_A == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>a</span>
                <span>
                    @if ($espes_largura_e_B == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>b</span>
                <span>
                    @if ($espes_largura_e_C == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
                </span><span>c</span><br>
                <span class="p9">
                    3 à 5mm = A<br>
                    5 à 10mm = B<br>
                    > 10mm = C
                </span>
            </div>
        </div>
    </div>
    <!-- 4A -->
    <div class="box">
        <span class="topic">4A) Outras anormalidades:</span>
        <span>
            @if ($outras_anormalidades == 'S') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
        </span><span>SIM</span>
        <span>
            @if ($outras_anormalidades == 'N') [&nbsp;X&nbsp;] @else [<span style="color:white"> X </span>] @endif
        </span><span>NAO</span>
    </div>
    @php
        $result = "";
        $legenda = "";
    @endphp
    @foreach ($simbolos as $key => $value)
        @php
            $result .= '[x] ' . $key . ' ';
            $legenda .= $key . '('. $value . ')';
        @endphp
    @endforeach
    <!-- 4B -->
    <div class="box">
        <span class="topic">4B) Simbolos:</span><br>
        <span>{{$result}}</span><br>
        <span>Legenda: </span>
        <span>{{$legenda}}</span>
    </div>
    <!-- 4C -->
    <div class="box" style="height: 100px;">
        <div style="float:left;max-width:40%">
            <span class="topic">4C) Comentários</span><br>
            {{$comentarios_laudo}}
        </div>
        <div style="float:left">
            Assinatura:<br />
            <img src="{{$sign}}" />
        </div>
        <div style="float:left">
            <img src="{{$signer}}" /><br>
            <span style="color:red;font-family:Monospace;font-weight: bold;">
                &nbsp;{{$protocolo}}
            </span>
        </div>
        <div style="float:left">
            <img src="{{$qrCode}}" class="qrcode" /><br>
        </div>
    </div>
</div>
