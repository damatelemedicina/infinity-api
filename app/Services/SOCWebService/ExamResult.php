<?php

namespace App\Services\SOCWebService;

use App\Services\SOCWebService\SOCWebService;
use App\Services\SOCWebService\WsseAuthHeader;
use GuzzleHttp\Client;

class ExamResult extends SOCWebService
{
    protected const WSDL_URI = 'https://ws1.soc.com.br/WSSoc/services/ResultadoExamesWs?wsdl';

    public function examResult(array $params)
    {
        [
            "examesIdentificacaoPorIdWsVo" => $examesIdentificacaoPorIdWsVo,
            "identificacaoWsVo" => $identificacaoWsVo,
            "resultadoExamesDadosWsVo" => $resultadoExamesDadosWsVo,
            "resultadoExamesIdentificacaoFuncionarioWsVo" => $resultadoExamesIdentificacaoFuncionarioWsVo,
        ] = array_merge([
            "examesIdentificacaoPorIdWsVo" => [],
            "identificacaoWsVo" => [],
            "resultadoExamesDadosWsVo" => null,
            "resultadoExamesIdentificacaoFuncionarioWsVo" => null,
        ], $params);

        [
            "codigoIdFicha" => $codigoIdFicha,
            "codigoIdResultadoExame" => $codigoIdResultadoExame,
        ] = array_merge([
            "codigoIdFicha" => null,
            "codigoIdResultadoExame" => null,
        ], is_array($examesIdentificacaoPorIdWsVo) ? $examesIdentificacaoPorIdWsVo : []);

        [
            "codigoUsuario" => $codigoUsuario,
            "chaveAcesso" => $chaveAcesso,
            "codigoEmpresaPrincipal" => $codigoEmpresaPrincipal,
            "codigoResponsavel" => $codigoResponsavel,
        ] = array_merge([
            "codigoUsuario" => null,
            "chaveAcesso" => null,
            "codigoEmpresaPrincipal" => null,
            "codigoResponsavel" => null,
        ], is_array($identificacaoWsVo) ? $identificacaoWsVo : []);

        [
            "codigoExame" => $codigoExame,
            "sobrepoeResultadoExistente" => $sobrepoeResultadoExistente,
            "resultado" => $resultado,
            "dataResultadoExame" => $dataResultadoExame,
            "resultadoAlterado" => $resultadoAlterado,
        ] = array_merge([
            "codigoExame" => null,
            "sobrepoeResultadoExistente" => null,
            "resultado" => null,
            "dataResultadoExame" => null,
            "resultadoAlterado" => null,
        ], is_array($resultadoExamesDadosWsVo) ? $resultadoExamesDadosWsVo : []);

        $header = new WsseAuthHeader($this->username, $this->password);

        $resultado = Self::htmlToText($resultado);

        $xmlEnvelope = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://services.soc.age.com/">
                <soapenv:Header>
                    {$header->createWsseHeader()}
                </soapenv:Header>
                <soapenv:Body>
                    <ser:resultadoExamesPorCodigoSequencial>
                        <resultadoExame>
                            <examesIdentificacaoPorIdWsVo>
                                <codigoIdFicha>$codigoIdFicha</codigoIdFicha>
                                <codigoIdResultadoExame>$codigoIdResultadoExame</codigoIdResultadoExame>
                            </examesIdentificacaoPorIdWsVo>
                            <identificacaoWsVo>
                                <chaveAcesso>$chaveAcesso</chaveAcesso>
                                <codigoEmpresaPrincipal>$codigoEmpresaPrincipal</codigoEmpresaPrincipal>
                                <codigoResponsavel>$codigoResponsavel</codigoResponsavel>
                                <codigoUsuario>$codigoUsuario</codigoUsuario>
                            </identificacaoWsVo>
                            <resultadoExamesDadosWsVo>
                                <codigoExame>$codigoExame</codigoExame>
                                <dataResultadoExame>$dataResultadoExame</dataResultadoExame>
                                <resultado>$resultado</resultado>
                                <sobrepoeResultadoExistente>$sobrepoeResultadoExistente</sobrepoeResultadoExistente>
                                <resultadoAlterado>$resultadoAlterado</resultadoAlterado>
                            </resultadoExamesDadosWsVo>
                        </resultadoExame>
                    </ser:resultadoExamesPorCodigoSequencial>
                </soapenv:Body>
            </soapenv:Envelope>
            XML;

        return $this->defaultRequest($xmlEnvelope);
    }

    protected function defaultRequest($xmlEnvelope, $_ = null, $__ = null)
    {
        // Configurar o cliente Guzzle
        $client = new Client(['base_uri' => 'https://ws1.soc.com.br/']);

        try {
            // Enviar a requisição
            $response = $client->post('WSSoc/services/ResultadoExamesWs', [
                'body' => $xmlEnvelope,
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
                    $error = "ResultadoExame: {$fault->faultcode} - {$fault->faultstring}";
                }
            } catch (\Throwable $th) {
                throw new \Exception("Erro ao processar a resposta da requisição: " . $th->getMessage());
            }
            throw new \Exception($error);
        }
    }

    static function htmlToText($html)
    {
        if(empty($html)) return $html;

        $html = strip_tags($html, "<p><br><tr><blockquote>"); //remove all unsupported tags
        $html = str_replace("\n", ' ', $html); //replace carriage returns by spaces
        $html = str_replace("&nbsp;", ' ', $html); //replace carriage returns by spaces
        $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE); //explodes the string

        foreach ($a as $k => $v) {
            if (preg_match('/^br$/i', $v)) $a[$k] = PHP_EOL; //replaces <br> by CRLF
            elseif (preg_match('/^\/p$/i', $v)) $a[$k] = PHP_EOL; //replaces </p> by CRLF
            elseif (preg_match('/^p$/i', $v)) $a[$k] = PHP_EOL; //replaces <p> by CRLF
            elseif (preg_match('/^\/tr$/i', $v)) $a[$k] = PHP_EOL; //replaces </tr> by CRLF
            elseif (preg_match('/^tr$/i', $v)) $a[$k] = PHP_EOL; //replaces <tr> by CRLF
            elseif (preg_match('/^\/blockquote$/i', $v)) $a[$k] = PHP_EOL; //replaces </blockquote> by CRLF
            elseif (preg_match('/^blockquote$/i', $v)) $a[$k] = PHP_EOL; //replaces <blockquote> by CRLF
        }

        return implode('', $a);
    }
}
