<?php

declare(strict_types=1);

namespace XGate\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exceção para erros de limite de taxa (rate limiting)
 *
 * Esta exceção é lançada quando a API retorna erro HTTP 429 (Too Many Requests)
 * ou quando limites de taxa são excedidos. Inclui informações sobre quando
 * tentar novamente e detalhes sobre os limites aplicados.
 *
 * @package XGate\Exception
 * @author XGate PHP SDK Contributors
 * @version 1.0.0
 *
 * @example
 * ```php
 * try {
 *     $response = $client->post('/api/transactions', $data);
 * } catch (RateLimitException $e) {
 *     // Aguarda antes de tentar novamente
 *     $retryAfter = $e->getRetryAfter();
 *     if ($retryAfter) {
 *         sleep($retryAfter);
 *         // Tenta novamente...
 *     }
 * }
 * ```
 */
class RateLimitException extends ApiException
{
    /**
     * Tempo em segundos para aguardar antes de tentar novamente
     *
     * @var int|null
     */
    private ?int $retryAfter = null;

    /**
     * Limite de requisições por período
     *
     * @var int|null
     */
    private ?int $rateLimit = null;

    /**
     * Número de requisições restantes no período atual
     *
     * @var int|null
     */
    private ?int $rateLimitRemaining = null;

    /**
     * Timestamp quando o limite será resetado
     *
     * @var int|null
     */
    private ?int $rateLimitReset = null;

    /**
     * Tipo de limite aplicado (por minuto, hora, dia, etc.)
     *
     * @var string|null
     */
    private ?string $limitType = null;

    /**
     * Identificador do cliente/usuário que atingiu o limite
     *
     * @var string|null
     */
    private ?string $clientId = null;

    /**
     * Construtor da RateLimitException
     *
     * Suporta múltiplas assinaturas:
     * 1. RateLimitException(string $message, int $retryAfter) - básico
     * 2. RateLimitException(string $message, RequestInterface $request, ResponseInterface $response) - com HTTP
     * 3. RateLimitException(string $message, array $rateLimitInfo) - com informações detalhadas
     *
     * @param string $message Mensagem de erro
     * @param RequestInterface|int|array|null $requestOrRetryOrInfo Requisição, retry-after ou informações
     * @param ResponseInterface|\Throwable|null $responseOrPrevious Resposta ou exceção anterior
     * @param \Throwable|null $previous Exceção anterior
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        $requestOrRetryOrInfo = null,
        $responseOrPrevious = null,
        ?\Throwable $previous = null
    ) {
        // Detecção de assinatura baseada no tipo do segundo parâmetro
        if ($requestOrRetryOrInfo instanceof RequestInterface) {
            // Assinatura com objetos HTTP
            parent::__construct($message, $requestOrRetryOrInfo, $responseOrPrevious, $previous);
            $this->extractRateLimitInfo();
        } elseif (is_int($requestOrRetryOrInfo)) {
            // Assinatura básica com retry-after
            $this->retryAfter = $requestOrRetryOrInfo;
            parent::__construct($message, 429, $responseOrPrevious, '');
        } elseif (is_array($requestOrRetryOrInfo)) {
            // Assinatura com informações detalhadas
            $this->setRateLimitInfo($requestOrRetryOrInfo);
            parent::__construct($message, 429, $responseOrPrevious, '');
        } else {
            // Assinatura padrão
            parent::__construct($message, 429, $responseOrPrevious, '');
        }

        // Se não foi fornecida mensagem específica, gera uma informativa
        if ($message === 'Rate limit exceeded') {
            $this->message = $this->generateInformativeMessage();
        }

        // Adiciona contexto específico de rate limiting
        $this->addContext('rate_limit_info', [
            'retry_after' => $this->retryAfter,
            'rate_limit' => $this->rateLimit,
            'rate_limit_remaining' => $this->rateLimitRemaining,
            'rate_limit_reset' => $this->rateLimitReset,
            'limit_type' => $this->limitType,
            'client_id' => $this->clientId,
        ]);
    }

    /**
     * Obtém o tempo em segundos para aguardar antes de tentar novamente
     *
     * @return int|null
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Obtém o limite de requisições por período
     *
     * @return int|null
     */
    public function getRateLimit(): ?int
    {
        return $this->rateLimit;
    }

    /**
     * Obtém o número de requisições restantes no período atual
     *
     * @return int|null
     */
    public function getRateLimitRemaining(): ?int
    {
        return $this->rateLimitRemaining;
    }

    /**
     * Obtém o timestamp quando o limite será resetado
     *
     * @return int|null
     */
    public function getRateLimitReset(): ?int
    {
        return $this->rateLimitReset;
    }

    /**
     * Obtém o timestamp quando o limite será resetado como DateTime
     *
     * @return \DateTimeImmutable|null
     */
    public function getRateLimitResetDateTime(): ?\DateTimeImmutable
    {
        if ($this->rateLimitReset === null) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', (string) $this->rateLimitReset) ?: null;
    }

    /**
     * Obtém o tipo de limite aplicado
     *
     * @return string|null
     */
    public function getLimitType(): ?string
    {
        return $this->limitType;
    }

    /**
     * Obtém o identificador do cliente que atingiu o limite
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    /**
     * Verifica se há informação de quando tentar novamente
     *
     * @return bool
     */
    public function hasRetryAfter(): bool
    {
        return $this->retryAfter !== null && $this->retryAfter > 0;
    }

    /**
     * Verifica se o limite já foi resetado
     *
     * @return bool
     */
    public function isLimitReset(): bool
    {
        if ($this->rateLimitReset === null) {
            return false;
        }

        return time() >= $this->rateLimitReset;
    }

    /**
     * Calcula quantos segundos faltam para o reset do limite
     *
     * @return int Segundos até o reset (0 se já resetou ou sem informação)
     */
    public function getSecondsUntilReset(): int
    {
        if ($this->rateLimitReset === null) {
            return 0;
        }

        $secondsLeft = $this->rateLimitReset - time();
        return max(0, $secondsLeft);
    }

    /**
     * Verifica se todas as requisições foram consumidas
     *
     * @return bool
     */
    public function isFullyExhausted(): bool
    {
        return $this->rateLimitRemaining === 0;
    }

    /**
     * Obtém a porcentagem de limite utilizada
     *
     * @return float|null Porcentagem (0.0 a 100.0) ou null se sem informação
     */
    public function getLimitUsagePercentage(): ?float
    {
        if ($this->rateLimit === null || $this->rateLimitRemaining === null) {
            return null;
        }

        if ($this->rateLimit === 0) {
            return 100.0;
        }

        $used = $this->rateLimit - $this->rateLimitRemaining;
        return ($used / $this->rateLimit) * 100.0;
    }

    /**
     * Define informações de rate limiting a partir de array
     *
     * @param array<string, mixed> $info Informações de rate limiting
     * @return static
     */
    public function setRateLimitInfo(array $info): static
    {
        $this->retryAfter = isset($info['retry_after']) ? (int) $info['retry_after'] : null;
        $this->rateLimit = isset($info['rate_limit']) ? (int) $info['rate_limit'] : null;
        $this->rateLimitRemaining = isset($info['rate_limit_remaining']) ? (int) $info['rate_limit_remaining'] : null;
        $this->rateLimitReset = isset($info['rate_limit_reset']) ? (int) $info['rate_limit_reset'] : null;
        $this->limitType = $info['limit_type'] ?? null;
        $this->clientId = $info['client_id'] ?? null;

        return $this;
    }

    /**
     * Extrai informações de rate limiting da resposta HTTP
     */
    private function extractRateLimitInfo(): void
    {
        $response = $this->getResponse();
        if (!$response) {
            return;
        }

        // Headers padrão de rate limiting
        $headers = [
            'retry_after' => ['Retry-After', 'X-Retry-After'],
            'rate_limit' => ['X-RateLimit-Limit', 'X-Rate-Limit-Limit', 'RateLimit-Limit'],
            'rate_limit_remaining' => ['X-RateLimit-Remaining', 'X-Rate-Limit-Remaining', 'RateLimit-Remaining'],
            'rate_limit_reset' => ['X-RateLimit-Reset', 'X-Rate-Limit-Reset', 'RateLimit-Reset'],
        ];

        foreach ($headers as $property => $headerNames) {
            foreach ($headerNames as $headerName) {
                if ($response->hasHeader($headerName)) {
                    $value = $response->getHeaderLine($headerName);
                    $numericValue = is_numeric($value) ? (int) $value : null;
                    
                    switch ($property) {
                        case 'retry_after':
                            $this->retryAfter = $numericValue;
                            break;
                        case 'rate_limit':
                            $this->rateLimit = $numericValue;
                            break;
                        case 'rate_limit_remaining':
                            $this->rateLimitRemaining = $numericValue;
                            break;
                        case 'rate_limit_reset':
                            $this->rateLimitReset = $numericValue;
                            break;
                    }
                    break;
                }
            }
        }

        // Extrai tipo de limite se disponível
        $typeHeaders = ['X-RateLimit-Type', 'X-Rate-Limit-Type', 'RateLimit-Type'];
        foreach ($typeHeaders as $header) {
            if ($response->hasHeader($header)) {
                $this->limitType = $response->getHeaderLine($header);
                break;
            }
        }

        // Extrai ID do cliente se disponível
        $clientHeaders = ['X-RateLimit-Client-ID', 'X-Rate-Limit-Client-ID', 'RateLimit-Client-ID'];
        foreach ($clientHeaders as $header) {
            if ($response->hasHeader($header)) {
                $this->clientId = $response->getHeaderLine($header);
                break;
            }
        }

        // Tenta extrair informações do corpo da resposta
        $errorData = $this->getErrorData();
        if ($errorData) {
            $this->retryAfter = $this->retryAfter ?? ($errorData['retry_after'] ?? null);
            $this->rateLimit = $this->rateLimit ?? ($errorData['rate_limit'] ?? null);
            $this->rateLimitRemaining = $this->rateLimitRemaining ?? ($errorData['remaining'] ?? null);
            $this->rateLimitReset = $this->rateLimitReset ?? ($errorData['reset_time'] ?? null);
            $this->limitType = $this->limitType ?? ($errorData['limit_type'] ?? null);
        }
    }

    /**
     * Gera mensagem informativa baseada nas informações disponíveis
     *
     * @return string
     */
    private function generateInformativeMessage(): string
    {
        $message = 'Rate limit exceeded';

        if ($this->retryAfter) {
            $message .= ". Retry after {$this->retryAfter} seconds";
        } elseif ($this->rateLimitReset) {
            $secondsLeft = $this->getSecondsUntilReset();
            if ($secondsLeft > 0) {
                $message .= ". Limit resets in {$secondsLeft} seconds";
            }
        }

        if ($this->rateLimit && $this->rateLimitRemaining !== null) {
            $used = $this->rateLimit - $this->rateLimitRemaining;
            $message .= " ({$used}/{$this->rateLimit} requests used)";
        }

        if ($this->limitType) {
            $message .= " [Type: {$this->limitType}]";
        }

        return $message;
    }

    /**
     * Converte a exceção para array com informações de rate limiting
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter,
            'rate_limit' => $this->rateLimit,
            'rate_limit_remaining' => $this->rateLimitRemaining,
            'rate_limit_reset' => $this->rateLimitReset,
            'rate_limit_reset_datetime' => $this->getRateLimitResetDateTime()?->format('c'),
            'limit_type' => $this->limitType,
            'client_id' => $this->clientId,
            'seconds_until_reset' => $this->getSecondsUntilReset(),
            'is_fully_exhausted' => $this->isFullyExhausted(),
            'usage_percentage' => $this->getLimitUsagePercentage(),
        ]);
    }

    /**
     * Representação em string da exceção
     *
     * @return string
     */
    public function __toString(): string
    {
        $result = parent::__toString();
        
        $result .= "\nRate Limit Information:\n";
        
        if ($this->retryAfter) {
            $result .= "  - Retry After: {$this->retryAfter} seconds\n";
        }
        
        if ($this->rateLimit) {
            $result .= "  - Rate Limit: {$this->rateLimit} requests\n";
        }
        
        if ($this->rateLimitRemaining !== null) {
            $result .= "  - Remaining: {$this->rateLimitRemaining} requests\n";
        }
        
        if ($this->rateLimitReset) {
            $resetTime = $this->getRateLimitResetDateTime();
            $result .= "  - Reset Time: " . ($resetTime ? $resetTime->format('Y-m-d H:i:s T') : $this->rateLimitReset) . "\n";
        }
        
        if ($this->limitType) {
            $result .= "  - Limit Type: {$this->limitType}\n";
        }
        
        $usage = $this->getLimitUsagePercentage();
        if ($usage !== null) {
            $result .= "  - Usage: " . number_format($usage, 1) . "%\n";
        }
        
        return $result;
    }

    /**
     * Cria uma RateLimitException básica com retry-after
     *
     * @param int $retryAfter Segundos para aguardar
     * @param string|null $message Mensagem personalizada
     * @return static
     */
    public static function withRetryAfter(int $retryAfter, ?string $message = null): static
    {
        return new static(
            $message ?? "Rate limit exceeded. Retry after {$retryAfter} seconds",
            $retryAfter
        );
    }

    /**
     * Cria uma RateLimitException com informações completas
     *
     * @param int $limit Limite total de requisições
     * @param int $remaining Requisições restantes
     * @param int $resetTime Timestamp do reset
     * @param string|null $limitType Tipo de limite
     * @param string|null $message Mensagem personalizada
     * @return static
     */
    public static function withLimitInfo(
        int $limit,
        int $remaining,
        int $resetTime,
        ?string $limitType = null,
        ?string $message = null
    ): static {
        $info = [
            'rate_limit' => $limit,
            'rate_limit_remaining' => $remaining,
            'rate_limit_reset' => $resetTime,
            'limit_type' => $limitType,
        ];

        return new static($message ?? 'Rate limit exceeded', $info);
    }
} 