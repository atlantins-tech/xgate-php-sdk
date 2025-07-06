<?php

declare(strict_types=1);

namespace XGate\Authentication;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use XGate\Exception\ApiException;
use XGate\Exception\AuthenticationException;
use XGate\Exception\NetworkException;
use XGate\Http\HttpClient;

/**
 * Manages authentication with XGATE API
 *
 * Handles user login, token storage using PSR-16 cache,
 * and authorization header injection for API requests.
 * Supports simple token authentication (non-JWT).
 *
 * @package XGate\Authentication
 * @author XGate PHP SDK
 */
class AuthenticationManager implements AuthenticationManagerInterface
{
    /**
     * Cache key for storing authentication token
     */
    private const TOKEN_CACHE_KEY = 'xgate_auth_token';

    /**
     * Token cache TTL in seconds (24 hours)
     */
    private const TOKEN_TTL = 86400;

    /**
     * HTTP client for API requests
     *
     * @var HttpClient
     */
    private HttpClient $httpClient;

    /**
     * Cache implementation for token storage
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Create new authentication manager
     *
     * @param HttpClient $httpClient HTTP client for API communication
     * @param CacheInterface $cache PSR-16 cache implementation for token storage
     */
    public function __construct(HttpClient $httpClient, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /**
     * Authenticate user with email and password
     *
     * Sends POST request to /auth/token endpoint with credentials
     * and stores the received access token for subsequent requests.
     *
     * @param string $email User email address for authentication
     * @param string $password User password for authentication
     * @return bool True if authentication successful, false otherwise
     * @throws AuthenticationException When authentication fails or API returns error
     *
     * @example
     * ```php
     * $authManager = new AuthenticationManager($httpClient, $cache);
     * if ($authManager->login('user@example.com', 'password123')) {
     *     echo 'Login successful!';
     * }
     * ```
     */
    public function login(string $email, string $password): bool
    {
        try {
            $response = $this->httpClient->post('/auth/token', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['token'])) {
                throw AuthenticationException::loginFailed(
                    $response->getStatusCode(),
                    'Token de acesso não encontrado na resposta'
                );
            }

            $token = $data['token'];

            // Store token in cache
            $this->storeToken($token);

            return true;

        } catch (ApiException $e) {
            if ($e->getStatusCode() === 401) {
                throw AuthenticationException::invalidCredentials($email);
            }

            throw AuthenticationException::loginFailed(
                $e->getStatusCode(),
                $e->getMessage()
            );
        } catch (NetworkException $e) {
            throw new AuthenticationException(
                'Erro de rede durante autenticação: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get stored authentication token
     *
     * Retrieves the access token from cache storage.
     * Returns null if no token is stored or token is invalid.
     *
     * @return string|null The stored access token or null if not available
     */
    public function getToken(): ?string
    {
        try {
            $token = $this->cache->get(self::TOKEN_CACHE_KEY);

            return is_string($token) ? $token : null;
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Check if user is currently authenticated
     *
     * Verifies if a valid authentication token is available.
     *
     * @return bool True if authenticated (token available), false otherwise
     */
    public function isAuthenticated(): bool
    {
        return $this->getToken() !== null;
    }

    /**
     * Get authorization header for API requests
     *
     * Returns the Authorization header with Bearer token
     * for authenticated API requests.
     *
     * @return array<string, string> Authorization header array or empty array if not authenticated
     * @throws AuthenticationException When no token is available
     *
     * @example
     * ```php
     * $headers = $authManager->getAuthorizationHeader();
     * // Returns: ['Authorization' => 'Bearer abc123...']
     * ```
     */
    public function getAuthorizationHeader(): array
    {
        $token = $this->getToken();

        if ($token === null) {
            throw AuthenticationException::missingToken();
        }

        return ['Authorization' => 'Bearer ' . $token];
    }

    /**
     * Clear stored authentication token
     *
     * Removes the stored token from cache, effectively logging out the user.
     *
     * @return bool True if token was cleared successfully
     */
    public function logout(): bool
    {
        try {
            return $this->cache->delete(self::TOKEN_CACHE_KEY);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Store authentication token in cache
     *
     * @param string $token The access token to store
     * @throws AuthenticationException When token storage fails
     */
    private function storeToken(string $token): void
    {
        try {
            $success = $this->cache->set(self::TOKEN_CACHE_KEY, $token, self::TOKEN_TTL);

            if (!$success) {
                throw new AuthenticationException('Falha ao armazenar token de autenticação');
            }
        } catch (InvalidArgumentException $e) {
            throw new AuthenticationException(
                'Erro ao armazenar token: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
