## NTLM-SOAP

 - The purpose of this thin PHP library is to provide an easy and handy way to communicate with SOAP services that are using [NTLM](https://docs.microsoft.com/en-us/windows/desktop/secauthn/microsoft-ntlm) authentication protocol.
 
### Example

Using a local WSDL file:

```php
<?php

use TheIconic\NtlmSoap\Client\NtlmSoap;
use GuzzleHttp\Client;

$client = new Client();

$soapClient = new NtlmSoap(
    $client,
    null,
    [
        'username' => 'your-username',
        'password' => 'your-password',
        'wsdl' => 'path-of-your-local-wsdl-file',
        'wsdl_options' => [
            'location' => 'http://my-location.com',
            'cache_wsdl' => WSDL_CACHE_NONE,
        ],
    ]
);

$response = $soapClient->soapMethod([
    'methodParameter' => null,
]);
```

In order to use a remote WSDL definition, you need a fileystem cache adapter. Example:

```php
<?php

use Symfony\Component\Filesystem\Filesystem;
use TheIconic\NtlmSoap\Cache\FilesystemCache;
use TheIconic\NtlmSoap\Client\NtlmSoap;
use GuzzleHttp\Client;

$cacheRootDir = __DIR__.'/cache';
$defaultTtl = 3600; // cache the WSDL files for 1 hour

$client = new Client();
$cache = new FilesystemCache(new Filesystem(), $cacheRootDir, $defaultTtl);

$soapClient = new NtlmSoap(
    $client,
    $cache,
    [
        'username' => 'your-username',
        'password' => 'your-password',
        'wsdl' => 'http://myurl.com/remote/wsdl',
    ]
);

$response = $soapClient->soapMethod();
```
