<?php

namespace TheIconic\NtlmSoap\Client;

use GuzzleHttp\ClientInterface;
use SoapClient;
use SoapFault;

class NtlmSoap extends SoapClient
{
    protected $client;
    protected $username;
    protected $password;

    public function __construct(
        ClientInterface $client,
        string $username,
        string $password,
        string $wsdl = null,
        array $options = []
    ) {
        $this->client = $client;
        $this->username = $username;
        $this->password = $password;

        parent::__construct($wsdl, $options);
    }

    /**
     * @throws SoapFault
     */
    public function __doRequest($request, $location, $action, $version, $oneWay = 0): string
    {
        $headers = [
            'Connection' => 'Keep-Alive',
            'Content-type' => 'text/xml; charset=utf-8',
            'SOAPAction' => $action,
        ];

        $response = $this->client->request('POST', $location, [
            'headers' => $headers,
            'auth' => [$this->username, $this->password, 'ntlm'],
            'body' => $request,
            'http_errors' => false,
        ]);

        return (string)$response->getBody();
    }
}
