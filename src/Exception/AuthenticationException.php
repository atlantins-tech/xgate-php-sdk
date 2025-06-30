<?php

declare(strict_types=1);

namespace XGate\Exception;

/**
 * Exception thrown when authentication with XGATE API fails
 *
 * This exception is thrown when login credentials are invalid,
 * token is expired, or authentication process encounters errors.
 *
 * @package XGate\Exception
 * @author XGate PHP SDK
 */
class AuthenticationException extends XGateException
{
    /**
     * Create a new authentication exception
     *
     * @param string $message The error message describing the authentication failure
     * @param int $code The error code (default: 0)
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(string $message = 'Authentication failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for invalid credentials
     *
     * @param string $email The email that failed authentication
     * @return self
     */
    public static function invalidCredentials(string $email): self
    {
        return new self("Credenciais inválidas para o email: {$email}");
    }

    /**
     * Create exception for expired token
     *
     * @return self
     */
    public static function tokenExpired(): self
    {
        return new self('Token de autenticação expirou. Faça login novamente.');
    }

    /**
     * Create exception for missing token
     *
     * @return self
     */
    public static function missingToken(): self
    {
        return new self('Token de autenticação não encontrado. Faça login primeiro.');
    }

    /**
     * Create exception for login API failure
     *
     * @param int $statusCode HTTP status code from API
     * @param string $response API response body
     * @return self
     */
    public static function loginFailed(int $statusCode, string $response): self
    {
        return new self("Falha no login. Status: {$statusCode}, Resposta: {$response}");
    }
}
