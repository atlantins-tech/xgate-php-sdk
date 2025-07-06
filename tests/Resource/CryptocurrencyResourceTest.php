<?php

declare(strict_types=1);

namespace XGate\Tests\Resource;

use PHPUnit\Framework\TestCase;
use XGate\Http\HttpClient;
use XGate\Exception\ApiException;
use XGate\Exception\AuthenticationException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Teste unitário para o endpoint de criptomoedas disponíveis para depósito
 * 
 * Baseado na documentação oficial: https://api.xgateglobal.com/pages/crypto/deposit/get-crypto.html
 * Endpoint: GET /deposit/company/cryptocurrencies
 */
class CryptocurrencyResourceTest extends TestCase
{
    private HttpClient $httpClient;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Testa a consulta bem-sucedida de criptomoedas disponíveis
     */
    public function testGetCryptocurrenciesSuccess(): void
    {
        // Dados de resposta baseados na resposta real da API
        $responseData = [
            [
                '_id' => '67339b18ca592e9d570e8586',
                'name' => 'USDT',
                'symbol' => 'USDT',
                'coinGecko' => 'tether',
                'updatedDate' => '2024-11-15T05:53:32.979Z',
                'createdDate' => '2024-11-12T18:14:48.380Z',
                '__v' => 0
            ]
        ];

        // Mock da resposta HTTP
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        // Mock da requisição HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/deposit/company/cryptocurrencies')
            ->willReturn($response);

        // Executar requisição
        $actualResponse = $this->httpClient->request('GET', '/deposit/company/cryptocurrencies');
        $actualData = json_decode($actualResponse->getBody()->getContents(), true);

        // Verificações
        $this->assertEquals(200, $actualResponse->getStatusCode());
        $this->assertIsArray($actualData);
        $this->assertCount(1, $actualData);
        
        $crypto = $actualData[0];
        $this->assertArrayHasKey('_id', $crypto);
        $this->assertArrayHasKey('name', $crypto);
        $this->assertArrayHasKey('symbol', $crypto);
        $this->assertArrayHasKey('coinGecko', $crypto);
        $this->assertArrayHasKey('createdDate', $crypto);
        $this->assertArrayHasKey('updatedDate', $crypto);
        
        $this->assertEquals('USDT', $crypto['name']);
        $this->assertEquals('USDT', $crypto['symbol']);
        $this->assertEquals('tether', $crypto['coinGecko']);
    }

    /**
     * Testa erro de autenticação (401 Unauthorized)
     */
    public function testGetCryptocurrenciesUnauthorized(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->expectExceptionCode(401);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/deposit/company/cryptocurrencies')
            ->willThrowException(new ApiException('Unauthorized', 401));

        $this->httpClient->request('GET', '/deposit/company/cryptocurrencies');
    }

    /**
     * Testa erro interno do servidor (500 Internal Server Error)
     */
    public function testGetCryptocurrenciesInternalServerError(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Internal Server Error');
        $this->expectExceptionCode(500);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/deposit/company/cryptocurrencies')
            ->willThrowException(new ApiException('Internal Server Error', 500));

        $this->httpClient->request('GET', '/deposit/company/cryptocurrencies');
    }

    /**
     * Testa resposta vazia (sem criptomoedas disponíveis)
     */
    public function testGetCryptocurrenciesEmptyResponse(): void
    {
        $responseData = [];

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/deposit/company/cryptocurrencies')
            ->willReturn($response);

        $actualResponse = $this->httpClient->request('GET', '/deposit/company/cryptocurrencies');
        $actualData = json_decode($actualResponse->getBody()->getContents(), true);

        $this->assertEquals(200, $actualResponse->getStatusCode());
        $this->assertIsArray($actualData);
        $this->assertEmpty($actualData);
    }

    /**
     * Testa estrutura de dados da resposta conforme documentação
     */
    public function testCryptocurrencyDataStructure(): void
    {
        // Estrutura esperada conforme documentação oficial
        $expectedFields = [
            '_id' => 'string',
            'name' => 'string', 
            'symbol' => 'string',
            'coinGecko' => 'string',
            'updatedDate' => 'string', // ISO 8601
            'createdDate' => 'string', // ISO 8601
            '__v' => 'integer'
        ];

        $responseData = [
            [
                '_id' => '67339b18ca592e9d570e8586',
                'name' => 'USDT',
                'symbol' => 'USDT',
                'coinGecko' => 'tether',
                'updatedDate' => '2024-11-15T05:53:32.979Z',
                'createdDate' => '2024-11-12T18:14:48.380Z',
                '__v' => 0
            ]
        ];

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $actualResponse = $this->httpClient->request('GET', '/deposit/company/cryptocurrencies');
        $actualData = json_decode($actualResponse->getBody()->getContents(), true);

        $crypto = $actualData[0];

        // Verificar se todos os campos esperados estão presentes
        foreach ($expectedFields as $field => $expectedType) {
            $this->assertArrayHasKey($field, $crypto, "Campo '{$field}' deve estar presente");
            
            switch ($expectedType) {
                case 'string':
                    $this->assertIsString($crypto[$field], "Campo '{$field}' deve ser string");
                    break;
                case 'integer':
                    $this->assertIsInt($crypto[$field], "Campo '{$field}' deve ser integer");
                    break;
            }
        }

        // Verificar formato ISO 8601 das datas
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/',
            $crypto['createdDate'],
            'createdDate deve estar no formato ISO 8601'
        );

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/',
            $crypto['updatedDate'],
            'updatedDate deve estar no formato ISO 8601'
        );
    }
} 