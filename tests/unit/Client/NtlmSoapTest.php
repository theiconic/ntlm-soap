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
            ['location' => 'http://test-location', 'uri' => 'http://test-uri']
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
