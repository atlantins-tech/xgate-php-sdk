<?php

declare(strict_types=1);

namespace XGate\Resource;

use XGate\Http\HttpClient;
use XGate\Model\Customer;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use Psr\Log\LoggerInterface;

/**
 * Customer Resource for XGATE API operations
 *
 * Handles HTTP operations for customer management through the XGATE API.
 * Provides methods for creating, retrieving, updating, and listing customers
 * with proper error handling and DTO conversion.
 *
 * @package XGate\Resource
 * @author XGate PHP SDK Contributors
 *
 * @example Basic customer operations
 * ```php
 * $customerResource = new CustomerResource($httpClient, $logger);
 * 
 * // Create new customer
 * $newCustomer = $customerResource->create('João Silva', 'joao@example.com');
 * 
 * // Get customer by ID
 * $customer = $customerResource->get('customer-123');
 * 
 * // List all customers
 * $customers = $customerResource->list();
 * ```
 */
class CustomerResource
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
     * Base endpoint for customer operations
     *
     * @var string
     */
    private const ENDPOINT = '/customers';

    /**
     * Create new CustomerResource instance
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
     * Create new customer via API
     *
     * @param string $name Customer full name
     * @param string $email Customer email address
     * @param string|null $phone Customer phone number
     * @param string|null $document Customer document number
     * @param string|null $documentType Customer document type (cpf, cnpj)
     * @param array<string, mixed> $metadata Additional customer metadata
     * @return Customer Created customer DTO
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Creating a new customer
     * ```php
     * $customer = $customerResource->create(
     *     name: 'João Silva',
     *     email: 'joao@example.com',
     *     phone: '+5511999999999',
     *     document: '12345678901',
     *     documentType: 'cpf'
     * );
     * ```
     */
    public function create(
        string $name,
        string $email,
        ?string $phone = null,
        ?string $document = null,
        ?string $documentType = null,
        array $metadata = []
    ): Customer {
        $this->logger->info('Creating new customer', [
            'name' => $name,
            'email' => $email,
            'has_phone' => $phone !== null,
            'has_document' => $document !== null,
        ]);

        $requestData = [
            'name' => $name,
            'email' => $email,
        ];

        if ($phone !== null) {
            $requestData['phone'] = $phone;
        }

        if ($document !== null) {
            $requestData['document'] = $document;
        }

        if ($documentType !== null) {
            $requestData['document_type'] = $documentType;
        }

        if (!empty($metadata)) {
            $requestData['metadata'] = $metadata;
        }

        try {
            $response = $this->httpClient->request('POST', self::ENDPOINT, [
                'json' => $requestData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $customer = Customer::fromArray($responseData);

            $this->logger->info('Customer created successfully', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
            ]);

            return $customer;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to create customer', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);
            throw $e;
        }
    }

    /**
     * Get customer by ID from API
     *
     * @param string $customerId Customer unique identifier
     * @return Customer Customer DTO
     * @throws ApiException If API returns error response or customer not found
     * @throws NetworkException If network request fails
     *
     * @example Getting customer by ID
     * ```php
     * $customer = $customerResource->get('customer-123');
     * echo $customer->name; // "João Silva"
     * ```
     */
    public function get(string $customerId): Customer
    {
        $this->logger->info('Retrieving customer', ['customer_id' => $customerId]);

        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT . '/' . $customerId);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $customer = Customer::fromArray($responseData);

            $this->logger->info('Customer retrieved successfully', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
            ]);

            return $customer;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to retrieve customer', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
            ]);
            throw $e;
        }
    }

    /**
     * Update customer information via API
     *
     * @param string $customerId Customer unique identifier
     * @param array<string, mixed> $updateData Fields to update
     * @return Customer Updated customer DTO
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Updating customer information
     * ```php
     * $updatedCustomer = $customerResource->update('customer-123', [
     *     'name' => 'João Silva Santos',
     *     'phone' => '+5511888888888'
     * ]);
     * ```
     */
    public function update(string $customerId, array $updateData): Customer
    {
        $this->logger->info('Updating customer', [
            'customer_id' => $customerId,
            'fields' => array_keys($updateData),
        ]);

        try {
            $response = $this->httpClient->request('PUT', self::ENDPOINT . '/' . $customerId, [
                'json' => $updateData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $customer = Customer::fromArray($responseData);

            $this->logger->info('Customer updated successfully', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
            ]);

            return $customer;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to update customer', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
            ]);
            throw $e;
        }
    }

    /**
     * Delete customer from API
     *
     * @param string $customerId Customer unique identifier
     * @return bool True if deletion was successful
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Deleting a customer
     * ```php
     * $success = $customerResource->delete('customer-123');
     * if ($success) {
     *     echo 'Customer deleted successfully';
     * }
     * ```
     */
    public function delete(string $customerId): bool
    {
        $this->logger->info('Deleting customer', ['customer_id' => $customerId]);

        try {
            $this->httpClient->request('DELETE', self::ENDPOINT . '/' . $customerId);

            $this->logger->info('Customer deleted successfully', ['customer_id' => $customerId]);

            return true;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to delete customer', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
            ]);
            throw $e;
        }
    }

    /**
     * List customers from API with optional filtering
     *
     * @param int $page Page number for pagination (default: 1)
     * @param int $limit Number of customers per page (default: 20)
     * @param array<string, mixed> $filters Optional filters for customer search
     * @return array{customers: Customer[], pagination: array<string, mixed>} Customers list with pagination info
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Listing customers with pagination
     * ```php
     * $result = $customerResource->list(page: 1, limit: 10);
     * foreach ($result['customers'] as $customer) {
     *     echo $customer->name . "\n";
     * }
     * echo "Total: " . $result['pagination']['total'];
     * ```
     */
    public function list(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $this->logger->info('Listing customers', [
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
        ]);

        $queryParams = [
            'page' => $page,
            'limit' => $limit,
        ];

        if (!empty($filters)) {
            $queryParams = array_merge($queryParams, $filters);
        }

        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT, [
                'query' => $queryParams,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $customers = [];
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                foreach ($responseData['data'] as $customerData) {
                    $customers[] = Customer::fromArray($customerData);
                }
            }

            $pagination = $responseData['pagination'] ?? [];

            $this->logger->info('Customers listed successfully', [
                'count' => count($customers),
                'page' => $page,
                'total' => $pagination['total'] ?? 0,
            ]);

            return [
                'customers' => $customers,
                'pagination' => $pagination,
            ];
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to list customers', [
                'error' => $e->getMessage(),
                'page' => $page,
                'limit' => $limit,
            ]);
            throw $e;
        }
    }

    /**
     * Search customers by email or name
     *
     * @param string $query Search query (email or name)
     * @param int $limit Maximum number of results (default: 10)
     * @return Customer[] Array of matching customers
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @example Searching customers
     * ```php
     * $customers = $customerResource->search('joao@example.com');
     * foreach ($customers as $customer) {
     *     echo $customer->getDisplayName() . "\n";
     * }
     * ```
     */
    public function search(string $query, int $limit = 10): array
    {
        $this->logger->info('Searching customers', [
            'query' => $query,
            'limit' => $limit,
        ]);

        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT . '/search', [
                'query' => [
                    'q' => $query,
                    'limit' => $limit,
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $customers = [];
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                foreach ($responseData['data'] as $customerData) {
                    $customers[] = Customer::fromArray($customerData);
                }
            }

            $this->logger->info('Customer search completed', [
                'query' => $query,
                'results_count' => count($customers),
            ]);

            return $customers;
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to search customers', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            throw $e;
        }
    }
} 