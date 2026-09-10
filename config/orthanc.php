<?php

return [
    'url' => env('ORTHANC_URL', 'http://localhost:8042'),
    'user' => env('ORTHANC_USER'),
    'password' => env('ORTHANC_PASSWORD'),
    'sync_poll_seconds' => (int) env('ORTHANC_SYNC_POLL_SECONDS', 20),
];
