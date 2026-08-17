<!DOCTYPE html>
<html>
<head>
<title>Financeiro de Medicos</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
    <table class="table">
        <thead>
        <tr>
            <th>CODIGO</th>
            <th>EXAME</th>
            <th>MEDICO</th>
            <th>PACIENTE</th>
            <th>DATA DO EXAME</th>
            <th>DATA DO LAUDO</th>
            <th>TELEMEDICINA</th>
            <th>PREÇO</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($laudos as $laudo)
            <tr>
                <td width="25">{{ $laudo->exame_id }}</td>
                <td width="25">{{ $laudo->tipo_exame }}</td>
                <td width="35">{{ $laudo->medico_nome }}</td>
                <td width="35">{{ $laudo->paciente }}</td>
                <td width="20">{{ $laudo->exame_data }}</td>
                <td width="20">{{ $laudo->laudo_data }}</td>
                <td width="20">{{ $laudo->empresa }}</td>
                <td>{{ $laudo->preco_laudo }}</td>
            </tr>
        @endforeach
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>VALOR TOTAL</td>
                <td>{{ $total_geral }}</td>
            </tr>
        </tbody>
    </table>

    <table class="table">
        <thead>
        <tr>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($sumario as $item)
            <tr>
                <td>{{ $item->exame }}</td>
                <td>{{ $item->total }}</td>
            </tr>
        @endforeach
            <tr>
                <td>VALOR TOTAL</td>
                <td>{{ $total_geral }}</td>
            </tr>
        </tbody>
    </table>

</body>
