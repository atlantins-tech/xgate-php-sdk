<?php

declare(strict_types=1);

namespace XGate\Tests\Exception;

use PHPUnit\Framework\TestCase;
use XGate\Exception\ApiException;

/**
 * Testes para ApiException
 */
class ApiExceptionTest extends TestCase
{
    /**
     * Testa construção básica da exceção
     */
    public function testBasicConstruction(): void
    {
        $exception = new ApiException('Test error', 404, null, '{"error": "Not found"}');

        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('{"error": "Not found"}', $exception->getResponseBody());
    }

    /**
     * Testa identificação de erro de cliente (4xx)
     */
    public function testIsClientError(): void
    {
        $exception = new ApiException('Client error', 400);
        $this->assertTrue($exception->isClientError());
        $this->assertFalse($exception->isServerError());

        $exception = new ApiException('Not found', 404);
        $this->assertTrue($exception->isClientError());

        $exception = new ApiException('Server error', 500);
        $this->assertFalse($exception->isClientError());
    }

    /**
     * Testa identificação de erro de servidor (5xx)
     */
    public function testIsServerError(): void
    {
        $exception = new ApiException('Server error', 500);
        $this->assertTrue($exception->isServerError());
        $this->assertFalse($exception->isClientError());

        $exception = new ApiException('Bad gateway', 502);
        $this->assertTrue($exception->isServerError());

        $exception = new ApiException('Bad request', 400);
        $this->assertFalse($exception->isServerError());
    }

    /**
     * Testa identificação de erro de autenticação (401)
     */
    public function testIsAuthenticationError(): void
    {
        $exception = new ApiException('Unauthorized', 401);
        $this->assertTrue($exception->isAuthenticationError());

        $exception = new ApiException('Forbidden', 403);
        $this->assertFalse($exception->isAuthenticationError());
    }

    /**
     * Testa identificação de erro de autorização (403)
     */
    public function testIsAuthorizationError(): void
    {
        $exception = new ApiException('Forbidden', 403);
        $this->assertTrue($exception->isAuthorizationError());

        $exception = new ApiException('Unauthorized', 401);
        $this->assertFalse($exception->isAuthorizationError());
    }

    /**
     * Testa identificação de erro não encontrado (404)
     */
    public function testIsNotFoundError(): void
    {
        $exception = new ApiException('Not found', 404);
        $this->assertTrue($exception->isNotFoundError());

        $exception = new ApiException('Bad request', 400);
        $this->assertFalse($exception->isNotFoundError());
    }

    /**
     * Testa identificação de erro de rate limit (429)
     */
    public function testIsRateLimitError(): void
    {
        $exception = new ApiException('Too many requests', 429);
        $this->assertTrue($exception->isRateLimitError());

        $exception = new ApiException('Bad request', 400);
        $this->assertFalse($exception->isRateLimitError());
    }

    /**
     * Testa decodificação de resposta JSON válida
     */
    public function testGetResponseBodyAsArrayValidJson(): void
    {
        $jsonResponse = '{"error": "Validation failed", "details": ["Name is required"]}';
        $exception = new ApiException('Validation error', 422, null, $jsonResponse);

        $responseArray = $exception->getResponseBodyAsArray();
        
        $this->assertIsArray($responseArray);
        $this->assertEquals('Validation failed', $responseArray['error']);
        $this->assertIsArray($responseArray['details']);
        $this->assertEquals('Name is required', $responseArray['details'][0]);
    }

    /**
     * Testa decodificação de resposta não-JSON
     */
    public function testGetResponseBodyAsArrayInvalidJson(): void
    {
        $exception = new ApiException('Server error', 500, null, 'Internal Server Error');

        $responseArray = $exception->getResponseBodyAsArray();
        
        $this->assertNull($responseArray);
    }

    /**
     * Testa decodificação de resposta JSON vazia
     */
    public function testGetResponseBodyAsArrayEmptyJson(): void
    {
        $exception = new ApiException('Empty response', 204, null, '');

        $responseArray = $exception->getResponseBodyAsArray();
        
        $this->assertNull($responseArray);
    }

    /**
     * Testa representação string da exceção
     */
    public function testToString(): void
    {
        $exception = new ApiException('Test error', 404, null, '{"error": "Not found"}');

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString('ApiException', $stringRepresentation);
        $this->assertStringContainsString('[404]', $stringRepresentation);
        $this->assertStringContainsString('Test error', $stringRepresentation);
        $this->assertStringContainsString('{"error": "Not found"}', $stringRepresentation);
        $this->assertStringContainsString('Stack trace:', $stringRepresentation);
    }

    /**
     * Testa construção com exceção anterior
     */
    public function testConstructionWithPreviousException(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $exception = new ApiException('API error', 500, $previousException, '{}');

        $this->assertEquals($previousException, $exception->getPrevious());
        $this->assertEquals('API error', $exception->getMessage());
        $this->assertEquals(500, $exception->getStatusCode());
    }

    /**
     * Testa construção com valores padrão
     */
    public function testConstructionWithDefaults(): void
    {
        $exception = new ApiException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getStatusCode());
        $this->assertEquals('', $exception->getResponseBody());
        $this->assertNull($exception->getPrevious());
    }
} 