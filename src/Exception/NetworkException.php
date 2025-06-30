<?php

declare(strict_types=1);

namespace XGate\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exceção para erros de rede e conectividade
 *
 * Esta exceção é lançada quando há problemas de conectividade, timeouts,
 * falhas de DNS, problemas de SSL/TLS ou outros erros relacionados à rede.
 *
 * @package XGate\Exception
 * @author XGate PHP SDK Contributors
 * @version 1.0.0
 */
class NetworkException extends XGateException
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
     * Tipo de erro de rede
     *
     * @var string
     */
    private string $errorType;

    /**
     * Tipos de erro de rede conhecidos
     */
    public const ERROR_CONNECTION_TIMEOUT = 'connection_timeout';
    public const ERROR_READ_TIMEOUT = 'read_timeout';
    public const ERROR_CONNECTION_REFUSED = 'connection_refused';
    public const ERROR_DNS_RESOLUTION = 'dns_resolution';
    public const ERROR_SSL_CERTIFICATE = 'ssl_certificate';
    public const ERROR_SSL_HANDSHAKE = 'ssl_handshake';
    public const ERROR_NETWORK_UNREACHABLE = 'network_unreachable';
    public const ERROR_HOST_UNREACHABLE = 'host_unreachable';
    public const ERROR_UNKNOWN = 'unknown';

    /**
     * Construtor da NetworkException
     *
     * Suporta múltiplas assinaturas para compatibilidade:
     * 1. NetworkException() - construtor vazio
     * 2. NetworkException(string $message) - apenas mensagem
     * 3. NetworkException(string $message, int $code) - mensagem + código
     * 4. NetworkException(string $message, int $code, ?\Throwable $previous) - mensagem + código + exceção anterior
     * 5. NetworkException(string $message, RequestInterface $request, ?ResponseInterface $response, ?\Throwable $previous) - completo
     *
     * @param string $message Mensagem de erro
     * @param RequestInterface|int|null $requestOrCode Requisição ou código de erro
     * @param ResponseInterface|\Throwable|null $responseOrPrevious Resposta ou exceção anterior
     * @param \Throwable|null $previous Exceção anterior
     */
    public function __construct(
        string $message = '',
        $requestOrCode = null,
        $responseOrPrevious = null,
        ?\Throwable $previous = null
    ) {
        // Detecção de assinatura baseada nos tipos dos parâmetros
        if ($requestOrCode instanceof RequestInterface) {
            // Assinatura completa: NetworkException(string, RequestInterface, ?ResponseInterface, ?\Throwable)
            $this->request = $requestOrCode;
            $this->response = $responseOrPrevious instanceof ResponseInterface ? $responseOrPrevious : null;
            $code = 0;
            $actualPrevious = $previous;
        } elseif (is_int($requestOrCode)) {
            // Assinatura com código: NetworkException(string, int, ?\Throwable)
            $this->request = $this->createDummyRequest();
            $this->response = null;
            $code = $requestOrCode;
            $actualPrevious = $responseOrPrevious instanceof \Throwable ? $responseOrPrevious : null;
        } else {
            // Assinatura simples: NetworkException() ou NetworkException(string)
            $this->request = $this->createDummyRequest();
            $this->response = null;
            $code = 0;
            $actualPrevious = null;
        }

        // Determina o tipo de erro baseado na mensagem
        $this->errorType = $this->determineErrorType($message, $actualPrevious);

        // Aprimora a mensagem se temos uma requisição real (não dummy)
        if ($this->request->getUri()->getHost() !== 'dummy.local') {
            $message = $this->enhanceMessage($message);
        }

        parent::__construct($message, $code, $actualPrevious);
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
     * Obtém o tipo de erro de rede
     *
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Verifica se o erro é de timeout de conexão
     *
     * @return bool
     */
    public function isConnectionTimeoutError(): bool
    {
        return $this->errorType === self::ERROR_CONNECTION_TIMEOUT;
    }

    /**
     * Verifica se o erro é de timeout de leitura
     *
     * @return bool
     */
    public function isReadTimeoutError(): bool
    {
        return $this->errorType === self::ERROR_READ_TIMEOUT;
    }

    /**
     * Verifica se é qualquer tipo de timeout
     *
     * @return bool
     */
    public function isTimeoutError(): bool
    {
        return in_array($this->errorType, [
            self::ERROR_CONNECTION_TIMEOUT,
            self::ERROR_READ_TIMEOUT,
        ]);
    }

    /**
     * Verifica se o erro é de conexão recusada
     *
     * @return bool
     */
    public function isConnectionRefusedError(): bool
    {
        return $this->errorType === self::ERROR_CONNECTION_REFUSED;
    }

    /**
     * Verifica se o erro é de resolução DNS
     *
     * @return bool
     */
    public function isDnsError(): bool
    {
        return $this->errorType === self::ERROR_DNS_RESOLUTION;
    }

    /**
     * Verifica se o erro é relacionado a SSL/TLS
     *
     * @return bool
     */
    public function isSslError(): bool
    {
        return in_array($this->errorType, [
            self::ERROR_SSL_CERTIFICATE,
            self::ERROR_SSL_HANDSHAKE,
        ]);
    }

    /**
     * Verifica se o erro é de rede inalcançável
     *
     * @return bool
     */
    public function isNetworkUnreachableError(): bool
    {
        return in_array($this->errorType, [
            self::ERROR_NETWORK_UNREACHABLE,
            self::ERROR_HOST_UNREACHABLE,
        ]);
    }

    /**
     * Verifica se o erro pode ser resolvido com retry
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        return in_array($this->errorType, [
            self::ERROR_CONNECTION_TIMEOUT,
            self::ERROR_READ_TIMEOUT,
            self::ERROR_CONNECTION_REFUSED,
            self::ERROR_DNS_RESOLUTION,
            self::ERROR_NETWORK_UNREACHABLE,
            self::ERROR_HOST_UNREACHABLE,
        ]);
    }

    /**
     * Obtém sugestão de resolução baseada no tipo de erro
     *
     * @return string
     */
    public function getSuggestion(): string
    {
        switch ($this->errorType) {
            case self::ERROR_CONNECTION_TIMEOUT:
                return 'Verifique sua conexão com a internet e tente aumentar o timeout de conexão.';

            case self::ERROR_READ_TIMEOUT:
                return 'A resposta demorou muito para chegar. Tente aumentar o timeout de leitura.';

            case self::ERROR_CONNECTION_REFUSED:
                return 'O servidor está indisponível. Verifique se o serviço está ativo e a URL está correta.';

            case self::ERROR_DNS_RESOLUTION:
                return 'Não foi possível resolver o nome do servidor. Verifique a URL e sua conexão DNS.';

            case self::ERROR_SSL_CERTIFICATE:
                return 'Problema com o certificado SSL. Verifique se o certificado é válido e confiável.';

            case self::ERROR_SSL_HANDSHAKE:
                return 'Falha no handshake SSL/TLS. Verifique as configurações de segurança.';

            case self::ERROR_NETWORK_UNREACHABLE:
            case self::ERROR_HOST_UNREACHABLE:
                return 'Rede ou host inalcançável. Verifique sua conectividade e configurações de firewall.';

            default:
                return 'Erro de rede desconhecido. Verifique sua conexão com a internet e tente novamente.';
        }
    }

    /**
     * Obtém tempo recomendado para retry (em segundos)
     *
     * @return int
     */
    public function getRecommendedRetryDelay(): int
    {
        switch ($this->errorType) {
            case self::ERROR_CONNECTION_TIMEOUT:
            case self::ERROR_READ_TIMEOUT:
                return 5; // 5 segundos para timeouts

            case self::ERROR_NETWORK_UNREACHABLE:
            case self::ERROR_HOST_UNREACHABLE:
                return 10; // 10 segundos para problemas de alcançabilidade

            case self::ERROR_CONNECTION_REFUSED:
                return 30; // 30 segundos para conexão recusada

            default:
                return 15; // 15 segundos por padrão
        }
    }

    /**
     * Determina o tipo de erro baseado na mensagem e exceção anterior
     *
     * @param string $message
     * @param \Throwable|null $previous
     * @return string
     */
    private function determineErrorType(string $message, ?\Throwable $previous): string
    {
        $lowerMessage = strtolower($message);

        // Verifica padrões na mensagem de erro (ordem importa - mais específicos primeiro)
        if (strpos($lowerMessage, 'connection timeout') !== false ||
            strpos($lowerMessage, 'connection timed out') !== false ||
            strpos($lowerMessage, 'request timed out') !== false ||
            strpos($lowerMessage, 'timed out') !== false) {
            return self::ERROR_CONNECTION_TIMEOUT;
        }

        if (strpos($lowerMessage, 'read timeout') !== false) {
            return self::ERROR_READ_TIMEOUT;
        }

        // Verificação genérica de timeout por último
        if (strpos($lowerMessage, 'timeout') !== false) {
            return self::ERROR_READ_TIMEOUT;
        }

        if (strpos($lowerMessage, 'connection refused') !== false ||
            strpos($lowerMessage, 'connection denied') !== false) {
            return self::ERROR_CONNECTION_REFUSED;
        }

        if (strpos($lowerMessage, 'could not resolve host') !== false ||
            strpos($lowerMessage, 'name resolution') !== false ||
            strpos($lowerMessage, 'dns resolution') !== false ||
            strpos($lowerMessage, 'dns') !== false) {
            return self::ERROR_DNS_RESOLUTION;
        }

        if (strpos($lowerMessage, 'ssl certificate') !== false ||
            strpos($lowerMessage, 'certificate verify failed') !== false ||
            strpos($lowerMessage, 'certificate verification failed') !== false) {
            return self::ERROR_SSL_CERTIFICATE;
        }

        if (strpos($lowerMessage, 'ssl handshake') !== false ||
            strpos($lowerMessage, 'tls handshake') !== false ||
            strpos($lowerMessage, 'ssl connect error') !== false) {
            return self::ERROR_SSL_HANDSHAKE;
        }

        if (strpos($lowerMessage, 'network unreachable') !== false) {
            return self::ERROR_NETWORK_UNREACHABLE;
        }

        if (strpos($lowerMessage, 'host unreachable') !== false) {
            return self::ERROR_HOST_UNREACHABLE;
        }

        // Verifica o tipo da exceção anterior
        if ($previous) {
            $previousClass = get_class($previous);
            $previousMessage = strtolower($previous->getMessage());

            if (strpos($previousClass, 'ConnectException') !== false) {
                if (strpos($previousMessage, 'timeout') !== false) {
                    return self::ERROR_CONNECTION_TIMEOUT;
                }

                return self::ERROR_CONNECTION_REFUSED;
            }
        }

        return self::ERROR_UNKNOWN;
    }

    /**
     * Aprimora a mensagem de erro com informações contextuais
     *
     * @param string $message
     * @return string
     */
    private function enhanceMessage(string $message): string
    {
        $uri = (string) $this->request->getUri();
        $method = $this->request->getMethod();

        $enhancedMessage = $message;

        // Adiciona informações da requisição se não estiverem na mensagem
        if (strpos($message, $uri) === false) {
            $enhancedMessage .= " [{$method} {$uri}]";
        }

        return $enhancedMessage;
    }

    /**
     * Converte a exceção para array para logging/debugging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'network_error',
            'error_type' => $this->errorType,
            'message' => $this->getMessage(),
            'is_retryable' => $this->isRetryable(),
            'suggestion' => $this->getSuggestion(),
            'recommended_retry_delay' => $this->getRecommendedRetryDelay(),
            'request' => [
                'method' => $this->request->getMethod(),
                'uri' => (string) $this->request->getUri(),
                'headers' => $this->request->getHeaders(),
            ],
            'response' => $this->response ? [
                'status_code' => $this->response->getStatusCode(),
                'headers' => $this->response->getHeaders(),
            ] : null,
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
        $message = $this->getMessage();
        $code = $this->getCode();

        // Inclui o código se for maior que 0
        $codeInfo = $code > 0 ? " [{$code}]" : '';
        $result = "NetworkException: {$message}{$codeInfo} [{$method} {$uri}] (Type: {$this->errorType})";

        // Adiciona sugestão
        $result .= "\nSugestão: " . $this->getSuggestion();

        // Adiciona stack trace como nas exceções padrão do PHP
        $result .= "\nStack trace:\n" . $this->getTraceAsString();

        return $result;
    }
}
