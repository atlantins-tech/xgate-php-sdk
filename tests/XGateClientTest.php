<?php

declare(strict_types=1);

namespace XGate\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use XGate\Authentication\AuthenticationManagerInterface;
use XGate\Configuration\ConfigurationManager;
use XGate\Exception\ApiException;
use XGate\Exception\AuthenticationException;
use XGate\Http\HttpClient;
use XGate\XGateClient;

/**
 * Tests for XGateClient
 */
class XGateClientTest extends TestCase
{
    private XGateClient $client;
    private ConfigurationManager&MockObject $config;
    private LoggerInterface&MockObject $logger;
    private CacheInterface&MockObject $cache;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigurationManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        // Mock configuration methods
        $this->config->method('getEnvironment')->willReturn('test');
        $this->config->method('getBaseUrl')->willReturn('https://api.test.com');
        $this->config->method('getTimeout')->willReturn(30);
        $this->config->method('getMaxRetries')->willReturn(3);
        $this->config->method('isDebugMode')->willReturn(false);
        $this->config->method('getCustomHeaders')->willReturn([]);
        $this->config->method('getProxySettings')->willReturn([]);

        $this->client = new XGateClient($this->config, $this->logger, $this->cache);
    }

    public function testConstructorWithArray(): void
    {
        $config = [
            'base_url' => 'https://api.test.com',
            'environment' => 'development',
            'timeout' => 30,
        ];

        $client = new XGateClient($config);

        $this->assertInstanceOf(XGateClient::class, $client);
        $this->assertTrue($client->isInitialized());
        $this->assertEquals('1.0.0-dev', $client->getVersion());
    }

    public function testConstructorWithConfigurationManager(): void
    {
        $this->assertInstanceOf(XGateClient::class, $this->client);
        $this->assertTrue($this->client->isInitialized());
        $this->assertSame($this->config, $this->client->getConfiguration());
    }

    public function testConstructorWithInvalidConfig(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Falha ao inicializar configuração');

        // Configuração inválida que realmente causará erro
        $invalidConfig = [
            'base_url' => 'invalid-url',
            'environment' => 'invalid-environment', // Deve ser 'development' ou 'production'
        ];
        new XGateClient($invalidConfig);
    }

    public function testGetVersion(): void
    {
        $this->assertEquals('1.0.0-dev', $this->client->getVersion());
    }

    public function testGetConfiguration(): void
    {
        $this->assertSame($this->config, $this->client->getConfiguration());
    }

    public function testGetHttpClient(): void
    {
        $httpClient = $this->client->getHttpClient();
        $this->assertInstanceOf(HttpClient::class, $httpClient);
    }

    public function testGetAuthenticationManager(): void
    {
        $authManager = $this->client->getAuthenticationManager();
        $this->assertInstanceOf(AuthenticationManagerInterface::class, $authManager);
    }

    public function testGetLogger(): void
    {
        $this->assertSame($this->logger, $this->client->getLogger());
    }

    public function testGetCache(): void
    {
        $this->assertSame($this->cache, $this->client->getCache());
    }

    public function testIsInitialized(): void
    {
        $this->assertTrue($this->client->isInitialized());
    }

    public function testAuthenticate(): void
    {
        // Mock successful authentication
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->expects($this->once())
            ->method('login')
            ->with('test@example.com', 'password')
            ->willReturn(true);

        // Use reflection to replace the auth manager
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('authManager');
        $property->setAccessible(true);
        $property->setValue($this->client, $authManager);

        $result = $this->client->authenticate('test@example.com', 'password');
        $this->assertTrue($result);
    }

    public function testAuthenticateFailure(): void
    {
        $this->expectException(AuthenticationException::class);

        // Mock failed authentication
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->expects($this->once())
            ->method('login')
            ->with('test@example.com', 'wrongpassword')
            ->willThrowException(new AuthenticationException('Invalid credentials'));

        // Use reflection to replace the auth manager
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('authManager');
        $property->setAccessible(true);
        $property->setValue($this->client, $authManager);

        $this->client->authenticate('test@example.com', 'wrongpassword');
    }

    public function testIsAuthenticated(): void
    {
        // Mock authentication status
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        // Use reflection to replace the auth manager
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('authManager');
        $property->setAccessible(true);
        $property->setValue($this->client, $authManager);

        $result = $this->client->isAuthenticated();
        $this->assertTrue($result);
    }

    public function testLogout(): void
    {
        // Mock successful logout
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->expects($this->once())
            ->method('logout')
            ->willReturn(true);

        // Use reflection to replace the auth manager
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('authManager');
        $property->setAccessible(true);
        $property->setValue($this->client, $authManager);

        $result = $this->client->logout();
        $this->assertTrue($result);
    }

    public function testGetRequest(): void
    {
        $expectedData = ['users' => [['id' => 1, 'name' => 'Test User']]];

        // Mock HTTP response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/users', [])
            ->willReturn($response);

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $result = $this->client->get('/users');
        $this->assertEquals($expectedData, $result);
    }

    public function testPostRequest(): void
    {
        $postData = ['name' => 'New User', 'email' => 'new@example.com'];
        $expectedData = ['id' => 123, 'name' => 'New User', 'email' => 'new@example.com'];

        // Mock HTTP response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', '/users', ['json' => $postData])
            ->willReturn($response);

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $result = $this->client->post('/users', $postData);
        $this->assertEquals($expectedData, $result);
    }

    public function testPutRequest(): void
    {
        $putData = ['name' => 'Updated User'];
        $expectedData = ['id' => 123, 'name' => 'Updated User'];

        // Mock HTTP response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('PUT', '/users/123', ['json' => $putData])
            ->willReturn($response);

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $result = $this->client->put('/users/123', $putData);
        $this->assertEquals($expectedData, $result);
    }

    public function testDeleteRequest(): void
    {
        $expectedData = ['success' => true];

        // Mock HTTP response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', '/users/123', [])
            ->willReturn($response);

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $result = $this->client->delete('/users/123');
        $this->assertEquals($expectedData, $result);
    }

    public function testPatchRequest(): void
    {
        $patchData = ['status' => 'active'];
        $expectedData = ['id' => 123, 'status' => 'active'];

        // Mock HTTP response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('PATCH', '/users/123', ['json' => $patchData])
            ->willReturn($response);

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $result = $this->client->patch('/users/123', $patchData);
        $this->assertEquals($expectedData, $result);
    }

    public function testRequestWithAuthentication(): void
    {
        $expectedData = ['protected' => 'data'];
        $authHeaders = ['Authorization' => 'Bearer test-token'];

        // Mock HTTP response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/protected', ['headers' => $authHeaders])
            ->willReturn($response);

        // Mock auth manager (authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(true);
        $authManager->method('getAuthorizationHeader')->willReturn($authHeaders);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $result = $this->client->get('/protected');
        $this->assertEquals($expectedData, $result);
    }

    public function testRequestWithInvalidJson(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Resposta da API não é um JSON válido');

        // Mock HTTP response with invalid JSON
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('invalid json {');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('request')->willReturn($response);

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $this->client->get('/test');
    }

    public function testRequestWithHttpException(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Erro inesperado na requisição');

        // Mock HTTP client to throw exception
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('request')
            ->willThrowException(new \Exception('Network error'));

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $this->client->get('/test');
    }

    public function testRequestWithXGateException(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Custom API error');

        // Mock HTTP client to throw ApiException (concrete class)
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('request')
            ->willThrowException(new ApiException('Custom API error'));

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $this->client->get('/test');
    }

    public function testRequestWithEmptyJsonResponse(): void
    {
        // Mock HTTP response with valid empty JSON
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('{}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        // Mock HTTP client
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('request')->willReturn($response);

        // Mock auth manager (not authenticated)
        $authManager = $this->createMock(AuthenticationManagerInterface::class);
        $authManager->method('isAuthenticated')->willReturn(false);

        // Use reflection to replace dependencies
        $reflection = new \ReflectionClass($this->client);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this->client, $httpClient);

        $authProperty = $reflection->getProperty('authManager');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->client, $authManager);

        $result = $this->client->get('/test');
        $this->assertEquals([], $result);
    }
}
