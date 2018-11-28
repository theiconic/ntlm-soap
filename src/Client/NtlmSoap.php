<?php

namespace TheIconic\NtlmSoap\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use SoapClient;
use SoapFault;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NtlmSoap extends SoapClient
{
    protected $client;
    protected $options;

    public function __construct(ClientInterface $client, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->client = $client;
        $this->options = $resolver->resolve($options);

        parent::__construct($this->options['wsdl'], $this->options['soap_options']);
    }

    /**
     * @throws SoapFault
     * @throws GuzzleException
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
            'auth' => [
                $this->options['username'],
                $this->options['password'],
                'ntlm'
            ],
            'body' => $request,
            'http_errors' => false,
        ]);

        return (string) $response->getBody();
    }

    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'username',
            'password',
        ]);

        $resolver->setDefaults([
            'wsdl' => null,
            'soap_options' => [],
        ]);
    }
}
