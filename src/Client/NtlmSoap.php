<?php

namespace TheIconic\NtlmSoap\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use SoapClient;
use SoapFault;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TheIconic\NtlmSoap\Cache\CacheInterface;
use TheIconic\NtlmSoap\Exception\InvalidCacheAdapterException;

class NtlmSoap extends SoapClient
{
    protected $client;
    protected $cache;
    protected $options;

    public function __construct(ClientInterface $client, CacheInterface $cache = null, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->client = $client;
        $this->cache = $cache;
        $this->options = $resolver->resolve($options);

        $wsdl = $this->resolveWsdl($this->options['wsdl']);

        parent::__construct($wsdl, $this->options['soap_options']);
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

    private function resolveWsdl(?string $wsdl): ?string
    {
        $isRemoteWsdl = preg_match('/^https?:\/\//', $wsdl);

        if (!$isRemoteWsdl) {
            return $wsdl;
        }

        return $this->getWsdlCachedFile($wsdl);
    }

    private function getWsdlCachedFile(string $url): string
    {
        if (!$this->cache instanceof CacheInterface) {
            throw new InvalidCacheAdapterException();
        }

        $fileName = sprintf('wsdl_%s.xml', md5($url));
        $wsdl = $this->cache->get($fileName);

        if ($wsdl === null) {
            $contents = $this->client->request('GET', $url, [
                'auth' => [
                    $this->options['username'],
                    $this->options['password'],
                    'ntlm'
                ],
            ])->getBody()->getContents();

            $wsdl = $this->cache->put($fileName, $contents);
        }

        return $wsdl;
    }
}
