<?php

namespace App\Services\Orthanc;

use GuzzleHttp\Client;

/**
 * Thin wrapper around Orthanc's REST API (https://orthanc.uclouvain.be/book/users/rest.html).
 * Only exposes what the sync command needs: incremental polling via /changes
 * and downloading a single instance's raw DICOM bytes.
 */
class OrthancClient
{
    private Client $http;

    // No constructor-injected Client here on purpose: a plain `Client $http = null`
    // type-hint gets auto-resolved by Laravel's container to a bare, unconfigured
    // GuzzleHttp\Client (no base_uri/auth), silently shadowing the default below.
    // Tests instead replace this whole class via $this->app->instance(OrthancClient::class, ...).
    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => rtrim(config('orthanc.url'), '/') . '/',
            'auth' => [config('orthanc.user'), config('orthanc.password')],
            'timeout' => 30,
        ]);
    }

    /**
     * Polls Orthanc's change log starting right after $since.
     * Returns the raw decoded response: ['Changes' => [...], 'Done' => bool, 'Last' => int].
     */
    public function getChangesSince(int $since, int $limit = 50): array
    {
        $response = $this->http->get('changes', [
            'query' => ['since' => $since, 'limit' => $limit],
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * Downloads the raw DICOM bytes for a single instance.
     */
    public function downloadInstanceFile(string $instanceId): string
    {
        $response = $this->http->get("instances/{$instanceId}/file");

        return (string) $response->getBody();
    }
}
