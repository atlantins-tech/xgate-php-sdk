<?php

declare(strict_types=1);

namespace XGate\Tests\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use XGate\Authentication\AuthenticationManager;
use XGate\Authentication\AuthenticationManagerInterface;
use XGate\Exception\ApiException;
use XGate\Exception\AuthenticationException;
use XGate\Exception\NetworkException;
use XGate\Http\HttpClient;

/**
 * Tests for AuthenticationManager
 */
class AuthenticationManagerTest extends TestCase
{
    private HttpClient&MockObject $httpClient;
    private CacheInterface&MockObject $cache;
    private AuthenticationManager $authManager;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->authManager = new AuthenticationManager($this->httpClient, $this->cache);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(AuthenticationManagerInterface::class, $this->authManager);
    }

    public function testSuccessfulLogin(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $token = 'abc123token';

        // Mock response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode(['access_token' => $token]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);

        // Mock HTTP client
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with('/login', ['email' => $email, 'password' => $password])
            ->willReturn($response);

        // Mock cache storage
        $this->cache
            ->expects($this->once())
            ->method('set')
            ->with('xgate_auth_token', $token, 86400)
            ->willReturn(true);

        $result = $this->authManager->login($email, $password);
        $this->assertTrue($result);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $email = 'test@example.com';
        $password = 'wrongpassword';

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new ApiException('Unauthorized', 401));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Credenciais inválidas');

        $this->authManager->login($email, $password);
    }

    public function testLoginWithNetworkError(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willThrowException(new NetworkException('Network timeout'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Erro de rede durante autenticação');

        $this->authManager->login($email, $password);
    }

    public function testLoginWithMissingToken(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        // Mock response without token
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode(['message' => 'Success']));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token de acesso não encontrado');

        $this->authManager->login($email, $password);
    }

    public function testLoginWithCacheFailure(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $token = 'abc123token';

        // Mock successful response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode(['access_token' => $token]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock cache failure
        $this->cache
            ->expects($this->once())
            ->method('set')
            ->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Falha ao armazenar token');

        $this->authManager->login($email, $password);
    }

    public function testGetTokenSuccess(): void
    {
        $token = 'stored_token_123';

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('xgate_auth_token')
            ->willReturn($token);

        $result = $this->authManager->getToken();
        $this->assertEquals($token, $result);
    }

    public function testGetTokenWhenNotStored(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('xgate_auth_token')
            ->willReturn(null);

        $result = $this->authManager->getToken();
        $this->assertNull($result);
    }

    public function testGetTokenWithCacheException(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException(new class () extends \Exception implements InvalidArgumentException {});

        $result = $this->authManager->getToken();
        $this->assertNull($result);
    }

    public function testIsAuthenticatedWhenTokenExists(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturn('some_token');

        $result = $this->authManager->isAuthenticated();
        $this->assertTrue($result);
    }

    public function testIsAuthenticatedWhenNoToken(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $result = $this->authManager->isAuthenticated();
        $this->assertFalse($result);
    }

    public function testGetAuthorizationHeaderSuccess(): void
    {
        $token = 'auth_token_123';

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturn($token);

        $result = $this->authManager->getAuthorizationHeader();
        $expected = ['Authorization' => 'Bearer ' . $token];

        $this->assertEquals($expected, $result);
    }

    public function testGetAuthorizationHeaderWhenNotAuthenticated(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token de autenticação não encontrado');

        $this->authManager->getAuthorizationHeader();
    }

    public function testLogoutSuccess(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('xgate_auth_token')
            ->willReturn(true);

        $result = $this->authManager->logout();
        $this->assertTrue($result);
    }

    public function testLogoutWithCacheException(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new class () extends \Exception implements InvalidArgumentException {});

        $result = $this->authManager->logout();
        $this->assertFalse($result);
    }
}
