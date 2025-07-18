<?php

declare(strict_types=1);

namespace XGate\Tests\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use XGate\Configuration\ConfigurationManager;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use XGate\Exception\ValidationException;
use XGate\Exception\RateLimitException;
use XGate\Http\HttpClient;

/**
 * Testes para o HttpClient
 *
 * Esta classe testa todas as funcionalidades do cliente HTTP,
 * incluindo requisições, middleware de logging, tratamento de erros
 * e integração com o ConfigurationManager.
 */
class HttpClientTest extends TestCase
{
    private ConfigurationManager $config;
    private Logger $logger;
    private TestHandler $testHandler;
    private MockHandler $mockHandler;
    private HttpClient $httpClient;

    /**
     * Configuração antes de cada teste
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configuração de teste - removendo api_key desnecessária
        $this->config = ConfigurationManager::fromArray([
            'base_url' => 'https://api.test.com',
            'timeout' => 30,
            'debug_mode' => true,
        ]);

        // Logger de teste
        $this->testHandler = new TestHandler();
        $this->logger = new Logger('test', [$this->testHandler]);

        // Mock handler para simular respostas
        $this->mockHandler = new MockHandler();
        
        // HttpClient sem cliente pré-configurado para que ele configure seus próprios middlewares
        $this->httpClient = new HttpClient($this->config, $this->logger);
        
        // Substitui o handler do cliente Guzzle interno pelo nosso mock
        $reflection = new \ReflectionClass($this->httpClient);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $guzzleClient = $clientProperty->getValue($this->httpClient);
        
        // Obtém o handler stack atual e substitui o handler base pelo mock
        $guzzleConfig = $guzzleClient->getConfig();
        $handlerStack = $guzzleConfig['handler'];
        $handlerStack->setHandler($this->mockHandler);
    }

    /**
     * Limpeza após cada teste
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Testa construção do HttpClient com configuração válida
     */
    public function testConstructorWithValidConfiguration(): void
    {
        $httpClient = new HttpClient($this->config, $this->logger);

        $this->assertInstanceOf(HttpClient::class, $httpClient);
        $this->assertNotNull($httpClient->getGuzzleClient());
    }

    /**
     * Testa requisição GET bem-sucedida
     */
    public function testGetRequestSuccess(): void
    {
        // Simula resposta de sucesso
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], '{"success": true}')
        );

        $response = $this->httpClient->get('/test');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"success": true}', (string) $response->getBody());
    }

    /**
     * Testa requisição POST com dados JSON
     */
    public function testPostRequestWithJsonData(): void
    {
        $this->mockHandler->append(
            new Response(201, ['Content-Type' => 'application/json'], '{"id": 123}')
        );

        $response = $this->httpClient->post('/users', [
            'json' => ['name' => 'João', 'email' => 'joao@example.com'],
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('{"id": 123}', (string) $response->getBody());
    }

    /**
     * Testa requisição PUT
     */
    public function testPutRequest(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"updated": true}')
        );

        $response = $this->httpClient->put('/users/123', [
            'json' => ['name' => 'João Silva'],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"updated": true}', (string) $response->getBody());
    }

    /**
     * Testa requisição DELETE
     */
    public function testDeleteRequest(): void
    {
        $this->mockHandler->append(
            new Response(204, [], '')
        );

        $response = $this->httpClient->delete('/users/123');

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * Testa requisição PATCH
     */
    public function testPatchRequest(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"patched": true}')
        );

        $response = $this->httpClient->patch('/users/123', [
            'json' => ['status' => 'active'],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"patched": true}', (string) $response->getBody());
    }

    /**
     * Testa requisição genérica
     */
    public function testGenericRequest(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"method": "OPTIONS"}')
        );

        $response = $this->httpClient->request('OPTIONS', '/test');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"method": "OPTIONS"}', (string) $response->getBody());
    }

    /**
     * Testa tratamento de erro 404 (ApiException)
     */
    public function testApiException404(): void
    {
        $this->mockHandler->append(
            new Response(404, [], '{"error": "Not found"}')
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Erro da API:');

        try {
            $this->httpClient->get('/nonexistent');
        } catch (ApiException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('{"error": "Not found"}', $e->getResponseBody());
            $this->assertTrue($e->isNotFoundError());
            $this->assertTrue($e->isClientError());
            $this->assertFalse($e->isServerError());

            throw $e;
        }
    }

    /**
     * Testa tratamento de erro 401 (Autenticação)
     */
    public function testApiException401(): void
    {
        $this->mockHandler->append(
            new Response(401, [], '{"error": "Unauthorized"}')
        );

        $this->expectException(ApiException::class);

        try {
            $this->httpClient->get('/protected');
        } catch (ApiException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            $this->assertTrue($e->isAuthenticationError());
            $this->assertTrue($e->isClientError());

            throw $e;
        }
    }

    /**
     * Testa tratamento de erro 403 (Autorização)
     */
    public function testApiException403(): void
    {
        $this->mockHandler->append(
            new Response(403, [], '{"error": "Forbidden"}')
        );

        $this->expectException(ApiException::class);

        try {
            $this->httpClient->get('/admin');
        } catch (ApiException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertTrue($e->isAuthorizationError());

            throw $e;
        }
    }

    /**
     * Testa tratamento de erro 429 (Rate Limit)
     */
    public function testApiException429(): void
    {
        // Adiciona mocks suficientes para cobrir retries (429 é retryado)
        $this->mockHandler->append(
            new Response(429, [], '{"error": "Too many requests"}'),
            new Response(429, [], '{"error": "Too many requests"}'),
            new Response(429, [], '{"error": "Too many requests"}'),
            new Response(429, [], '{"error": "Too many requests"}') // Para cobrir todos os retries
        );

        $this->expectException(ApiException::class);

        try {
            $this->httpClient->get('/rate-limited');
        } catch (ApiException $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertTrue($e->isRateLimitError());

            throw $e;
        }
    }

    /**
     * Testa tratamento de erro 500 (Servidor)
     */
    public function testApiException500(): void
    {
        $this->mockHandler->append(
            new Response(500, [], '{"error": "Internal server error"}')
        );

        $this->expectException(ApiException::class);

        try {
            $this->httpClient->get('/server-error');
        } catch (ApiException $e) {
            $this->assertEquals(500, $e->getStatusCode());
            $this->assertTrue($e->isServerError());
            $this->assertFalse($e->isClientError());

            throw $e;
        }
    }

    /**
     * Testa tratamento de exceções de rede
     */
    public function testNetworkException(): void
    {
        // Adiciona mocks suficientes para cobrir retries (ConnectionException é retryada)
        $this->mockHandler->append(
            new ConnectException('Connection timeout', new Request('GET', '/timeout')),
            new ConnectException('Connection timeout', new Request('GET', '/timeout')),
            new ConnectException('Connection timeout', new Request('GET', '/timeout')),
            new ConnectException('Connection timeout', new Request('GET', '/timeout')) // Para cobrir todos os retries
        );

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Erro de rede:');

        try {
            $this->httpClient->get('/timeout');
        } catch (NetworkException $e) {
            $this->assertTrue($e->isTimeoutError());
            $this->assertTrue($e->isRetryable());
            $this->assertStringContainsString('timeout', $e->getSuggestion());

            throw $e;
        }
    }

    /**
     * Testa logging de requisições em modo debug
     */
    public function testRequestLogging(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"logged": true}')
        );

        $this->httpClient->get('/test');

        // Verifica se logs foram registrados
        $this->assertTrue($this->testHandler->hasDebugRecords());

        $debugRecords = $this->testHandler->getRecords();
        $requestLog = null;
        $responseLog = null;

        foreach ($debugRecords as $record) {
            if ($record['message'] === 'HTTP Request') {
                $requestLog = $record;
            } elseif ($record['message'] === 'HTTP Response') {
                $responseLog = $record;
            }
        }

        $this->assertNotNull($requestLog);
        $this->assertNotNull($responseLog);

        // Verifica conteúdo do log de requisição
        $this->assertIsArray($requestLog['context']);
        $requestContext = $requestLog['context'];
        $this->assertEquals('GET', $requestContext['method']);
        $this->assertArrayHasKey('uri', $requestContext);
        $this->assertArrayHasKey('headers', $requestContext);

        // Verifica conteúdo do log de resposta
        $this->assertIsArray($responseLog['context']);
        $responseContext = $responseLog['context'];
        $this->assertEquals(200, $responseContext['status_code']);
        $this->assertEquals('{"logged": true}', $responseContext['body']);
    }

    /**
     * Testa sanitização de headers sensíveis
     */
    public function testHeaderSanitization(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{}')
        );

        $this->httpClient->get('/test', [
            'headers' => [
                'Authorization' => 'Bearer secret-token',
                'X-API-Key' => 'api-key-123',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertTrue($this->testHandler->hasDebugRecords());

        $requestLog = null;
        foreach ($this->testHandler->getRecords() as $record) {
            if ($record['message'] === 'HTTP Request') {
                $requestLog = $record;

                break;
            }
        }

        $this->assertNotNull($requestLog);

        // Headers sensíveis devem estar mascarados
        $this->assertIsArray($requestLog['context']);
        $requestContext = $requestLog['context'];
        $this->assertIsArray($requestContext['headers']);
        $headers = $requestContext['headers'];
        $this->assertEquals('***', $headers['authorization'] ?? $headers['Authorization'] ?? null);
    }

    /**
     * Testa sanitização de body com dados sensíveis
     */
    public function testBodySanitization(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"success": true}')
        );

        $this->httpClient->post('/login', [
            'json' => [
                'username' => 'user@example.com',
                'password' => 'secret123',
                'api_key' => 'key123',
            ],
        ]);

        $this->assertTrue($this->testHandler->hasDebugRecords());
    }

    /**
     * Testa requisição assíncrona
     */
    public function testAsyncRequest(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"async": true}')
        );

        $promise = $this->httpClient->requestAsync('GET', '/async-test');
        $response = $promise->wait();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"async": true}', (string) $response->getBody());
    }

    /**
     * Testa ApiException com resposta JSON
     */
    public function testApiExceptionJsonResponse(): void
    {
        $jsonResponse = '{"error": "Bad Request", "details": ["Invalid request format"]}';

        $this->mockHandler->append(
            new Response(400, [], $jsonResponse)
        );

        $this->expectException(ApiException::class);

        try {
            $this->httpClient->post('/validate', ['json' => []]);
        } catch (ApiException $e) {
            $responseArray = $e->getResponseBodyAsArray();
            $this->assertIsArray($responseArray);
            $this->assertEquals('Bad Request', $responseArray['error']);
            $this->assertIsArray($responseArray['details']);

            throw $e;
        }
    }

    /**
     * Testa ApiException com resposta não-JSON
     */
    public function testApiExceptionNonJsonResponse(): void
    {
        $this->mockHandler->append(
            new Response(500, [], 'Internal Server Error')
        );

        $this->expectException(ApiException::class);

        try {
            $this->httpClient->get('/error');
        } catch (ApiException $e) {
            $this->assertNull($e->getResponseBodyAsArray());
            $this->assertEquals('Internal Server Error', $e->getResponseBody());

            throw $e;
        }
    }

    /**
     * Testa configuração sem modo debug (sem logs)
     */
    public function testNoLoggingWhenDebugDisabled(): void
    {
        // Configuração sem debug
        $config = ConfigurationManager::fromArray([
            'base_url' => 'https://api.test.com',
            'timeout' => 30,
            'debug_mode' => false,
        ]);

        // Cria novo logger para este teste
        $testHandler = new TestHandler();
        $logger = new Logger('test', [$testHandler]);

        // Cria novo mock handler
        $mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $httpClient = new HttpClient($config, $logger, $guzzleClient);

        $mockHandler->append(
            new Response(200, [], '{"no_debug": true}')
        );

        $httpClient->get('/test');

        // Não deve haver logs de debug
        $this->assertFalse($testHandler->hasDebugRecords());
    }

    /**
     * Testa acesso ao cliente Guzzle subjacente
     */
    public function testGetGuzzleClient(): void
    {
        $guzzleClient = $this->httpClient->getGuzzleClient();

        $this->assertNotNull($guzzleClient);
        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $guzzleClient);
    }



    /**
     * Remove informações sensíveis dos headers para logging (para testes)
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie'];
        $sanitized = [];

        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), $sensitiveHeaders, true)) {
                $sanitized[$name] = '***';
            } else {
                $sanitized[$name] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Remove informações sensíveis do body para logging (para testes)
     */
    private function sanitizeBody(string $body): string
    {
        // Se é JSON, tenta mascarar campos sensíveis
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $sensitiveFields = ['password', 'token', 'secret', 'api_key', 'private_key'];

            foreach ($sensitiveFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '***';
                }
            }

            return json_encode($data) ?: $body;
        }

        // Se não é JSON válido, limita o tamanho
        return strlen($body) > 1000 ? substr($body, 0, 1000) . '...[truncated]' : $body;
    }

    /**
     * Cria um stream mock para testes
     */
    private function createStream(string $content): \Psr\Http\Message\StreamInterface
    {
        $stream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $stream->method('__toString')->willReturn($content);
        $stream->method('getContents')->willReturn($content);
        return $stream;
    }

    public function testCreateValidationExceptionFrom422Response(): void
    {
        $errorData = [
            'message' => 'Validation failed',
            'errors' => [
                'email' => ['Email is required', 'Email format is invalid'],
                'amount' => ['Amount must be positive']
            ]
        ];

        // Simula resposta 422 com erro de validação
        $this->mockHandler->append(
            new Response(422, ['Content-Type' => 'application/json'], json_encode($errorData))
        );

        try {
            $this->httpClient->get('/test');
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $this->assertEquals(422, $e->getCode());
            $this->assertStringContainsString('Validation failed', $e->getMessage());
            $this->assertEquals($errorData['errors'], $e->getValidationErrors());
            $this->assertEquals('email', $e->getFailedField());
            $this->assertEquals('api_validation', $e->getFailedRule());
        }
    }

    public function testCreateValidationExceptionFromGenericValidationError(): void
    {
        $errorData = [
            'message' => 'The given data was invalid',
        ];

        // Simula resposta 422 com erro genérico de validação
        $this->mockHandler->append(
            new Response(422, ['Content-Type' => 'application/json'], json_encode($errorData))
        );

        try {
            $this->httpClient->post('/test');
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $this->assertEquals(422, $e->getCode());
            $this->assertStringContainsString('The given data was invalid', $e->getMessage());
            $validationErrors = $e->getValidationErrors();
            $this->assertArrayHasKey('_general', $validationErrors);
            $this->assertStringContainsString('The given data was invalid', $validationErrors['_general'][0]);
        }
    }

    public function testCreateRateLimitExceptionFrom429Response(): void
    {
        $errorData = [
            'message' => 'Rate limit exceeded',
        ];

        // Simula resposta 429 com headers de rate limit (Retry-After > 60 para não fazer retry automático)
        $this->mockHandler->append(
            new Response(429, [
                'Content-Type' => 'application/json',
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string)(time() + 3600),
                'Retry-After' => '120' // > 60 para não fazer retry automático
            ], json_encode($errorData))
        );

        try {
            $this->httpClient->get('/test');
            $this->fail('Expected RateLimitException to be thrown');
        } catch (RateLimitException $e) {
            $this->assertEquals(429, $e->getCode());
            $this->assertStringContainsString('Rate limit exceeded', $e->getMessage());
            $this->assertEquals(100, $e->getRateLimit());
            $this->assertEquals(0, $e->getRateLimitRemaining());
            $this->assertEquals(120, $e->getRetryAfterSeconds());
            $this->assertTrue($e->isFullyExhausted());
        }
    }

    public function testRateLimitRetryLogic(): void
    {
        $errorData = ['message' => 'Rate limit exceeded'];

        // Primeira resposta: 429 com retry
        $this->mockHandler->append(
            new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => '2' // 2 segundos
            ], json_encode($errorData))
        );

        // Segunda resposta: sucesso
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], '{"success": true}')
        );

        // O teste deve ser bem rápido pois estamos mockando o sleep
        $result = $this->httpClient->get('/test');
        $this->assertInstanceOf(ResponseInterface::class, $result);
        
        // Verifica se a mensagem de retry foi logada através do TestHandler
        $logRecords = $this->testHandler->getRecords();
        $retryLogFound = false;
        foreach ($logRecords as $record) {
            if (strpos($record['message'], 'Rate limit hit') !== false) {
                $retryLogFound = true;
                break;
            }
        }
        $this->assertTrue($retryLogFound, 'Rate limit retry message should be logged');
    }

    public function testRateLimitExceptionWhenRetryAfterTooLong(): void
    {
        $errorData = ['message' => 'Rate limit exceeded'];

        // Simula resposta 429 com retry muito longo
        $this->mockHandler->append(
            new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => '120' // 2 minutos - muito longo
            ], json_encode($errorData))
        );

        try {
            $this->httpClient->get('/test');
            $this->fail('Expected RateLimitException to be thrown');
        } catch (RateLimitException $e) {
            $this->assertEquals(429, $e->getCode());
            $this->assertEquals(120, $e->getRetryAfterSeconds());
        }
    }

    public function testApiExceptionForOtherStatusCodes(): void
    {
        $errorData = ['message' => 'Internal server error'];

        // Simula resposta 500
        $this->mockHandler->append(
            new Response(500, ['Content-Type' => 'application/json'], json_encode($errorData))
        );

        try {
            $this->httpClient->get('/test');
            $this->fail('Expected ApiException to be thrown');
        } catch (ApiException $e) {
            $this->assertEquals(500, $e->getCode());
            $this->assertStringContainsString('Internal server error', $e->getMessage());
            // Deve ser ApiException genérica, não ValidationException ou RateLimitException
            $this->assertInstanceOf(ApiException::class, $e);
            $this->assertNotInstanceOf(ValidationException::class, $e);
            $this->assertNotInstanceOf(RateLimitException::class, $e);
        }
    }
}
