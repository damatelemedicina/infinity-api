<?php

namespace App\Services\SOCWebService;

class SOCWebService
{
    protected const WSDL_URI = '';
    protected $username;
    protected $password;

    public function __construct($username = null, $password = null)
    {
        $this->username = $username;
        $this->password = $password;
    }

    protected function client(array $extraParams = [])
    {
        return new \SoapClient(static::WSDL_URI, array_merge(
            [
                'trace' => 1,
                'exceptions' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE
            ],
            $extraParams,
        ));
    }

    protected function defaultRequest($serviceName, $argName, $params)
    {
        $arg0 = [$argName => $params];
        $client = $this->client();
        $response = $client->__soapCall($serviceName, [$arg0]);
        unset($client);
        return $response;
    }
}
