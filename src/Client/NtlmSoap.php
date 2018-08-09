<?php

namespace TheIconic\NtlmSoap\Client;

use SoapClient;
use SoapFault;

class NtlmSoap extends SoapClient
{
    protected $user;
    protected $password;

    public function __construct(string $user, string $password, string $wsdl, array $options = [])
    {
        $this->user = $user;
        $this->password = $password;

        parent::__construct($wsdl, $options);
    }

    /**
     * @throws SoapFault
     */
    public function __doRequest($request, $location, $action, $version, $oneWay = 0): string
    {
        $headers = [
            'Method: POST',
            'Connection: Keep-Alive',
            'User-Agent: PHP-SOAP-CURL',
            'Content-type: text/xml; charset=utf-8',
            sprintf('SOAPAction: "%s"', $action),
        ];

        $handle = curl_init($location);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $request);
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($handle, CURLOPT_USERPWD, $this->user.':'.$this->password);

        $response = (string)curl_exec($handle);

        if ($error = curl_error($handle)) {
            throw new SoapFault(
                'Error accessing WSDL server: '.$error, curl_errno($handle)
            );
        }
        curl_close($handle);

        return $response;
    }
}
