<?php

return [
    // Hostname interno da rede Docker (ex: http://infinity-ort:8042), não
    // acessível pelo navegador do usuário — só usado servidor-a-servidor,
    // pelo OrthancClient e pelo proxy do viewer em routes/web.php
    // ("/orthanc-viewer/{path}"). O navegador nunca fala direto com essa URL:
    // ele acessa "/orthanc-viewer/..." na própria API (publicamente
    // acessível), que repassa pro Orthanc com a credencial injetada aqui.
    'url' => env('ORTHANC_URL', 'http://localhost:8042'),

    'user' => env('ORTHANC_USER'),
    'password' => env('ORTHANC_PASSWORD'),
    'sync_poll_seconds' => (int) env('ORTHANC_SYNC_POLL_SECONDS', 20),
];
