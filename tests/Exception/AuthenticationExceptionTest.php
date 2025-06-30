<?php

declare(strict_types=1);

namespace XGate\Tests\Exception;

use PHPUnit\Framework\TestCase;
use XGate\Exception\AuthenticationException;
use XGate\Exception\XGateException;

/**
 * Tests for AuthenticationException
 */
class AuthenticationExceptionTest extends TestCase
{
    public function testExtendsXGateException(): void
    {
        $exception = new AuthenticationException();
        $this->assertInstanceOf(XGateException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new AuthenticationException();
        $this->assertEquals('Authentication failed', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $message = 'Custom authentication error';
        $exception = new AuthenticationException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testInvalidCredentials(): void
    {
        $email = 'test@example.com';
        $exception = AuthenticationException::invalidCredentials($email);

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertStringContainsString($email, $exception->getMessage());
        $this->assertStringContainsString('Credenciais inválidas', $exception->getMessage());
    }

    public function testTokenExpired(): void
    {
        $exception = AuthenticationException::tokenExpired();

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertStringContainsString('expirou', $exception->getMessage());
    }

    public function testMissingToken(): void
    {
        $exception = AuthenticationException::missingToken();

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertStringContainsString('não encontrado', $exception->getMessage());
    }

    public function testLoginFailed(): void
    {
        $statusCode = 400;
        $response = 'Invalid request';
        $exception = AuthenticationException::loginFailed($statusCode, $response);

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertStringContainsString((string)$statusCode, $exception->getMessage());
        $this->assertStringContainsString($response, $exception->getMessage());
    }
}
