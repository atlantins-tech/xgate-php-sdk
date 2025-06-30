<?php

declare(strict_types=1);

namespace XGate\Tests\Exception;

use PHPUnit\Framework\TestCase;
use XGate\Exception\NetworkException;

/**
 * Testes para NetworkException
 */
class NetworkExceptionTest extends TestCase
{
    /**
     * Testa construção básica da exceção
     */
    public function testBasicConstruction(): void
    {
        $exception = new NetworkException('Connection failed', 1001);

        $this->assertEquals('Connection failed', $exception->getMessage());
        $this->assertEquals(1001, $exception->getCode());
    }

    /**
     * Testa identificação de erro de timeout
     */
    public function testIsTimeoutError(): void
    {
        $exception = new NetworkException('Connection timeout');
        $this->assertTrue($exception->isTimeoutError());

        $exception = new NetworkException('Request timed out');
        $this->assertTrue($exception->isTimeoutError());

        $exception = new NetworkException('Connection refused');
        $this->assertFalse($exception->isTimeoutError());
    }

    /**
     * Testa identificação de erro de DNS
     */
    public function testIsDnsError(): void
    {
        $exception = new NetworkException('DNS resolution failed');
        $this->assertTrue($exception->isDnsError());

        $exception = new NetworkException('Could not resolve host');
        $this->assertTrue($exception->isDnsError());

        $exception = new NetworkException('Name resolution error');
        $this->assertTrue($exception->isDnsError());

        $exception = new NetworkException('Connection timeout');
        $this->assertFalse($exception->isDnsError());
    }

    /**
     * Testa identificação de erro de conexão recusada
     */
    public function testIsConnectionRefusedError(): void
    {
        $exception = new NetworkException('Connection refused');
        $this->assertTrue($exception->isConnectionRefusedError());

        $exception = new NetworkException('Connection denied');
        $this->assertTrue($exception->isConnectionRefusedError());

        $exception = new NetworkException('DNS error');
        $this->assertFalse($exception->isConnectionRefusedError());
    }

    /**
     * Testa identificação de erro de SSL
     */
    public function testIsSslError(): void
    {
        $exception = new NetworkException('SSL certificate error');
        $this->assertTrue($exception->isSslError());

        $exception = new NetworkException('TLS handshake failed');
        $this->assertTrue($exception->isSslError());

        $exception = new NetworkException('Certificate verification failed');
        $this->assertTrue($exception->isSslError());

        $exception = new NetworkException('Connection timeout');
        $this->assertFalse($exception->isSslError());
    }

    /**
     * Testa identificação de erros que podem ser tentados novamente
     */
    public function testIsRetryable(): void
    {
        // Timeout é retryable
        $exception = new NetworkException('Connection timeout');
        $this->assertTrue($exception->isRetryable());

        // DNS é retryable
        $exception = new NetworkException('DNS resolution failed');
        $this->assertTrue($exception->isRetryable());

        // Connection refused é retryable
        $exception = new NetworkException('Connection refused');
        $this->assertTrue($exception->isRetryable());

        // SSL não é retryable
        $exception = new NetworkException('SSL certificate error');
        $this->assertFalse($exception->isRetryable());
    }

    /**
     * Testa sugestões para erro de timeout
     */
    public function testGetSuggestionForTimeout(): void
    {
        $exception = new NetworkException('Connection timeout');
        $suggestion = $exception->getSuggestion();

        $this->assertStringContainsString('conexão', $suggestion);
        $this->assertStringContainsString('timeout', $suggestion);
    }

    /**
     * Testa sugestões para erro de DNS
     */
    public function testGetSuggestionForDns(): void
    {
        $exception = new NetworkException('DNS resolution failed');
        $suggestion = $exception->getSuggestion();

        $this->assertStringContainsString('DNS', $suggestion);
        $this->assertStringContainsString('conexão', $suggestion);
    }

    /**
     * Testa sugestões para erro de conexão recusada
     */
    public function testGetSuggestionForConnectionRefused(): void
    {
        $exception = new NetworkException('Connection refused');
        $suggestion = $exception->getSuggestion();

        $this->assertStringContainsString('servidor', $suggestion);
        $this->assertStringContainsString('indisponível', $suggestion);
    }

    /**
     * Testa sugestões para erro de SSL
     */
    public function testGetSuggestionForSsl(): void
    {
        $exception = new NetworkException('SSL certificate error');
        $suggestion = $exception->getSuggestion();

        $this->assertStringContainsString('SSL', $suggestion);
        $this->assertStringContainsString('certificado', $suggestion);
    }

    /**
     * Testa sugestão genérica para outros erros
     */
    public function testGetSuggestionGeneric(): void
    {
        $exception = new NetworkException('Unknown network error');
        $suggestion = $exception->getSuggestion();

        $this->assertStringContainsString('conexão', $suggestion);
        $this->assertStringContainsString('internet', $suggestion);
    }

    /**
     * Testa representação string da exceção
     */
    public function testToString(): void
    {
        $exception = new NetworkException('Connection timeout', 1001);

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString('NetworkException', $stringRepresentation);
        $this->assertStringContainsString('[1001]', $stringRepresentation);
        $this->assertStringContainsString('Connection timeout', $stringRepresentation);
        $this->assertStringContainsString('Sugestão:', $stringRepresentation);
        $this->assertStringContainsString('Stack trace:', $stringRepresentation);
    }

    /**
     * Testa construção com exceção anterior
     */
    public function testConstructionWithPreviousException(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $exception = new NetworkException('Network error', 1001, $previousException);

        $this->assertEquals($previousException, $exception->getPrevious());
        $this->assertEquals('Network error', $exception->getMessage());
        $this->assertEquals(1001, $exception->getCode());
    }

    /**
     * Testa construção com valores padrão
     */
    public function testConstructionWithDefaults(): void
    {
        $exception = new NetworkException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * Testa detecção case-insensitive
     */
    public function testCaseInsensitiveDetection(): void
    {
        $exception = new NetworkException('CONNECTION TIMEOUT');
        $this->assertTrue($exception->isTimeoutError());

        $exception = new NetworkException('dns Resolution Failed');
        $this->assertTrue($exception->isDnsError());

        $exception = new NetworkException('SSL Certificate Error');
        $this->assertTrue($exception->isSslError());
    }
}
