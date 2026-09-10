<?php

namespace App\Services\SOCWebService;

use GuzzleHttp\Client;

class FileUploadGED extends SOCWebService
{
    protected const WSDL_URI = 'https://ws1.soc.com.br/WSSoc/services/UploadArquivosWs?wsdl';

    public function fileUpload($params)
    {
        return $this->defaultRequest('uploadArquivo', 'arg0', $params);
    }

    protected function defaultRequest($serviceName, $argName, $params)
    {
        [
            "arquivo" => $arquivo,
            "classificacao" => $classificacao,
            "codigoEmpresa" => $codigoEmpresa,
            "codigoFuncionario" => $codigoFuncionario,
            "codigoGed" => $codigoGed,
            "codigoSequencialFicha" => $codigoSequencialFicha,
            "codigoTipoGed" => $codigoTipoGed,
            "extensaoArquivo" => $extensaoArquivo,
            "identificacaoVo" => $identificacaoVo,
            "nomeArquivo" => $nomeArquivo,
            "nomeGed" => $nomeGed,
            "nomeTipoGed" => $nomeTipoGed,
            "sobreescreveArquivo" => $sobreescreveArquivo,
            "codigoUnidadeGed" => $codigoUnidadeGed,
            "dataValidadeGed" => $dataValidadeGed,
            "revisaoGed" => $revisaoGed,
            "string01" => $string01,
            "observacao" => $observacao
        ] = array_merge([
            "arquivo" => null,
            "classificacao" => 'RESULTADO_EXAME',
            "codigoEmpresa" => null,
            "codigoFuncionario" => null,
            "codigoGed" => null,
            "codigoSequencialFicha" => null,
            "codigoTipoGed" => null,
            "extensaoArquivo" => null,
            "identificacaoVo" => null,
            "nomeArquivo" => null,
            "nomeGed" => null,
            "nomeTipoGed" => 'Prontuário Médico',
            "sobreescreveArquivo" => 0,
            "codigoUnidadeGed" => null,
            "dataValidadeGed" => null,
            "revisaoGed" => null,
            "string01" => null,
            "observacao" => null
        ], $params);

        [
            "codigoUsuario" => $codigoUsuario,
            "chaveAcesso" => $chaveAcesso,
            "codigoEmpresaPrincipal" => $codigoEmpresaPrincipal,
            "codigoResponsavel" => $codigoResponsavel,
            "homologacao" => $homologacao,
        ] = array_merge([
            "codigoUsuario" => null,
            "chaveAcesso" => null,
            "codigoEmpresaPrincipal" => null,
            "codigoResponsavel" => null,
            "homologacao" => null
        ], $identificacaoVo);

        $header = new WsseAuthHeader($this->username, $this->password);

        // Configurar o cliente Guzzle
        $client = new Client(['base_uri' => 'https://ws1.soc.com.br/']);

        try {
            $xmlEnvelope = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://services.soc.age.com/">
                <soapenv:Header>
                    {$header->createWsseHeader()}
                </soapenv:Header>
                <soapenv:Body>
                    <ser:$serviceName>
                        <arg0>
                            <arquivo>$arquivo</arquivo>
                            <classificacao>$classificacao</classificacao>
                            <codigoEmpresa>$codigoEmpresa</codigoEmpresa>
                            <codigoFuncionario>$codigoFuncionario</codigoFuncionario>
                            <codigoGed>$codigoGed</codigoGed>
                            <codigoSequencialFicha>$codigoSequencialFicha</codigoSequencialFicha>
                            <codigoTipoGed>$codigoTipoGed</codigoTipoGed>
                            <extensaoArquivo>$extensaoArquivo</extensaoArquivo>
                            <identificacaoVo>
                                <codigoUsuario>$codigoUsuario</codigoUsuario>
                                <chaveAcesso>$chaveAcesso</chaveAcesso>
                                <codigoEmpresaPrincipal>$codigoEmpresaPrincipal</codigoEmpresaPrincipal>
                                <codigoResponsavel>$codigoResponsavel</codigoResponsavel>
                                <homologacao>$homologacao</homologacao>
                            </identificacaoVo>
                            <nomeArquivo>$nomeArquivo</nomeArquivo>
                            <nomeGed>$nomeGed</nomeGed>
                            <nomeTipoGed>$nomeTipoGed</nomeTipoGed>
                            <sobreescreveArquivo>$sobreescreveArquivo</sobreescreveArquivo>
                            <codigoUnidadeGed>$codigoUnidadeGed</codigoUnidadeGed>
                            <dataValidadeGed>$dataValidadeGed</dataValidadeGed>
                            <revisaoGed>$revisaoGed</revisaoGed>
                            <string01>$string01</string01>
                            <observacao>$observacao</observacao>
                        </arg0>
                    </ser:$serviceName>
                </soapenv:Body>
            </soapenv:Envelope>
            XML;

            // Gere um boundary único para separar as partes
            $boundary = 'boundary_' . uniqid();
            $mtomBody = <<<XML
            --$boundary
            Content-Type: multipart/related; boundary="$boundary"; type="application/xop+xml"; start="<rootpart@soap.xml>"; start-info="text/xml"
            Content-Transfer-Encoding: binary
            Content-ID: <rootpart@soap.xml>
            --$boundary
            Content-Type: text/xml; charset=UTF-8
            Content-Transfer-Encoding: binary
            Content-ID: <rootpart@soap.xml>
            
            $xmlEnvelope
            --$boundary--
            XML;

            // Enviar a requisição
            $response = $client->post('WSSoc/services/UploadArquivosWs', [
                'body' => $mtomBody,
                'headers' => [
                    'Content-Type' => "multipart/related; boundary=\"$boundary\"; type=\"application/xop+xml\"; start=\"<rootpart@soap.xml>\"; start-info=\"text/xml\"",
                    // 'SOAPAction' => '' // Informe o SOAPAction se necessário
                ]
            ]);
            // Obter a resposta
            $responseBody = $response->getBody()->getContents();

            // Processar a resposta
            return $responseBody;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Tratamento de erro
            $error = $e->getMessage();
            try {
                preg_match('/<soap:Envelope.*<\/soap:Envelope>/s', $e->getResponse()->getBody()->getContents(), $matches);
                $xml = new \SimpleXMLElement($matches[0]);

                $fault = $xml->xpath('//soap:Fault')[0] ?? null;
                if ($fault) {
                    // throw dos valores faultcode, faultstring e detail
                    $error = "SOCGED: {$fault->faultcode} - {$fault->faultstring}";
                }
            } catch (\Throwable $th) {
                throw new \Exception("Erro ao processar a resposta da requisição: " . $th->getMessage());
            }
            throw new \Exception($error);
        }
    }
}
