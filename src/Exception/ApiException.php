<?php

declare(strict_types=1);

namespace XGate\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exceção para erros relacionados à API da XGATE
 *
 * Esta exceção é lançada quando a API retorna códigos de erro HTTP (4xx, 5xx)
 * ou quando há problemas específicos com a resposta da API.
 *
 * @package XGate\Exception
 * @author XGate PHP SDK Contributors
 * @version 1.0.0
 */
class ApiException extends XGateException
{
    /**
     * Requisição HTTP que causou o erro
     *
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * Resposta HTTP recebida (se houver)
     *
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $response;

    /**
     * Código de status HTTP da resposta
     *
     * @var int
     */
    private int $statusCode;

    /**
     * Corpo da resposta de erro
     *
     * @var string
     */
    private string $responseBody;

    /**
     * Dados decodificados da resposta de erro (se for JSON)
     *
     * @var array<string, mixed>|null
     */
    private ?array $errorData;

    /**
     * Construtor da ApiException
     *
     * Suporta múltiplas assinaturas para compatibilidade:
     * 1. ApiException() - construtor vazio
     * 2. ApiException(string $message, int $statusCode) - mensagem + status code
     * 3. ApiException(string $message, int $statusCode, ?\Throwable $previous, string $responseBody) - completo com exceção
     * 4. ApiException(string $message, RequestInterface $request, ?ResponseInterface $response, ?\Throwable $previous) - com objetos HTTP
     *
     * @param string $message Mensagem de erro
     * @param RequestInterface|int|null $requestOrStatusCode Requisição ou código de status
     * @param ResponseInterface|\Throwable|null $responseOrPrevious Resposta ou exceção anterior
     * @param \Throwable|string|null $previousOrResponseBody Exceção anterior ou corpo da resposta
     */
    public function __construct(
        string $message = '',
        $requestOrStatusCode = null,
        $responseOrPrevious = null,
        $previousOrResponseBody = null
    ) {
        // Detecção de assinatura baseada no tipo do segundo parâmetro
        if ($requestOrStatusCode instanceof RequestInterface) {
            // Assinatura com objetos HTTP: ApiException(string, RequestInterface, ?ResponseInterface, ?\Throwable)
            $this->request = $requestOrStatusCode;
            $this->response = $responseOrPrevious instanceof ResponseInterface ? $responseOrPrevious : null;
            $this->statusCode = $this->response ? $this->response->getStatusCode() : 0;
            $this->responseBody = $this->response ? (string) $this->response->getBody() : '';
            $previous = $previousOrResponseBody instanceof \Throwable ? $previousOrResponseBody : null;
        } elseif (is_int($requestOrStatusCode)) {
            // Assinatura com status code: ApiException(string, int, ?\Throwable, string)
            $this->request = $this->createDummyRequest();
            $this->response = null;
            $this->statusCode = $requestOrStatusCode;

            // Determina a exceção anterior e o corpo da resposta baseado nos tipos
            if ($responseOrPrevious instanceof \Throwable) {
                $previous = $responseOrPrevious;
                $this->responseBody = is_string($previousOrResponseBody) ? $previousOrResponseBody : '';
            } else {
                $previous = $previousOrResponseBody instanceof \Throwable ? $previousOrResponseBody : null;
                $this->responseBody = is_string($previousOrResponseBody) ? $previousOrResponseBody : '';
            }
        } else {
            // Assinatura vazia: ApiException()
            $this->request = $this->createDummyRequest();
            $this->response = null;
            $this->statusCode = 0;
            $this->responseBody = '';
            $previous = null;
        }

        $this->errorData = $this->parseErrorData();

        // Se não foi fornecida uma mensagem específica, tenta extrair da resposta
        if (empty($message) && $this->errorData) {
            $message = $this->extractErrorMessage();
        }

        parent::__construct($message, $this->statusCode, $previous);
    }

    /**
     * Obtém a requisição que causou o erro
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Obtém a resposta da API (se houver)
     *
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Obtém o código de status HTTP
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Obtém o corpo da resposta de erro
     *
     * @return string
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * Obtém os dados decodificados da resposta de erro
     *
     * @return array<string, mixed>|null
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Obtém o código de erro específico da API (se disponível)
     *
     * @return string|null
     */
    public function getApiErrorCode(): ?string
    {
        return $this->errorData['code'] ?? $this->errorData['error_code'] ?? null;
    }

    /**
     * Obtém detalhes adicionais do erro (se disponíveis)
     *
     * @return array<string, mixed>|null
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorData['details'] ?? $this->errorData['errors'] ?? null;
    }

    /**
     * Verifica se o erro é de autenticação (401)
     *
     * @return bool
     */
    public function isAuthenticationError(): bool
    {
        return $this->statusCode === 401;
    }

    /**
     * Verifica se o erro é de autorização (403)
     *
     * @return bool
     */
    public function isAuthorizationError(): bool
    {
        return $this->statusCode === 403;
    }

    /**
     * Verifica se o erro é de recurso não encontrado (404)
     *
     * @return bool
     */
    public function isNotFoundError(): bool
    {
        return $this->statusCode === 404;
    }

    /**
     * Verifica se o erro é de validação (422)
     *
     * @return bool
     */
    public function isValidationError(): bool
    {
        return $this->statusCode === 422;
    }

    /**
     * Verifica se o erro é de rate limiting (429)
     *
     * @return bool
     */
    public function isRateLimitError(): bool
    {
        return $this->statusCode === 429;
    }

    /**
     * Verifica se o erro é do cliente (4xx)
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Verifica se o erro é do servidor (5xx)
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Obtém o corpo da resposta como array (se for JSON válido)
     *
     * @return array<string, mixed>|null
     */
    public function getResponseBodyAsArray(): ?array
    {
        return $this->errorData;
    }

    /**
     * Obtém informações de retry-after se disponível (para rate limiting)
     *
     * @return int|null Segundos para retry ou null se não disponível
     */
    public function getRetryAfter(): ?int
    {
        if (!$this->response) {
            return null;
        }

        $retryAfter = $this->response->getHeaderLine('Retry-After');
        if (empty($retryAfter)) {
            return null;
        }

        // Pode ser em segundos ou uma data HTTP
        if (is_numeric($retryAfter)) {
            return (int) $retryAfter;
        }

        // Tenta parsear como data HTTP
        $timestamp = strtotime($retryAfter);
        if ($timestamp !== false) {
            return max(0, $timestamp - time());
        }

        return null;
    }

    /**
     * Cria uma requisição dummy para compatibilidade com testes
     *
     * @return RequestInterface
     */
    private function createDummyRequest(): RequestInterface
    {
        return new \GuzzleHttp\Psr7\Request('GET', 'http://dummy.local');
    }

    /**
     * Faz parse dos dados de erro da resposta
     *
     * @return array<string, mixed>|null
     */
    private function parseErrorData(): ?array
    {
        if (empty($this->responseBody)) {
            return null;
        }

        $data = json_decode($this->responseBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        return null;
    }

    /**
     * Extrai mensagem de erro dos dados da resposta
     *
     * @return string
     */
    private function extractErrorMessage(): string
    {
        if (!$this->errorData) {
            return "Erro da API (HTTP {$this->statusCode})";
        }

        // Tenta diferentes campos comuns para mensagens de erro
        $possibleFields = ['message', 'error', 'error_message', 'detail', 'title'];

        foreach ($possibleFields as $field) {
            if (isset($this->errorData[$field]) && is_string($this->errorData[$field])) {
                return $this->errorData[$field];
            }
        }

        return "Erro da API (HTTP {$this->statusCode})";
    }

    /**
     * Converte a exceção para array para logging/debugging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'api_error',
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'api_error_code' => $this->getApiErrorCode(),
            'request' => [
                'method' => $this->request->getMethod(),
                'uri' => (string) $this->request->getUri(),
                'headers' => $this->request->getHeaders(),
            ],
            'response' => [
                'body' => $this->responseBody,
                'headers' => $this->response ? $this->response->getHeaders() : [],
            ],
            'error_data' => $this->errorData,
        ];
    }

    /**
     * Representação string da exceção
     *
     * @return string
     */
    public function __toString(): string
    {
        $method = $this->request->getMethod();
        $uri = (string) $this->request->getUri();
        $status = $this->statusCode;
        $message = $this->getMessage();

        // Inclui o status code no formato esperado pelos testes [404]
        $statusInfo = $status > 0 ? " [{$status}]" : '';
        $result = "ApiException: {$message} [{$method} {$uri}]{$statusInfo}";

        // Adiciona informações da resposta se disponível
        if (!empty($this->responseBody)) {
            $result .= "\nResponse: {$this->responseBody}";
        }

        // Adiciona stack trace como nas exceções padrão do PHP
        $result .= "\nStack trace:\n" . $this->getTraceAsString();

        return $result;
    }

    /**
     * Define a resposta HTTP (para uso interno)
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
        $this->responseBody = (string) $response->getBody();
        $this->errorData = $this->parseErrorData();
        
        // Extrai informações de rate limiting se aplicável
        if (method_exists($this, 'extractRateLimitInfo')) {
            $this->extractRateLimitInfo();
        }
    }
}
