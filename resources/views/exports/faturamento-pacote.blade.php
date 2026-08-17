<!DOCTYPE html>
<html>
<head>
<title>Faturamento Pacotes</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
    <table class="table">
        <thead>
        <tr>
            <th>CLIENTE</th>
            <th>EXAME</th>
            <th>PACOTE ATE</th>
            <th>VALOR PACOTE</th>
            <th>EXAMES</th>
            <th>NAO COBRADOS</th>
            <th>Nº EXCEDENTES</th>
            <th>VALOR EXCEDENTE UNITÁRIO</th>
            <th>R$ TOTAL EXCEDENTES</th>
            <th>VALOR TOTAL</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($exames as $exame)
            <tr>
                <td width="35">{{ $exame->cliente }}</td>
                <td width="25">{{ $exame->exame }}</td>
                <td width="20">{{ $exame->pacote_ate }}</td>
                <td width="20">{{ $exame->pacote_preco }}</td>
                <td width="20">{{ $exame->exames }}</td>
                <td width="20">{{ $exame->abonado }}</td>
                <td width="20">{{ $exame->exames_excedente }}</td>
                <td width="35">{{ $exame->preco_excedente }}</td>
                <td width="35">{{ $exame->total_excedente }}</td>
                <td width="20">{{ $exame->total_exames }}</td>
            </tr>
        @endforeach
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td width="30">SOMA</td>
                <td width="20">{{ $total }}</td>
            </tr>
        </tbody>
    </table>
</body>
