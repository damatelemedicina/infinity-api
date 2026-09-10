<?php

namespace App\Services\SOCWebService;

class WsseAuthHeader
{
    private $username;
    private $password;
    private $created;
    private $expires;

    public function __construct($username, $password)
    {
        // Defina os parâmetros fornecidos
        $this->username = $username;
        $this->password = $password;
        // agora UTC - menos 5 segundos
        $created = date_create('now', new \DateTimeZone('UTC'));
        $this->created = $created->format('Y-m-d\TH:i:s.v\Z');  // Timestamp
        $this->expires = $created->add(date_interval_create_from_date_string('1 minutes'))->format('Y-m-d\TH:i:s.v\Z');  // Expires será 1 minuto depois
    }

    // Gerar Nonce (valor único aleatório em Base64 usando Math.random)
    private function generateNonceBinary()
    {
        return bin2hex(random_bytes(16));  // Gerar Nonce em Base64
    }

    // Gerar PasswordDigest
    private function generatePasswordDigest($nonce, $timestamp)
    {
        $stringToHash = $nonce . $timestamp . $this->password;
        return base64_encode(sha1($stringToHash, true));  // Gerar hash SHA-1 e codificar em Base64
    }

    public function createWsseHeader()
    {
        $nonceBinary = $this->generateNonceBinary();
        $nonce = base64_encode($nonceBinary);
        $createdNow = date_create('now', new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z');
        $passwordDigest = $this->generatePasswordDigest($nonceBinary, $createdNow);

        $timestampId = 'TS-' . md5($this->created) . md5($this->expires);

        $usernameTokenId = 'UsernameToken-' . md5($this->username);

        return <<<XML
        <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" 
                       xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
            <wsu:Timestamp wsu:Id="$timestampId">
                <wsu:Created>$this->created</wsu:Created>
                <wsu:Expires>$this->expires</wsu:Expires>
            </wsu:Timestamp>
            <wsse:UsernameToken wsu:Id="$usernameTokenId">
                <wsse:Username>$this->username</wsse:Username>
                <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">$passwordDigest</wsse:Password>
                <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">$nonce</wsse:Nonce>
                <wsu:Created>$createdNow</wsu:Created>
            </wsse:UsernameToken>
        </wsse:Security>
        XML;
    }
}
