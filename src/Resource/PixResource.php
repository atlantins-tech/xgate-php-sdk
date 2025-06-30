<?php

declare(strict_types=1);

namespace XGate\Resource;

use XGate\Http\HttpClient;
use XGate\Model\PixKey;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use Psr\Log\LoggerInterface;

/**
 * PIX Resource for XGATE API operations
 *
 * Handles HTTP operations for PIX key management through the XGATE API.
 * Provides methods for registering, retrieving, updating, and managing PIX keys
 * with proper error handling and DTO conversion.
 *
 * Supports all PIX key types: CPF, CNPJ, Email, Phone, and Random UUID.
 * Each PIX key is unique within the Brazilian Payment System.
 *
 * @package XGate\Resource
 * @author XGate PHP SDK Contributors
 *
 * @example Basic PIX key operations
 * ```php
 * $pixResource = new PixResource($httpClient, $logger);
 * 
 * // Register new PIX key
 * $pixKey = $pixResource->register('email', 'user@example.com');
 * 
 * // Get PIX key by ID
 * $pixKey = $pixResource->get('pix-key-123');
 * 
 * // List all PIX keys
 * $pixKeys = $pixResource->list();
 * ```
 */
class PixResource
{
    /**
     * HTTP client for API requests
     *
     * @var HttpClient
     */
    private HttpClient $httpClient;

    /**
     * Logger for operation tracking
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Base endpoint for PIX key operations
     *
     * @var string
     */
    private const ENDPOINT = '/pix/keys';

    /**
     * Create new PixResource instance
     *
     * @param HttpClient $httpClient HTTP client for API communication
     * @param LoggerInterface $logger Logger for operation tracking
     */
    public function __construct(HttpClient $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Register new PIX key via API
     *
     * @param string $type PIX key type (cpf, cnpj, email, phone, random)
     * @param string $key PIX key value
     * @param string|null $accountHolderName Account holder name
     * @param string|null $accountHolderDocument Account holder document
     * @param string|null $bankCode Bank code (ISPB)
     * @param string|null $accountNumber Account number
     * @param string|null $accountType Account type (checking, savings)
     * @param array<string, mixed> $metadata Additional PIX key metadata
     * @return PixKey Registered PIX key DTO
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Registering a new PIX key
     * ```php
     * $pixKey = $pixResource->register(
     *     type: 'email',
     *     key: 'user@example.com',
     *     accountHolderName: 'João Silva',
     *     accountHolderDocument: '12345678901',
     *     bankCode: '001'
     * );
     * ```
     */
    public function register(
        string $type,
        string $key,
        ?string $accountHolderName = null,
        ?string $accountHolderDocument = null,
        ?string $bankCode = null,
        ?string $accountNumber = null,
        ?string $accountType = null,
        array $metadata = []
    ): PixKey {
        $this->logger->info('Registering new PIX key', [
            'type' => $type,
            'key_masked' => $this->maskKeyForLogging($type, $key),
            'has_account_holder' => $accountHolderName !== null,
            'has_bank_code' => $bankCode !== null,
        ]);

        $requestData = [
            'type' => $type,
            'key' => $key,
        ];

        if ($accountHolderName !== null) {
            $requestData['account_holder_name'] = $accountHolderName;
        }

        if ($accountHolderDocument !== null) {
            $requestData['account_holder_document'] = $accountHolderDocument;
        }

        if ($bankCode !== null) {
            $requestData['bank_code'] = $bankCode;
        }

        if ($accountNumber !== null) {
            $requestData['account_number'] = $accountNumber;
        }

        if ($accountType !== null) {
            $requestData['account_type'] = $accountType;
        }

        if (!empty($metadata)) {
            $requestData['metadata'] = $metadata;
        }

        try {
            $response = $this->httpClient->request('POST', self::ENDPOINT, [
                'json' => $requestData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $pixKey = PixKey::fromArray($responseData);

            $this->logger->info('PIX key registered successfully', [
                'pix_key_id' => $pixKey->id,
                'type' => $pixKey->type,
                'status' => $pixKey->status,
            ]);

            return $pixKey;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to register PIX key', [
                'error' => $e->getMessage(),
                'type' => $type,
                'key_masked' => $this->maskKeyForLogging($type, $key),
            ]);
            throw $e;
        }
    }

    /**
     * Get PIX key by ID from API
     *
     * @param string $pixKeyId PIX key unique identifier
     * @return PixKey PIX key DTO
     * @throws ApiException If API returns error response or PIX key not found
     * @throws NetworkException If network request fails
     *
     * @example Getting PIX key by ID
     * ```php
     * $pixKey = $pixResource->get('pix-key-123');
     * echo $pixKey->type; // "email"
     * ```
     */
    public function get(string $pixKeyId): PixKey
    {
        $this->logger->info('Retrieving PIX key', ['pix_key_id' => $pixKeyId]);

        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT . '/' . $pixKeyId);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $pixKey = PixKey::fromArray($responseData);

            $this->logger->info('PIX key retrieved successfully', [
                'pix_key_id' => $pixKey->id,
                'type' => $pixKey->type,
                'status' => $pixKey->status,
            ]);

            return $pixKey;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to retrieve PIX key', [
                'error' => $e->getMessage(),
                'pix_key_id' => $pixKeyId,
            ]);
            throw $e;
        }
    }

    /**
     * Update PIX key information via API
     *
     * @param string $pixKeyId PIX key unique identifier
     * @param array<string, mixed> $updateData Fields to update
     * @return PixKey Updated PIX key DTO
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Updating PIX key metadata
     * ```php
     * $updatedPixKey = $pixResource->update('pix-key-123', [
     *     'account_holder_name' => 'João da Silva',
     *     'metadata' => ['updated_reason' => 'name_change']
     * ]);
     * ```
     */
    public function update(string $pixKeyId, array $updateData): PixKey
    {
        $this->logger->info('Updating PIX key', [
            'pix_key_id' => $pixKeyId,
            'fields_to_update' => array_keys($updateData),
        ]);

        try {
            $response = $this->httpClient->request('PUT', self::ENDPOINT . '/' . $pixKeyId, [
                'json' => $updateData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $pixKey = PixKey::fromArray($responseData);

            $this->logger->info('PIX key updated successfully', [
                'pix_key_id' => $pixKey->id,
                'type' => $pixKey->type,
                'status' => $pixKey->status,
            ]);

            return $pixKey;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to update PIX key', [
                'error' => $e->getMessage(),
                'pix_key_id' => $pixKeyId,
            ]);
            throw $e;
        }
    }

    /**
     * Delete (unregister) PIX key via API
     *
     * @param string $pixKeyId PIX key unique identifier
     * @return bool True if PIX key was successfully deleted
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Deleting PIX key
     * ```php
     * $success = $pixResource->delete('pix-key-123');
     * if ($success) {
     *     echo "PIX key unregistered successfully";
     * }
     * ```
     */
    public function delete(string $pixKeyId): bool
    {
        $this->logger->info('Deleting PIX key', ['pix_key_id' => $pixKeyId]);

        try {
            $response = $this->httpClient->request('DELETE', self::ENDPOINT . '/' . $pixKeyId);

            // Successful deletion typically returns empty response with 204 status
            json_decode($response->getBody()->getContents(), true);

            $this->logger->info('PIX key deleted successfully', ['pix_key_id' => $pixKeyId]);

            return true;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to delete PIX key', [
                'error' => $e->getMessage(),
                'pix_key_id' => $pixKeyId,
            ]);
            throw $e;
        }
    }

    /**
     * List PIX keys with pagination and filtering
     *
     * @param int $page Page number (1-based)
     * @param int $limit Number of PIX keys per page
     * @param array<string, mixed> $filters Optional filters (type, status, etc.)
     * @return array<PixKey> Array of PIX key DTOs
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Listing PIX keys with filters
     * ```php
     * // List active email PIX keys
     * $pixKeys = $pixResource->list(1, 10, [
     *     'type' => 'email',
     *     'status' => 'active'
     * ]);
     * 
     * foreach ($pixKeys as $pixKey) {
     *     echo $pixKey->getDisplayName() . "\n";
     * }
     * ```
     */
    public function list(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $this->logger->info('Listing PIX keys', [
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
        ]);

        $queryParams = [
            'page' => $page,
            'limit' => $limit,
        ];

        // Add filters to query parameters
        foreach ($filters as $key => $value) {
            $queryParams[$key] = $value;
        }

        $queryString = http_build_query($queryParams);
        $endpoint = self::ENDPOINT . '?' . $queryString;

        try {
            $response = $this->httpClient->request('GET', $endpoint);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Handle both direct array and paginated response formats
            $pixKeysData = $responseData['data'] ?? $responseData;
            
            $pixKeys = [];
            foreach ($pixKeysData as $pixKeyData) {
                $pixKeys[] = PixKey::fromArray($pixKeyData);
            }

            $this->logger->info('PIX keys listed successfully', [
                'count' => count($pixKeys),
                'page' => $page,
                'limit' => $limit,
            ]);

            return $pixKeys;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to list PIX keys', [
                'error' => $e->getMessage(),
                'page' => $page,
                'limit' => $limit,
            ]);
            throw $e;
        }
    }

    /**
     * Search PIX keys by key value or account holder
     *
     * @param string $query Search query (partial key value or account holder name)
     * @param int $limit Maximum number of results
     * @return array<PixKey> Array of matching PIX key DTOs
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Searching PIX keys
     * ```php
     * // Search by partial email
     * $pixKeys = $pixResource->search('example.com', 5);
     * 
     * // Search by account holder name
     * $pixKeys = $pixResource->search('João Silva', 10);
     * ```
     */
    public function search(string $query, int $limit = 10): array
    {
        $this->logger->info('Searching PIX keys', [
            'query_masked' => substr($query, 0, 3) . '***',
            'limit' => $limit,
        ]);

        $queryParams = [
            'q' => $query,
            'limit' => $limit,
        ];

        $queryString = http_build_query($queryParams);
        $endpoint = self::ENDPOINT . '/search?' . $queryString;

        try {
            $response = $this->httpClient->request('GET', $endpoint);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Handle both direct array and search response formats
            $pixKeysData = $responseData['results'] ?? $responseData;
            
            $pixKeys = [];
            foreach ($pixKeysData as $pixKeyData) {
                $pixKeys[] = PixKey::fromArray($pixKeyData);
            }

            $this->logger->info('PIX keys search completed', [
                'results_count' => count($pixKeys),
                'query_masked' => substr($query, 0, 3) . '***',
            ]);

            return $pixKeys;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to search PIX keys', [
                'error' => $e->getMessage(),
                'query_masked' => substr($query, 0, 3) . '***',
            ]);
            throw $e;
        }
    }

    /**
     * Get PIX key by key value and type
     *
     * @param string $type PIX key type (cpf, cnpj, email, phone, random)
     * @param string $key PIX key value
     * @return PixKey|null PIX key DTO if found, null otherwise
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Finding PIX key by value
     * ```php
     * $pixKey = $pixResource->findByKey('email', 'user@example.com');
     * if ($pixKey !== null) {
     *     echo "PIX key found: " . $pixKey->id;
     * }
     * ```
     */
    public function findByKey(string $type, string $key): ?PixKey
    {
        $this->logger->info('Finding PIX key by value', [
            'type' => $type,
            'key_masked' => $this->maskKeyForLogging($type, $key),
        ]);

        $queryParams = [
            'type' => $type,
            'key' => $key,
        ];

        $queryString = http_build_query($queryParams);
        $endpoint = self::ENDPOINT . '/find?' . $queryString;

        try {
            $response = $this->httpClient->request('GET', $endpoint);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if (empty($responseData)) {
                $this->logger->info('PIX key not found', [
                    'type' => $type,
                    'key_masked' => $this->maskKeyForLogging($type, $key),
                ]);
                return null;
            }

            $pixKey = PixKey::fromArray($responseData);

            $this->logger->info('PIX key found successfully', [
                'pix_key_id' => $pixKey->id,
                'type' => $pixKey->type,
                'status' => $pixKey->status,
            ]);

            return $pixKey;
        } catch (ApiException $e) {
            // Return null for 404 not found, but throw for other API errors
            if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'not found') !== false) {
                $this->logger->info('PIX key not found', [
                    'type' => $type,
                    'key_masked' => $this->maskKeyForLogging($type, $key),
                ]);
                return null;
            }
            
            $this->logger->error('Failed to find PIX key', [
                'error' => $e->getMessage(),
                'type' => $type,
                'key_masked' => $this->maskKeyForLogging($type, $key),
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Failed to find PIX key', [
                'error' => $e->getMessage(),
                'type' => $type,
                'key_masked' => $this->maskKeyForLogging($type, $key),
            ]);
            throw $e;
        }
    }

    /**
     * Mask PIX key value for secure logging
     *
     * @param string $type PIX key type
     * @param string $key PIX key value
     * @return string Masked key value
     */
    private function maskKeyForLogging(string $type, string $key): string
    {
        switch (strtolower($type)) {
            case 'cpf':
                $cleaned = preg_replace('/\D/', '', $key);
                if (strlen($cleaned) === 11) {
                    return substr($cleaned, 0, 3) . '***' . substr($cleaned, -2);
                }
                return '***';
            case 'cnpj':
                $cleaned = preg_replace('/\D/', '', $key);
                if (strlen($cleaned) === 14) {
                    return substr($cleaned, 0, 2) . '***' . substr($cleaned, -2);
                }
                return '***';
            case 'email':
                $parts = explode('@', $key);
                if (count($parts) === 2) {
                    return substr($parts[0], 0, 2) . '***@' . $parts[1];
                }
                return '***@***.***';
            case 'phone':
                $cleaned = preg_replace('/\D/', '', $key);
                if (strlen($cleaned) >= 10) {
                    return '+55***' . substr($cleaned, -4);
                }
                return '+55***';
            case 'random':
                return substr($key, 0, 8) . '***';
            default:
                return '***';
        }
    }
} 