<?php

namespace Test\Unit\TheIconic\NtlmSoap\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SoapFault;
use stdClass;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use TheIconic\NtlmSoap\Cache\CacheInterface;
use TheIconic\NtlmSoap\Client\NtlmSoap;
use TheIconic\NtlmSoap\Exception\InvalidCacheAdapterException;

class NtlmSoapTest extends TestCase
{
    private const INTERNAL_ERROR = 500;
    private const OK = 200;

    /** @var MockInterface */
    private $clientMock;

    private $soapClient;
    private $username = 'testUser';
    private $password = 'testPassword';
    private $testUri = 'http://test-uri';
    private $testLocation = 'http://test-location';

    protected function setUp()
    {
        parent::setUp();

        $this->clientMock = Mockery::mock(ClientInterface::class, [
            'request' => new Response(),
        ]);

        $this->soapClient = new NtlmSoap(
            $this->clientMock,
            null,
            [
                'username' => $this->username,
                'password' => $this->password,
                'soap_options' => ['location' => $this->testLocation, 'uri' => $this->testUri],
            ]
        );
    }

    public function testExceptionIsThrownInCaseRequiredParametersAreMissing(): void
    {
        $client = $this->getMockHttpClient();

        $this->expectException(MissingOptionsException::class);

        $soapClient = new NtlmSoap($client);
    }

    public function testShouldSendNtmlAuthCredentials(): void
    {
        $this->soapClient->testRequest('foo');

        $expectedAuth = [
            $this->username,
            $this->password,
            'ntlm',
        ];

        $this->assertRequestOptionsIsEquals('auth', $expectedAuth);
    }

    public function testShouldSendHeadersOnRequest(): void
    {
        $this->soapClient->testRequest('foo');

        $expectedHeaders = [
            'Connection' => 'Keep-Alive',
            'Content-type' => 'text/xml; charset=utf-8',
            'SOAPAction' => $this->testUri.'#testRequest',
        ];

        $this->assertRequestOptionsIsEquals('headers', $expectedHeaders);
    }

    public function testShouldSendRequestBodyParams(): void
    {
        $this->soapClient->testRequest('foo');

        $expectedBody = '<param0 xsi:type="xsd:string">foo</param0>';

        $this->assertRequestOptionsContains('body', $expectedBody);
    }

    public function testShouldSetHttpErrorsAsFalse(): void
    {
        $this->soapClient->testRequest('foo');

        $this->assertRequestOptionsIsEquals('http_errors', false);
    }

    public function testShouldReturnAResponseMessage(): void
    {
        $this->clientMock->allows([
            'request' => new Response(self::OK, [], $this->getResponseEnvelopeMsg('<Item><Name>Foo</Name></Item>'))
        ]);

        $expectedResponse = new stdClass();
        $expectedResponse->Name = 'Foo';

        $this->assertEquals($expectedResponse, $this->soapClient->testRequest('foo'));
    }

    public function testShouldReturnASoapFaultForInternalErrorResponse(): void
    {
        $errorMsg = 'Service was not found!';

        $errorResponse = new Response(
            self::INTERNAL_ERROR,
            [],
            $this->getFaultEnvelopeMsg($errorMsg)
        );

        $client = $this->getMockHttpClient([$errorResponse]);

        $soapClient = new NtlmSoap(
            $client,
            null,
            [
                'username' => $this->username,
                'password' => $this->password,
                'soap_options' => ['location' => $this->testLocation, 'uri' => $this->testUri],
            ]
        );

        $this->expectException(SoapFault::class);
        $this->expectExceptionMessage($errorMsg);
        $soapClient->testRequest('foo');
    }

    public function testRemoteWsdlIsFetchedAndCached(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $client = $this->getMockHttpClient([
            new Response(self::OK, [], ''),
        ]);

        $cache->expects($this->any())->method('get')->willReturn(null);
        $cache->expects($this->once())->method('put')->willReturn($this->getTemporarySampleWsdlFile());

        new NtlmSoap($client, $cache, ['username' => '', 'password' => '', 'wsdl' => $this->testLocation]);
    }

    public function testExceptionIsThrownIfCacheAdapterIsNotPresentWhenRequestingForRemoteWsdl(): void
    {
        $client = $this->getMockHttpClient();

        $this->expectException(InvalidCacheAdapterException::class);

        new NtlmSoap($client, null, ['username' => '', 'password' => '', 'wsdl' => $this->testLocation]);
    }

    public function testRemoteWsdlIsNotRequestedIfCachedVersionExists(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $cache = $this->createMock(CacheInterface::class);

        $cache->expects($this->any())->method('get')->willReturn($this->getTemporarySampleWsdlFile());
        $client->expects($this->never())->method('request');
        $cache->expects($this->never())->method('put');

        new NtlmSoap($client, $cache, ['username' => '', 'password' => '', 'wsdl' => $this->testLocation]);
    }

    private function assertRequestOptionsIsEquals(string $optionsKey, $expected): void
    {
        $this->assertRequestOptions(function (array $options) use ($optionsKey, $expected) {

            $this->assertEquals($expected, $options[$optionsKey]);

            return true;
        });
    }

    private function assertRequestOptionsContains(string $optionsKey, $expected): void
    {
        $this->assertRequestOptions(function (array $options) use ($optionsKey, $expected) {

            $this->assertContains($expected, $options[$optionsKey]);

            return true;
        });
    }

    private function assertRequestOptions(callable $assertionFn): void
    {
        $this->clientMock->shouldHaveReceived('request', [
            Mockery::any(),
            Mockery::any(),
            Mockery::on($assertionFn),
        ]);
    }

    private function getMockHttpClient(array $responses = []): ClientInterface
    {
        $mockHandler = new MockHandler($responses);
        $handler = HandlerStack::create($mockHandler);

        return new Client(['handler' => $handler]);
    }

    private function getFaultEnvelopeMsg(string $faultMsg): string
    {
        $envelopePattern = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><s:Fault>'.
            '<faultstring xml:lang="en-US">%s</faultstring>'.
            '</s:Fault></s:Body></s:Envelope>';

        return sprintf($envelopePattern, $faultMsg);
    }

    private function getResponseEnvelopeMsg(string $responseResult): string
    {
        $responsePattern = '<Soap:Envelope xmlns:Soap="http://schemas.xmlsoap.org/soap/envelope/"><Soap:Body>'.
            '<testRequest_Result>%s</testRequest_Result>'.
            '</Soap:Body></Soap:Envelope>';

        return sprintf($responsePattern, $responseResult);
    }

    private function getTemporarySampleWsdlFile(): string
    {
        return __DIR__.'/../../data/sample.wsdl';
    }
}
