<?php

declare(strict_types=1);

namespace XGate\Exception;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Centralized error message handler for consistent error formatting and logging
 *
 * This class provides standardized error message formatting, localization support,
 * severity-based logging, and security-focused error sanitization across the SDK.
 * It ensures all error messages are user-friendly, properly formatted, and logged
 * with appropriate context information.
 *
 * @package XGate\Exception
 * @author XGate PHP SDK Contributors
 * @version 1.0.0
 *
 * @example
 * ```php
 * $handler = new ErrorMessageHandler($logger, 'pt');
 * 
 * // Format validation error
 * $message = $handler->formatValidationError('email', 'required');
 * 
 * // Log exception with context
 * $handler->logException($exception, ['user_id' => 123]);
 * 
 * // Get user-friendly error message
 * $userMessage = $handler->getUserFriendlyMessage($exception);
 * ```
 */
class ErrorMessageHandler
{
    /**
     * Logger instance for error logging
     *
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;

    /**
     * Current locale for error messages (pt, en)
     *
     * @var string
     */
    private string $locale;

    /**
     * Error message templates organized by locale and type
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private array $messageTemplates;

    /**
     * Sensitive field patterns that should be masked in error messages
     *
     * @var array<string>
     */
    private array $sensitivePatterns = [
        '/password/i',
        '/secret/i',
        '/token/i',
        '/key/i',
        '/auth/i',
        '/credential/i',
        '/pin/i',
        '/cvv/i',
        '/ssn/i',
        '/cpf/i',
        '/cnpj/i',
    ];

    /**
     * Error severity levels mapped to PSR-3 log levels
     *
     * @var array<string, string>
     */
    private array $severityLevels = [
        'critical' => LogLevel::CRITICAL,
        'high' => LogLevel::ERROR,
        'medium' => LogLevel::WARNING,
        'low' => LogLevel::INFO,
        'debug' => LogLevel::DEBUG,
    ];

    /**
     * Constructor
     *
     * @param LoggerInterface|null $logger Logger instance for error logging
     * @param string $locale Locale for error messages (default: 'en')
     */
    public function __construct(?LoggerInterface $logger = null, string $locale = 'en')
    {
        $this->logger = $logger;
        $this->locale = $locale;
        $this->initializeMessageTemplates();
    }

    /**
     * Format a validation error message
     *
     * @param string $field Field name that failed validation
     * @param string $rule Validation rule that failed
     * @param mixed $value Value that failed (will be masked if sensitive)
     * @param array<string, mixed> $parameters Additional parameters for message formatting
     * @return string Formatted error message
     */
    public function formatValidationError(string $field, string $rule, mixed $value = null, array $parameters = []): string
    {
        $templates = $this->messageTemplates[$this->locale]['validation'] ?? $this->messageTemplates['en']['validation'];
        $template = $templates[$rule] ?? $templates['default'];

        $maskedValue = $this->maskSensitiveValue($field, $value);
        
        return $this->interpolateMessage($template, array_merge([
            'field' => $field,
            'value' => $maskedValue,
        ], $parameters));
    }

    /**
     * Format an API error message
     *
     * @param int $statusCode HTTP status code
     * @param string $apiMessage Original API error message
     * @param string|null $errorCode API-specific error code
     * @return string Formatted error message
     */
    public function formatApiError(int $statusCode, string $apiMessage, ?string $errorCode = null): string
    {
        $templates = $this->messageTemplates[$this->locale]['api'] ?? $this->messageTemplates['en']['api'];
        
        $template = match (true) {
            $statusCode >= 500 => $templates['server_error'],
            $statusCode === 429 => $templates['rate_limit'],
            $statusCode === 422 => $templates['validation'],
            $statusCode === 401 => $templates['unauthorized'],
            $statusCode === 403 => $templates['forbidden'],
            $statusCode === 404 => $templates['not_found'],
            $statusCode >= 400 => $templates['client_error'],
            default => $templates['default'],
        };

        return $this->interpolateMessage($template, [
            'status_code' => $statusCode,
            'api_message' => $this->sanitizeMessage($apiMessage),
            'error_code' => $errorCode,
        ]);
    }

    /**
     * Format a network error message
     *
     * @param string $errorType Type of network error
     * @param string $originalMessage Original error message
     * @param string|null $suggestion Suggested resolution
     * @return string Formatted error message
     */
    public function formatNetworkError(string $errorType, string $originalMessage, ?string $suggestion = null): string
    {
        $templates = $this->messageTemplates[$this->locale]['network'] ?? $this->messageTemplates['en']['network'];
        $template = $templates[$errorType] ?? $templates['default'];

        return $this->interpolateMessage($template, [
            'original_message' => $this->sanitizeMessage($originalMessage),
            'suggestion' => $suggestion,
        ]);
    }

    /**
     * Get a user-friendly error message from any exception
     *
     * @param \Throwable $exception Exception to format
     * @param bool $includeDetails Whether to include technical details
     * @return string User-friendly error message
     */
    public function getUserFriendlyMessage(\Throwable $exception, bool $includeDetails = false): string
    {
        $message = match (true) {
            $exception instanceof ValidationException => $this->formatValidationExceptionMessage($exception),
            $exception instanceof RateLimitException => $this->formatRateLimitExceptionMessage($exception),
            $exception instanceof ApiException => $this->formatApiExceptionMessage($exception),
            $exception instanceof NetworkException => $this->formatNetworkExceptionMessage($exception),
            $exception instanceof AuthenticationException => $this->formatAuthenticationExceptionMessage($exception),
            default => $this->formatGenericExceptionMessage($exception),
        };

        if ($includeDetails && method_exists($exception, 'getContext')) {
            $context = $exception->getContext();
            if (!empty($context)) {
                $message .= $this->formatContextDetails($context);
            }
        }

        return $message;
    }

    /**
     * Log an exception with appropriate severity and context
     *
     * @param \Throwable $exception Exception to log
     * @param array<string, mixed> $additionalContext Additional context for logging
     * @param string $severity Error severity level (critical, high, medium, low, debug)
     * @return void
     */
    public function logException(\Throwable $exception, array $additionalContext = [], string $severity = 'medium'): void
    {
        if ($this->logger === null) {
            return;
        }

        $logLevel = $this->severityLevels[$severity] ?? LogLevel::ERROR;
        $context = $this->buildLogContext($exception, $additionalContext);

        $this->logger->log($logLevel, $this->getUserFriendlyMessage($exception), $context);
    }

    /**
     * Aggregate multiple errors into a single formatted message
     *
     * @param array<\Throwable> $exceptions Array of exceptions to aggregate
     * @param int $maxDisplay Maximum number of errors to display (default: 5)
     * @return string Aggregated error message
     */
    public function aggregateErrors(array $exceptions, int $maxDisplay = 5): string
    {
        if (empty($exceptions)) {
            return $this->getMessage('general', 'no_errors');
        }

        $count = count($exceptions);
        $displayed = array_slice($exceptions, 0, $maxDisplay);
        
        $messages = array_map(fn($ex) => $this->getUserFriendlyMessage($ex), $displayed);
        
        $result = $this->getMessage('general', 'multiple_errors_header', ['count' => $count]);
        $result .= "\n" . implode("\n", array_map(fn($msg, $i) => "  " . ($i + 1) . ". {$msg}", $messages, array_keys($messages)));
        
        if ($count > $maxDisplay) {
            $remaining = $count - $maxDisplay;
            $result .= "\n" . $this->getMessage('general', 'more_errors', ['count' => $remaining]);
        }

        return $result;
    }

    /**
     * Set the locale for error messages
     *
     * @param string $locale Locale code (pt, en)
     * @return self
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get the current locale
     *
     * @return string Current locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Sanitize error message for safe display
     *
     * @param string $message Message to sanitize
     * @return string Sanitized message
     */
    public function sanitizeMessage(string $message): string
    {
        // Remove potentially sensitive information
        $sanitized = preg_replace('/\b[\w\.-]+@[\w\.-]+\.\w+\b/', '[EMAIL]', $message);
        $sanitized = preg_replace('/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', '[CARD]', $sanitized);
        $sanitized = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[SSN]', $sanitized);
        $sanitized = preg_replace('/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/', '[CPF]', $sanitized);
        $sanitized = preg_replace('/\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/', '[CNPJ]', $sanitized);
        
        return $sanitized ?? $message;
    }

    /**
     * Initialize error message templates for different locales
     *
     * @return void
     */
    private function initializeMessageTemplates(): void
    {
        $this->messageTemplates = [
            'en' => [
                'validation' => [
                    'required' => "The field '{field}' is required",
                    'format' => "The field '{field}' has invalid format",
                    'type' => "The field '{field}' must be of the correct type",
                    'range' => "The field '{field}' value is out of range",
                    'pattern' => "The field '{field}' doesn't match the required pattern",
                    'email' => "The field '{field}' must be a valid email address",
                    'url' => "The field '{field}' must be a valid URL",
                    'numeric' => "The field '{field}' must be numeric",
                    'default' => "Validation failed for field '{field}'",
                ],
                'api' => [
                    'server_error' => "Server error occurred. Please try again later. (Status: {status_code})",
                    'rate_limit' => "Too many requests. Please wait before trying again. (Status: {status_code})",
                    'validation' => "Request validation failed: {api_message}",
                    'unauthorized' => "Authentication required. Please check your credentials.",
                    'forbidden' => "Access denied. You don't have permission for this operation.",
                    'not_found' => "The requested resource was not found.",
                    'client_error' => "Request error: {api_message} (Status: {status_code})",
                    'default' => "API error: {api_message} (Status: {status_code})",
                ],
                'network' => [
                    'connection_timeout' => "Connection timeout. Please check your internet connection.",
                    'read_timeout' => "Request timeout. The server took too long to respond.",
                    'connection_refused' => "Connection refused. The service may be unavailable.",
                    'dns_resolution' => "DNS resolution failed. Please check the server address.",
                    'ssl_certificate' => "SSL certificate error. Please verify the certificate.",
                    'ssl_handshake' => "SSL handshake failed. Please check security settings.",
                    'network_unreachable' => "Network unreachable. Please check your connection.",
                    'host_unreachable' => "Host unreachable. Please verify the server address.",
                    'default' => "Network error: {original_message}",
                ],
                'general' => [
                    'no_errors' => "No errors found",
                    'multiple_errors_header' => "Found {count} error(s):",
                    'more_errors' => "... and {count} more error(s)",
                    'context_details' => "Additional details: {details}",
                ],
            ],
            'pt' => [
                'validation' => [
                    'required' => "O campo '{field}' é obrigatório",
                    'format' => "O campo '{field}' possui formato inválido",
                    'type' => "O campo '{field}' deve ser do tipo correto",
                    'range' => "O valor do campo '{field}' está fora do intervalo permitido",
                    'pattern' => "O campo '{field}' não atende ao padrão exigido",
                    'email' => "O campo '{field}' deve ser um endereço de email válido",
                    'url' => "O campo '{field}' deve ser uma URL válida",
                    'numeric' => "O campo '{field}' deve ser numérico",
                    'default' => "Falha na validação do campo '{field}'",
                ],
                'api' => [
                    'server_error' => "Erro no servidor. Tente novamente mais tarde. (Status: {status_code})",
                    'rate_limit' => "Muitas requisições. Aguarde antes de tentar novamente. (Status: {status_code})",
                    'validation' => "Falha na validação da requisição: {api_message}",
                    'unauthorized' => "Autenticação necessária. Verifique suas credenciais.",
                    'forbidden' => "Acesso negado. Você não tem permissão para esta operação.",
                    'not_found' => "O recurso solicitado não foi encontrado.",
                    'client_error' => "Erro na requisição: {api_message} (Status: {status_code})",
                    'default' => "Erro da API: {api_message} (Status: {status_code})",
                ],
                'network' => [
                    'connection_timeout' => "Timeout de conexão. Verifique sua conexão com a internet.",
                    'read_timeout' => "Timeout da requisição. O servidor demorou muito para responder.",
                    'connection_refused' => "Conexão recusada. O serviço pode estar indisponível.",
                    'dns_resolution' => "Falha na resolução DNS. Verifique o endereço do servidor.",
                    'ssl_certificate' => "Erro no certificado SSL. Verifique o certificado.",
                    'ssl_handshake' => "Falha no handshake SSL. Verifique as configurações de segurança.",
                    'network_unreachable' => "Rede inalcançável. Verifique sua conexão.",
                    'host_unreachable' => "Host inalcançável. Verifique o endereço do servidor.",
                    'default' => "Erro de rede: {original_message}",
                ],
                'general' => [
                    'no_errors' => "Nenhum erro encontrado",
                    'multiple_errors_header' => "Encontrado(s) {count} erro(s):",
                    'more_errors' => "... e mais {count} erro(s)",
                    'context_details' => "Detalhes adicionais: {details}",
                ],
            ],
        ];
    }

    /**
     * Get a message template by category and key
     *
     * @param string $category Message category
     * @param string $key Message key
     * @param array<string, mixed> $parameters Parameters for interpolation
     * @return string Formatted message
     */
    private function getMessage(string $category, string $key, array $parameters = []): string
    {
        $templates = $this->messageTemplates[$this->locale][$category] ?? $this->messageTemplates['en'][$category] ?? [];
        $template = $templates[$key] ?? $key;
        
        return $this->interpolateMessage($template, $parameters);
    }

    /**
     * Interpolate message template with parameters
     *
     * @param string $template Message template
     * @param array<string, mixed> $parameters Parameters to interpolate
     * @return string Interpolated message
     */
    private function interpolateMessage(string $template, array $parameters): string
    {
        $replacements = [];
        foreach ($parameters as $key => $value) {
            $replacements['{' . $key . '}'] = (string) $value;
        }
        
        return strtr($template, $replacements);
    }

    /**
     * Mask sensitive values based on field name patterns
     *
     * @param string $fieldName Field name to check for sensitivity
     * @param mixed $value Value to potentially mask
     * @return mixed Original value or masked string
     */
    private function maskSensitiveValue(string $fieldName, mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        foreach ($this->sensitivePatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return str_repeat('*', min(strlen($value), 8));
            }
        }

        return $value;
    }

    /**
     * Format ValidationException message
     *
     * @param ValidationException $exception
     * @return string Formatted message
     */
    private function formatValidationExceptionMessage(ValidationException $exception): string
    {
        $errors = $exception->getValidationErrors();
        
        if (empty($errors)) {
            return $exception->getMessage();
        }

        if (count($errors) === 1 && count(reset($errors)) === 1) {
            $field = array_key_first($errors);
            $error = reset($errors)[0];
            return $this->getMessage('validation', 'default', ['field' => $field]) . ": {$error}";
        }

        return $exception->getMessage();
    }

    /**
     * Format RateLimitException message
     *
     * @param RateLimitException $exception
     * @return string Formatted message
     */
    private function formatRateLimitExceptionMessage(RateLimitException $exception): string
    {
        $retryAfter = $exception->getRetryAfter();
        $template = $this->getMessage('api', 'rate_limit', ['status_code' => 429]);
        
        if ($retryAfter > 0) {
            $template .= " " . ($this->locale === 'pt' 
                ? "Tente novamente em {$retryAfter} segundos."
                : "Try again in {$retryAfter} seconds.");
        }
        
        return $template;
    }

    /**
     * Format ApiException message
     *
     * @param ApiException $exception
     * @return string Formatted message
     */
    private function formatApiExceptionMessage(ApiException $exception): string
    {
        return $this->formatApiError(
            $exception->getStatusCode(),
            $exception->getMessage(),
            $exception->getApiErrorCode()
        );
    }

    /**
     * Format NetworkException message
     *
     * @param NetworkException $exception
     * @return string Formatted message
     */
    private function formatNetworkExceptionMessage(NetworkException $exception): string
    {
        return $this->formatNetworkError(
            $exception->getErrorType(),
            $exception->getMessage(),
            $exception->getSuggestion()
        );
    }

    /**
     * Format AuthenticationException message
     *
     * @param AuthenticationException $exception
     * @return string Formatted message
     */
    private function formatAuthenticationExceptionMessage(AuthenticationException $exception): string
    {
        return $this->getMessage('api', 'unauthorized');
    }

    /**
     * Format generic exception message
     *
     * @param \Throwable $exception
     * @return string Formatted message
     */
    private function formatGenericExceptionMessage(\Throwable $exception): string
    {
        return $this->sanitizeMessage($exception->getMessage());
    }

    /**
     * Format context details for display
     *
     * @param array<string, mixed> $context
     * @return string Formatted context details
     */
    private function formatContextDetails(array $context): string
    {
        $details = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $details[] = "{$key}: " . $this->sanitizeMessage((string) $value);
            }
        }
        
        if (empty($details)) {
            return '';
        }
        
        return "\n" . $this->getMessage('general', 'context_details', [
            'details' => implode(', ', $details)
        ]);
    }

    /**
     * Build comprehensive log context from exception and additional data
     *
     * @param \Throwable $exception
     * @param array<string, mixed> $additionalContext
     * @return array<string, mixed>
     */
    private function buildLogContext(\Throwable $exception, array $additionalContext): array
    {
        $context = [
            'exception_class' => get_class($exception),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'locale' => $this->locale,
        ];

        // Add exception-specific context
        if (method_exists($exception, 'getContext')) {
            $context['exception_context'] = $exception->getContext();
        }

        if (method_exists($exception, 'toArray')) {
            $context['exception_data'] = $exception->toArray();
        }

        // Add additional context
        $context = array_merge($context, $additionalContext);

        // Sanitize sensitive data in context
        return $this->sanitizeContext($context);
    }

    /**
     * Sanitize context data to remove sensitive information
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->isSensitiveKey($key) 
                    ? $this->maskSensitiveValue($key, $value)
                    : $this->sanitizeMessage($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Check if a key represents sensitive data
     *
     * @param string $key
     * @return bool
     */
    private function isSensitiveKey(string $key): bool
    {
        foreach ($this->sensitivePatterns as $pattern) {
            if (preg_match($pattern, $key)) {
                return true;
            }
        }
        
        return false;
    }
} 