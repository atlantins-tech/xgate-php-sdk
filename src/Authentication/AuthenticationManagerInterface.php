<?php

declare(strict_types=1);

namespace XGate\Authentication;

use XGate\Exception\AuthenticationException;

/**
 * Interface for XGATE API authentication management
 *
 * Defines the contract for authentication operations including
 * login, token management, and request authorization.
 *
 * @package XGate\Authentication
 * @author XGate PHP SDK
 */
interface AuthenticationManagerInterface
{
    /**
     * Authenticate user with email and password
     *
     * Sends POST request to /login endpoint with credentials
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
    public function login(string $email, string $password): bool;

    /**
     * Get stored authentication token
     *
     * Retrieves the access token from cache storage.
     * Returns null if no token is stored or token is invalid.
     *
     * @return string|null The stored access token or null if not available
     */
    public function getToken(): ?string;

    /**
     * Check if user is currently authenticated
     *
     * Verifies if a valid authentication token is available.
     *
     * @return bool True if authenticated (token available), false otherwise
     */
    public function isAuthenticated(): bool;

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
    public function getAuthorizationHeader(): array;

    /**
     * Clear stored authentication token
     *
     * Removes the stored token from cache, effectively logging out the user.
     *
     * @return bool True if token was cleared successfully
     */
    public function logout(): bool;
}
