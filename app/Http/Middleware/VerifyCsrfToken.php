<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // Proxy pro Orthanc (routes/web.php): o Stone Web Viewer faz POST
        // (ex: /tools/find) sem nenhum token CSRF nosso — ele nem sabe que
        // está passando por um proxy Laravel.
        'orthanc-viewer/*',
    ];
}
