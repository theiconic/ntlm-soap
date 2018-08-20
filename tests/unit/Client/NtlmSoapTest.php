<?php

namespace Test\Unit\TheIconic\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use TheIconic\NtlmSoap\Client\NtlmSoap;

class NtlmSoapTest extends TestCase
{
    /** @var MockInterface */
    private $clientMock;

    private $soapClient;

    private $username = 'testUser';

    private $password = 'testPassword';

    private $testUri = 'http://test-uri';

    protected function setUp()
    {
        parent::setUp();

        $this->clientMock = Mockery::mock(ClientInterface::class, [
            'request' => new Response(),
        ]);

        $this->soapClient = new NtlmSoap(
            $this->clientMock,
            $this->username,
            $this->password,
            null,
            ['location' => 'http://test-location', 'uri' => $this->testUri]
        );
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
            'SOAPAction' => $this->testUri . '#testRequest',
        ];

        $this->assertRequestOptionsIsEquals('headers', $expectedHeaders);
    }

    private function assertRequestOptionsIsEquals(string $optionsKey, $expected): void
    {
        $this->clientMock->shouldHaveReceived('request', [
            Mockery::any(),
            Mockery::any(),
            Mockery::on(function (array $options) use ($optionsKey, $expected) {

                $this->assertEquals($expected, $options[$optionsKey]);

                return true;
            }),
        ]);
    }
}
