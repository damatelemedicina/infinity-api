<?php

namespace App\Services\Orthanc;

use GuzzleHttp\Client;

/**
 * Wrapper fino em volta da API REST do Orthanc (https://orthanc.uclouvain.be/book/users/rest.html).
 * Expõe só o que o comando de sincronismo precisa: consultar mudanças de
 * forma incremental via /changes, baixar os bytes crus de uma instância e
 * listar as instâncias de um estudo.
 */
class OrthancClient
{
    private Client $http;

    // Sem injeção de Client no construtor de propósito: um type-hint simples
    // `Client $http = null` seria resolvido automaticamente pelo container do
    // Laravel como um GuzzleHttp\Client cru, sem configuração (sem
    // base_uri/auth), sobrescrevendo silenciosamente o padrão abaixo. Os
    // testes substituem essa classe inteira via
    // $this->app->instance(OrthancClient::class, ...) em vez de mockar o Client.
    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => rtrim(config('orthanc.url'), '/') . '/',
            'auth' => [config('orthanc.user'), config('orthanc.password')],
            'timeout' => 30,
        ]);
    }

    /**
     * Consulta o log de mudanças do Orthanc a partir de logo após $since.
     * Devolve a resposta decodificada crua: ['Changes' => [...], 'Done' => bool, 'Last' => int].
     */
    public function getChangesSince(int $since, int $limit = 50): array
    {
        $response = $this->http->get('changes', [
            'query' => ['since' => $since, 'limit' => $limit],
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * Baixa os bytes DICOM crus de uma única instância.
     */
    public function downloadInstanceFile(string $instanceId): string
    {
        $response = $this->http->get("instances/{$instanceId}/file");

        return (string) $response->getBody();
    }

    /**
     * Lista o ID de cada instância pertencente a um estudo — usado quando o
     * Orthanc avisa que o estudo está estável (nenhuma instância nova
     * esperada), pra poder agrupar tudo por tipo de uma vez, igual um zip de lote.
     */
    public function getStudyInstanceIds(string $studyId): array
    {
        $response = $this->http->get("studies/{$studyId}/instances");
        $data = json_decode((string) $response->getBody(), true) ?? [];

        return array_column($data, 'ID');
    }
}
