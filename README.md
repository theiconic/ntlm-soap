## NTLM-SOAP

 - The purpose of this thin PHP library is to provide an easy and handy way to communicate with SOAP services that are using [NTLM](https://docs.microsoft.com/en-us/windows/desktop/secauthn/microsoft-ntlm) authentication protocol.
 

### Example
```php
use TheIconic\NtlmSoap\Client\NtlmSoap;

$soapClient = new NtlmSoap(
    'username',
    'password',
    'http://your-wsdl-url'
);

$response = $soapClient->soapMethod([
    'methodParameter' => null,
]);
```
