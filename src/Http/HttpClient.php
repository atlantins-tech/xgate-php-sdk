<?php

declare(strict_types=1);

namespace XGate\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use XGate\Configuration\ConfigurationManager;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use XGate\Exception\ValidationException;
use XGate\Exception\RateLimitException;

/**
 * Cliente HTTP wrapper para Guzzle com middleware avançado
 *
 * Esta classe fornece uma interface robusta para requisições HTTP usando Guzzle,
 * incluindo logging automático, tratamento de erros personalizado, autenticação
 * e suporte a retry com backoff exponencial.
 *
 * @package XGate\Http
 * @author XGate PHP SDK Contributors
 * @version 1.0.0
 *
 * @example
 * ```php
 * $config = new ConfigurationManager(['api_key' => 'your-api-key']);
 * $httpClient = new HttpClient($config);
 *
 * $response = $httpClient->get('/api/endpoint', [
 *     'query' => ['param' => 'value']
 * ]);
 * ```
 */
class HttpClient
{
    /**
     * Cliente Guzzle configurado
     *
     * @var Client
     */
    private Client $client;

    /**
     * Gerenciador de configuração
     *
     * @var ConfigurationManager
     */
    private ConfigurationManager $config;

    /**
     * Logger para requisições e respostas
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Headers padrão para todas as requisições
     *
     * @var array<string, string>
     */
    private array $defaultHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'User-Agent' => 'XGate-PHP-SDK/1.0.0',
    ];

    /**
     * Construtor do HttpClient
     *
     * Inicializa o cliente HTTP com configuração personalizada, middleware
     * e handlers para logging, autenticação e tratamento de erros.
     *
     * @param ConfigurationManager $config Configuração do cliente
     * @param LoggerInterface|null $logger Logger opcional (cria um padrão se não fornecido)
     * @param Client|null $client Cliente Guzzle opcional (cria um padrão se não fornecido)
     *
     * @throws \InvalidArgumentException Se a configuração for inválida
     */
    public function __construct(ConfigurationManager $config, ?LoggerInterface $logger = null, ?Client $client = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new Logger('xgate-http');

        if ($client !== null) {
            $this->client = $client;
        } else {
            $this->setupClient();
        }
    }

    /**
     * Configura o cliente HTTP com middlewares e configurações
     */
    private function setupClient(): void
    {
        $stack = HandlerStack::create();

        // Middleware de logging (apenas se debug habilitado)
        if ($this->config->isDebugMode()) {
            $stack->push($this->createLoggingMiddleware(), 'logging');
        }

        // Middleware de tratamento de erros removido - lógica agora está em executeRequest()

        // Configuração do cliente
        $clientConfig = [
            'base_uri' => $this->config->getBaseUrl(),
            'handler' => $stack,
            'timeout' => $this->config->getTimeout(),
            'headers' => array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'XGATE-PHP-SDK/1.0.0',
            ], $this->config->getCustomHeaders()),
        ];

        // Configurações de proxy se disponíveis
        $proxyConfig = $this->buildProxyConfig();
        if (!empty($proxyConfig)) {
            $clientConfig = array_merge($clientConfig, $proxyConfig);
        }

        $this->client = new Client($clientConfig);
    }

    /**
     * Cria middleware de logging
     *
     * Registra detalhes das requisições e respostas para debug e monitoramento
     *
     * @return callable
     */
    private function createLoggingMiddleware(): callable
    {
        return function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
                $start = microtime(true);

                // Log da requisição
                $this->logRequest($request);

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request, $start): ResponseInterface {
                        $duration = round((microtime(true) - $start) * 1000, 2);
                        $this->logResponse($request, $response, $duration);

                        return $response;
                    },
                    function ($reason) use ($request, $start) {
                        $duration = round((microtime(true) - $start) * 1000, 2);
                        $this->logError($request, $reason, $duration);

                        throw $reason;
                    }
                );
            };
        };
    }

    /**
     * Registra detalhes da requisição
     *
     * @param RequestInterface $request
     * @return void
     */
    private function logRequest(RequestInterface $request): void
    {
        if (!$this->config->isDebugMode()) {
            return;
        }

        $headers = $this->maskSensitiveHeaders($request->getHeaders());
        $body = $this->maskSensitiveBody((string) $request->getBody());

        $this->logger->debug('HTTP Request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $headers,
            'body' => $body,
        ]);
    }

    /**
     * Registra detalhes da resposta
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param float $duration Duração em milissegundos
     * @return void
     */
    private function logResponse(RequestInterface $request, ResponseInterface $response, float $duration): void
    {
        if (!$this->config->isDebugMode()) {
            return;
        }

        $this->logger->debug('HTTP Response', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
            'duration_ms' => $duration,
        ]);
    }

    /**
     * Registra erros de requisição
     *
     * @param RequestInterface $request
     * @param \Throwable $error
     * @param float $duration Duração em milissegundos
     * @return void
     */
    private function logError(RequestInterface $request, \Throwable $error, float $duration): void
    {
        $this->logger->error('HTTP Request Error', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'error' => $error->getMessage(),
            'error_class' => get_class($error),
            'duration_ms' => $duration,
        ]);
    }

    /**
     * Cria middleware para tratamento de erros HTTP
     *
     * @return callable
     */
    private function createErrorHandlingMiddleware(): callable
    {
        return function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request): ResponseInterface {
                        // Verifica se é um erro HTTP
                        if ($response->getStatusCode() >= 400) {
                            throw $this->createApiException($request, $response);
                        }

                        return $response;
                    },
                    function ($reason) use ($request) {
                        // Captura exceções do Guzzle e converte para nossas exceções
                        if ($reason instanceof RequestException) {
                            $response = $reason->getResponse();
                            
                            if ($response !== null) {
                                // Se há resposta, é um erro da API
                                throw $this->createApiException($request, $response, $reason);
                            }
                            
                            // Sem resposta, é erro de rede
                            throw new NetworkException(
                                "Erro de rede: {$reason->getMessage()}",
                                $reason->getCode(),
                                $reason
                            );
                        }

                        throw $reason;
                    }
                );
            };
        };
    }

    /**
     * Cria exceção específica baseada na resposta da API
     *
     * Este método serve como factory para diferentes tipos de exceção,
     * analisando o status HTTP e headers para determinar o tipo mais apropriado.
     *
     * @param RequestInterface $request Requisição original
     * @param ResponseInterface $response Resposta da API
     * @param \Throwable|null $previous Exceção anterior na cadeia
     * 
     * @return ApiException|ValidationException|RateLimitException
     */
    private function createApiException(RequestInterface $request, ResponseInterface $response, ?\Throwable $previous = null): ApiException|ValidationException|RateLimitException
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // Tenta decodificar JSON para obter dados de erro
        $errorData = json_decode($body, true);
        $errorMessage = $errorData['message'] ?? $errorData['error'] ?? "HTTP {$statusCode}";

        // Factory pattern: cria exceção específica baseada no status code
        switch ($statusCode) {
            case 422:
                return $this->createValidationException($errorData, $errorMessage, $request, $response, $previous);
            
            case 429:
                return $this->createRateLimitException($errorData, $errorMessage, $request, $response, $previous);
            
            default:
                // Para outros códigos, cria ApiException genérica
                $message = "Erro da API: {$errorMessage}";
                return new ApiException($message, $request, $response, $previous);
        }
    }

    /**
     * Cria ValidationException a partir da resposta da API
     *
     * @param array<string, mixed>|null $errorData Dados de erro decodificados
     * @param string $errorMessage Mensagem de erro principal
     * @param RequestInterface $request Requisição original
     * @param ResponseInterface $response Resposta da API
     * @param \Throwable|null $previous Exceção anterior
     * 
     * @return ValidationException
     */
    private function createValidationException(
        ?array $errorData, 
        string $errorMessage, 
        RequestInterface $request, 
        ResponseInterface $response, 
        ?\Throwable $previous
    ): ValidationException {
        // Extrai erros de validação estruturados da resposta
        $validationErrors = [];
        
        if (is_array($errorData)) {
            // Formato padrão: { "errors": { "field": ["error1", "error2"] } }
            if (isset($errorData['errors']) && is_array($errorData['errors'])) {
                $validationErrors = $errorData['errors'];
            }
            // Formato alternativo: { "validation_errors": { ... } }
            elseif (isset($errorData['validation_errors']) && is_array($errorData['validation_errors'])) {
                $validationErrors = $errorData['validation_errors'];
            }
            // Formato Laravel: { "errors": { "field": ["error"] }, "message": "..." }
            elseif (isset($errorData['message']) && stripos($errorData['message'], 'validation') !== false) {
                // Se há indicação de erro de validação mas sem estrutura, cria erro genérico
                $validationErrors = ['_general' => [$errorMessage]];
            }
        }

        // Se não conseguiu extrair erros estruturados, cria erro genérico
        if (empty($validationErrors)) {
            $validationErrors = ['_general' => ['Validation failed: ' . $errorMessage]];
        }

        // Tenta identificar o primeiro campo com erro
        $failedField = null;
        $failedValue = null;
        if (!empty($validationErrors) && !isset($validationErrors['_general'])) {
            $failedField = array_key_first($validationErrors);
        }

        return new ValidationException(
            message: "Validation failed: {$errorMessage}",
            validationErrors: $validationErrors,
            failedField: $failedField,
            failedValue: $failedValue,
            failedRule: 'api_validation',
            code: 422,
            previous: $previous
        );
    }

    /**
     * Cria RateLimitException a partir da resposta da API
     *
     * @param array<string, mixed>|null $errorData Dados de erro decodificados
     * @param string $errorMessage Mensagem de erro principal
     * @param RequestInterface $request Requisição original
     * @param ResponseInterface $response Resposta da API
     * @param \Throwable|null $previous Exceção anterior
     * 
     * @return RateLimitException
     */
    private function createRateLimitException(
        ?array $errorData, 
        string $errorMessage, 
        RequestInterface $request, 
        ResponseInterface $response, 
        ?\Throwable $previous
    ): RateLimitException {
        // Extrai informações de rate limiting dos headers
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = $values[0] ?? '';
        }

        return RateLimitException::fromHttpResponse(
            $response,
            "Rate limit exceeded: {$errorMessage}"
        );
    }

    /**
     * Constrói configuração de proxy
     *
     * @return array<string, mixed>|string
     */
    private function buildProxyConfig()
    {
        $proxyUrl = $this->config->getProxyUrl();
        $proxyAuth = $this->config->getProxyAuth();

        // Se não há URL de proxy, retorna string vazia
        if (empty($proxyUrl)) {
            return '';
        }

        if ($proxyAuth) {
            // Remove senha do log
            $maskedAuth = explode(':', $proxyAuth);
            if (count($maskedAuth) === 2) {
                $maskedAuth[1] = '***';
            }

            return [
                'http' => $proxyUrl,
                'https' => $proxyUrl,
            ];
        }

        return $proxyUrl;
    }

    /**
     * Mascara headers sensíveis para logging
     *
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    private function maskSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie', 'set-cookie'];
        $masked = [];

        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), $sensitiveHeaders)) {
                $masked[$name] = '***';
            } else {
                $masked[$name] = $value;
            }
        }

        return $masked;
    }

    /**
     * Mascara dados sensíveis no body para logging
     *
     * @param string $body
     * @return string
     */
    private function maskSensitiveBody(string $body): string
    {
        if (empty($body)) {
            return $body;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return $body; // Não é JSON, retorna como está
        }

        $sensitiveFields = ['password', 'token', 'api_key', 'secret', 'private_key'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE) ?: $body;
    }

    /**
     * Adiciona um header padrão para todas as requisições
     *
     * @param string $name Nome do header
     * @param string $value Valor do header
     * @return void
     */
    public function setDefaultHeader(string $name, string $value): void
    {
        $this->defaultHeaders[$name] = $value;
    }

    /**
     * Remove um header padrão
     *
     * @param string $name Nome do header
     * @return void
     */
    public function removeDefaultHeader(string $name): void
    {
        unset($this->defaultHeaders[$name]);
    }

    /**
     * Obtém todos os headers padrão
     *
     * @return array<string, string>
     */
    public function getDefaultHeaders(): array
    {
        return $this->defaultHeaders;
    }

    /**
     * Executa requisição com tratamento de exceções e retry
     *
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return ResponseInterface
     * @throws ApiException Em caso de erro da API
     * @throws NetworkException Em caso de erro de rede
     * @throws ValidationException Em caso de erro de validação (422)
     * @throws RateLimitException Em caso de rate limiting (429)
     */
    private function executeRequest(string $method, string $uri, array $options = []): ResponseInterface
    {
        // Mescla headers padrão com headers específicos da requisição
        $options['headers'] = array_merge(
            $this->defaultHeaders,
            $options['headers'] ?? []
        );

        $attempts = 0;
        $maxAttempts = $this->config->getRetryAttempts() + 1; // +1 para incluir a tentativa inicial

        while ($attempts < $maxAttempts) {
            try {
                return $this->client->request($method, $uri, $options);
            } catch (ConnectException $e) {
                $attempts++;

                if ($attempts >= $maxAttempts) {
                    throw new NetworkException(
                        'Erro de rede: Falha de conexão após ' . $this->config->getRetryAttempts() . ' tentativas: ' . $e->getMessage(),
                        $e->getRequest(),
                        null,
                        $e
                    );
                }

                // Backoff exponencial: 1s, 2s, 4s, 8s...
                $delay = (int) (1000000 * pow(2, $attempts - 1)); // microsegundos
                usleep($delay);

                continue;
            } catch (RequestException $e) {
                $attempts++;

                if ($e->hasResponse()) {
                    $response = $e->getResponse();

                    // Verificação adicional para PHPStan (hasResponse() garante que getResponse() não é null)
                    if ($response === null) {
                        continue; // Nunca deve acontecer, mas satisfaz o PHPStan
                    }

                    $statusCode = $response->getStatusCode();

                    // Para rate limiting (429), cria exceção específica com informações de retry
                    if ($statusCode === 429) {
                        $rateLimitException = $this->createRateLimitException(
                            json_decode((string) $response->getBody(), true),
                            'Rate limit exceeded',
                            $e->getRequest(),
                            $response,
                            $e
                        );

                        // Se ainda há tentativas, aguarda o tempo recomendado e tenta novamente
                        if ($attempts < $maxAttempts) {
                            $retryAfter = $rateLimitException->getRetryAfter();
                            if ($retryAfter > 0 && $retryAfter <= 60) { // Máximo 60 segundos de espera
                                $this->logger->info("Rate limit hit, waiting {$retryAfter} seconds before retry", [
                                    'attempt' => $attempts,
                                    'max_attempts' => $maxAttempts,
                                    'retry_after' => $retryAfter
                                ]);
                                
                                sleep($retryAfter);
                                continue;
                            }
                        }

                        // Se não pode mais tentar ou tempo de espera muito longo, lança exceção
                        throw $rateLimitException;
                    }

                    // Retry apenas para códigos específicos (503) e se ainda há tentativas
                    if (in_array($statusCode, [503]) && $attempts < $maxAttempts) {
                        $delay = (int) (1000000 * pow(2, $attempts - 1));
                        usleep($delay);
                        continue;
                    }

                    // Para outros códigos de erro, lança exceção específica imediatamente
                    throw $this->createApiException($e->getRequest(), $response, $e);
                }

                // Erro sem resposta (rede)
                if ($attempts >= $maxAttempts) {
                    throw new NetworkException(
                        'Erro de rede: Falha após ' . $this->config->getRetryAttempts() . ' tentativas: ' . $e->getMessage(),
                        $e->getRequest(),
                        null,
                        $e
                    );
                }

                $delay = (int) (1000000 * pow(2, $attempts - 1));
                usleep($delay);
            }
        }

        // Nunca deve chegar aqui, mas é necessário para o PHPStan
        throw new NetworkException('Erro inesperado na execução da requisição', null, null, null);
    }

    /**
     * Executa requisição GET
     *
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return ResponseInterface
     * @throws ApiException Em caso de erro da API
     * @throws NetworkException Em caso de erro de rede
     * @throws ValidationException Em caso de erro de validação
     * @throws RateLimitException Em caso de rate limiting
     */
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->executeRequest('GET', $uri, $options);
    }

    /**
     * Executa requisição POST
     *
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return ResponseInterface
     * @throws ApiException Em caso de erro da API
     * @throws NetworkException Em caso de erro de rede
     * @throws ValidationException Em caso de erro de validação
     * @throws RateLimitException Em caso de rate limiting
     */
    public function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->executeRequest('POST', $uri, $options);
    }

    /**
     * Executa requisição PUT
     *
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return ResponseInterface
     * @throws ApiException Em caso de erro da API
     * @throws NetworkException Em caso de erro de rede
     * @throws ValidationException Em caso de erro de validação
     * @throws RateLimitException Em caso de rate limiting
     */
    public function put(string $uri, array $options = []): ResponseInterface
    {
        return $this->executeRequest('PUT', $uri, $options);
    }

    /**
     * Executa requisição DELETE
     *
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return ResponseInterface
     * @throws ApiException Em caso de erro da API
     * @throws NetworkException Em caso de erro de rede
     * @throws ValidationException Em caso de erro de validação
     * @throws RateLimitException Em caso de rate limiting
     */
    public function delete(string $uri, array $options = []): ResponseInterface
    {
        return $this->executeRequest('DELETE', $uri, $options);
    }

    /**
     * Executa requisição PATCH
     *
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return ResponseInterface
     * @throws ApiException Em caso de erro da API
     * @throws NetworkException Em caso de erro de rede
     * @throws ValidationException Em caso de erro de validação
     * @throws RateLimitException Em caso de rate limiting
     */
    public function patch(string $uri, array $options = []): ResponseInterface
    {
        return $this->executeRequest('PATCH', $uri, $options);
    }

    /**
     * Executa requisição personalizada
     *
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return ResponseInterface
     * @throws ApiException Em caso de erro da API
     * @throws NetworkException Em caso de erro de rede
     * @throws ValidationException Em caso de erro de validação
     * @throws RateLimitException Em caso de rate limiting
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        return $this->executeRequest($method, $uri, $options);
    }

    /**
     * Executa requisição assíncrona
     *
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     *
     * @return PromiseInterface
     */
    public function requestAsync(string $method, string $uri, array $options = []): PromiseInterface
    {
        return $this->client->requestAsync($method, $uri, $options);
    }

    /**
     * Obtém o cliente Guzzle subjacente (para casos avançados)
     *
     * @return Client
     */
    public function getGuzzleClient(): Client
    {
        return $this->client;
    }

    /**
     * Obtém a configuração atual
     *
     * @return ConfigurationManager
     */
    public function getConfig(): ConfigurationManager
    {
        return $this->config;
    }

    /**
     * Obtém o logger atual
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
