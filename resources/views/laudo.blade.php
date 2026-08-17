<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            background:#FFF;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            margin: 0 auto;
        }

        .assinatura {
            transform: scale(0.6);
        }

        .cabecalho {
            max-height:100%;
            max-width:100%;
        }

        .rodape {
            max-height:100%;
            max-width:100%;
        }

        .cleanup p {
            margin: 0px;
        }

    </style>
</head>
<body>
    <div>
        <div>
            @if ($cabecalho)
                <img class="cabecalho" src="{{$cabecalho}}" />
            @endif
        </div>

        <table cellspacing="0" style="width:100%;">
            <tr><td style="padding:0px;" colspan="6"><hr></td></tr>
            <tr>
                <td style="padding:0px;" colspan="6">
                    <center><b>LAUDO DE&nbsp;{{$nome}}&nbsp;DIGITAL - Nº&nbsp;{{$numero}}</b></center>
                </td>
            </tr>
            <tr><td style="padding:0px;" colspan="6"><hr></td></tr>
            <tr>
                <td colspan="3">Nome:&nbsp;{{$paciente}}</td>
                <td colspan="3">Empresa:&nbsp;{{$contratante}}</td>
            </tr>
            <tr>
                <td colspan="3">
                    {{$idade}}
                </td>
                <td colspan="3">
                    Sexo:&nbsp;{{$sexo}}
                </td>
            </tr>
            <tr>
                <td colspan="6">
                    {{$documentos}}
                </td>
            </tr>

            <tr><td style="padding:0px;" colspan="6"><hr></td></tr>

            <tr>
                <td style="padding:0px;" colspan="6">
                    <center><b>EXAME</b></center>
                </td>
            </tr>

            <tr><td style="padding:0px;" colspan="6"><hr></td></tr>
            <tr>
                <td colspan="3">Dt. Exame:&nbsp;{{$data_exame}}</td>
                <td colspan="3">Dt. Laudo:&nbsp;{{$data_laudo}}</td>
            </tr>
            <tr>
                <td colspan="3">
                    Médico Solicitante:&nbsp;{{$medico_solicitante}}&nbsp;
                    CRM:&nbsp;{{$crm_solicitante}}
                </td>
                <td colspan="3">
                    Motivo Exame:&nbsp;{{$motivo}}
                </td>
            </tr>

            <!-- ACUIDADE VISUAL -->
            @if ($exame_id == 6)
                <tr>
                    <td colspan="3">Acuidade visual longe OD:&nbsp;{{$acuidade_longe_od}}</td>
                    <td colspan="3">Acuidade visual longe OE:&nbsp;{{$acuidade_longe_oe}}</td>
                </tr>
                <tr>
                    <td colspan="3">Acuidade visual perto OD:&nbsp;{{$acuidade_perto_od}}</td>
                    <td colspan="3">Acuidade visual perto OE:&nbsp;{{$acuidade_perto_oe}}</td>
                </tr>
                <tr>
                    <td colspan="3">Lente corretiva:&nbsp;{{$lente_corretiva}}</td>
                    <td colspan="3">Senso cromático:&nbsp;{{$senso_cromatico}}</td>
                </tr>
                <tr>
                    <td colspan="3">Visão noturna:&nbsp;{{$visao_noturna}}</td>
                    <td colspan="3">Visão ofuscada:&nbsp;{{$visao_ofuscada}}</td>
                </tr>
                <tr>
                    <td colspan="3">Profundidade:&nbsp;{{$profundidade}}</td>
                    <td colspan="3"></td>
                </tr>
            @endif
            <!-- ACUIDADE VISUAL -->

            <tr><td style="padding:0px;" colspan="6"><hr></td></tr>
            <tr><td style="padding:0px;" colspan="6"><center><b>ANÁLISE</b></center></td></tr>
            <tr><td style="padding:0px;" colspan="6"><hr></td></tr>

            <tr>
                <td colspan="6">
                    @if (!$impossibilitado)
                        @if ($exame_id == 1 && $dados_ecg)
                            {{$dados_ecg}}
                        @else
                            @if ($modelo_content != null)
                                <span style="font-size:10px;">{!!$modelo_content!!}</span>
                            @endif
                        @endif
                    @else
                        <h3>L A U D O&nbsp;&nbsp;I M P O S S I B I L I T A D O</h3>
                        <ul>
                            @foreach ($impossibilitado as $value)
                                <li style="color: red;">{{$value}}</li>
                            @endforeach
                        </ul>
                        <p>{{$observacoes_medico}}</p>
                    @endif
                </td>
            </tr>

            <tr>
                <td colspan="6">
                    <span style="color:red;">
                        {{$cliente_mensagem}}
                    </span>
                </td>
            </tr>

        </table>

        @if ($laudo_imagem && !$impossibilitado)
            <div style="width:90%">
                <div style="float:left; width:70%; height: 70%">
                    <img src="{{$laudo_imagem}}" style="max-height:100%; max-width:100%" />
                </div>
                <div>
                    @if ($signer)
                        <div align="center">
                            <img src="{{$signer}}" /><br>
                            <span style="color:red;font-family:Monospace;font-weight: bold;">
                                {{$protocolo}}
                            </span>
                        </div>
                    @endif
                    @if ($assinatura)
                        <div align="center">
                            <img src="{{$assinatura}}" class="assinatura" />
                        </div>
                    @endif
                    @if ($qrcode)
                        <div align="center">
                            <img src="{{$qrcode}}" />
                            <p style="font-size:9px">Aponte a câmera do seu celular <br>para baixar seu laudo.</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div align="center">
                @if ($signer)
                    <div style="float:left;width:20%">
                        <img src="{{$signer}}" /><br>
                        <span style="color:red;font-family:Monospace;font-weight: bold;">
                            {{$protocolo}}
                        </span>
                    </div>
                @endif
                @if ($assinatura)
                    <div style="float:left;width:50%">
                        <img src="{{$assinatura}}" class="assinatura" />
                    </div>
                @endif
                @if ($qrcode)
                    <div style="float:left;width:30%">
                        <img src="{{$qrcode}}" />
                        <p style="font-size:9px;margin:0px 30px 0px 30px;">Aponte a câmera do seu celular para baixar seu laudo.</p>
                    </div>
                @endif
            </div>
        @endif
        <div>
            @if ($rodape)
                <p style="visibility: hidden;">xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</p>
                <img class="rodape" src="{{$rodape}}" />
            @endif
        </div>
    </div>
</body>
</html>
