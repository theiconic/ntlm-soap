<?php

namespace TheIconic\NtlmSoap\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
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
        string $wsdl,
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
            'SOAPAction' => sprintf('"%s"', $action),
        ];

        try {
            $response = $this->client->request('POST', $location, [
                'headers' => $headers,
                'auth' => [$this->username, $this->password, 'ntlm'],
                'body' => $request,
                'http_errors' => false,
            ]);
        } catch (RequestException $exception) {
            throw new SoapFault($exception->getCode(), $exception->getMessage());
        }

            return (string) $response->getBody();
        }
    }
