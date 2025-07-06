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
    /**
     * API endpoint para operações de clientes
     * @see https://api.xgateglobal.com/pages/customer/create.html
     */
    private const ENDPOINT = '/customer';

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
     * @param array<string, mixed> $metadata Additional customer metadata
     * @return Customer Created customer DTO
     * @throws ApiException If API returns error response
     * @throws NetworkException If network request fails
     *
     * @see https://api.xgateglobal.com/pages/customer/create.html
     * @example Creating a new customer
     * ```php
     * $customer = $customerResource->create(
     *     name: 'João Silva',
     *     email: 'joao@example.com',
     *     phone: '+5511999999999',
     *     document: '12345678901'
     * );
     * ```
     */
    public function create(
        string $name,
        string $email,
        ?string $phone = null,
        ?string $document = null,
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

        if (!empty($metadata)) {
            $requestData['metadata'] = $metadata;
        }

        try {
            $response = $this->httpClient->request('POST', self::ENDPOINT, [
                'json' => $requestData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // A API retorna o cliente dentro da chave 'customer'
            $customerData = $responseData['customer'] ?? $responseData;
            
            // Mapear _id para id se necessário
            if (isset($customerData['_id']) && !isset($customerData['id'])) {
                $customerData['id'] = $customerData['_id'];
            }
            
            // Adicionar os dados originais se não estiverem na resposta
            if (!isset($customerData['name'])) {
                $customerData['name'] = $requestData['name'];
            }
            if (!isset($customerData['email'])) {
                $customerData['email'] = $requestData['email'];
            }
            if (!isset($customerData['phone']) && isset($requestData['phone'])) {
                $customerData['phone'] = $requestData['phone'];
            }
            if (!isset($customerData['document']) && isset($requestData['document'])) {
                $customerData['document'] = $requestData['document'];
            }
            
            $customer = Customer::fromArray($customerData);

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
            
            // Mapear _id para id se necessário
            if (isset($responseData['_id']) && !isset($responseData['id'])) {
                $responseData['id'] = $responseData['_id'];
            }
            
            // Mapear createdDate/updatedDate para createdAt/updatedAt
            if (isset($responseData['createdDate']) && !isset($responseData['createdAt'])) {
                $responseData['createdAt'] = $responseData['createdDate'];
            }
            if (isset($responseData['updatedDate']) && !isset($responseData['updatedAt'])) {
                $responseData['updatedAt'] = $responseData['updatedDate'];
            }
            
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
     * Official API Documentation: https://api.xgateglobal.com/pages/customer/update.html
     * 
     * Endpoint: PUT /customer/{CLIENT_ID}
     * 
     * Supported fields for update:
     * - name: string (optional) - Customer name
     * - document: string (optional) - Customer document number
     * - email: string (optional) - Customer email address  
     * - phone: string (optional) - Customer phone number
     *
     * @param string $customerId Customer unique identifier
     * @param array<string, mixed> $updateData Fields to update (name, document, email, phone)
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
            
            // A API de atualização não retorna os dados do cliente, apenas uma mensagem de sucesso
            // Precisamos buscar o cliente atualizado separadamente
            if (isset($responseData['message']) && strpos($responseData['message'], 'sucesso') !== false) {
                // Buscar o cliente atualizado
                $customer = $this->get($customerId);
                
                $this->logger->info('Customer updated successfully', [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                ]);
                
                return $customer;
            } else {
                // Fallback: tentar processar a resposta diretamente (caso a API mude no futuro)
                $customer = Customer::fromArray($responseData);
                
                $this->logger->info('Customer updated successfully', [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                ]);
                
                return $customer;
            }
        } catch (ApiException | NetworkException $e) {
            $this->logger->error('Failed to update customer', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
            ]);
            throw $e;
        }
    }

    // ===================================================================
    // MÉTODOS REMOVIDOS - NÃO DOCUMENTADOS OFICIALMENTE
    // ===================================================================
    // 
    // Os métodos delete(), list() e search() foram removidos porque:
    // 1. Não estão documentados na documentação oficial da XGATE
    // 2. Apresentam problemas de autenticação inconsistentes
    // 3. Mantemos apenas funcionalidades oficialmente suportadas
    //
    // Documentação oficial disponível:
    // - Criar: https://api.xgateglobal.com/pages/customer/create.html
    // - Atualizar: https://api.xgateglobal.com/pages/customer/update.html
    // - Buscar por ID: Funciona via endpoint GET /customer/{id}
    //
    // Se precisar dessas funcionalidades, verifique a documentação oficial
    // da XGATE para confirmar se foram adicionadas e como implementá-las.
    // ===================================================================
} 