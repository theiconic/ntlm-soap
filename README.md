## NTLM-SOAP

 - The purpose of this thin PHP library is to provide an easy and handy way to communicate with SOAP services that are using [NTLM](https://docs.microsoft.com/en-us/windows/desktop/secauthn/microsoft-ntlm) authentication protocol.
 

### Example
```php
<?php

use TheIconic\NtlmSoap\Client\NtlmSoap;
use GuzzleHttp\Client;

$client = new Client();

$soapClient = new NtlmSoap(
    $client,
    [
        'username' => 'you-rusername',
        'password' => 'your-password',
        'wsdl' => 'path-of-your-local-wsdl-file',
        'wsdl_options' => [
            'cache_wsdl' => WSDL_CACHE_NONE,
        ],
    ]
);

$response = $soapClient->soapMethod([
    'methodParameter' => null,
]);
```
