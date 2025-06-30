<?php

declare(strict_types=1);

namespace XGate\Model;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Customer Data Transfer Object for XGATE API
 *
 * Simple DTO class for transporting customer data between the XGATE API
 * and client applications. Provides JSON serialization/deserialization
 * and basic type safety without complex domain validation.
 *
 * @package XGate\Model
 * @author XGate PHP SDK Contributors
 *
 * @example Basic customer DTO usage
 * ```php
 * // Creating from API response
 * $customerData = json_decode($apiResponse, true);
 * $customer = Customer::fromArray($customerData);
 *
 * // Converting to API request
 * $requestData = $customer->toArray();
 * ```
 */
class Customer implements JsonSerializable
{
    /**
     * Customer unique identifier
     *
     * @var string|null
     */
    public readonly ?string $id;

    /**
     * Customer full name
     *
     * @var string
     */
    public readonly string $name;

    /**
     * Customer email address
     *
     * @var string
     */
    public readonly string $email;

    /**
     * Customer phone number
     *
     * @var string|null
     */
    public readonly ?string $phone;

    /**
     * Customer document number (CPF/CNPJ)
     *
     * @var string|null
     */
    public readonly ?string $document;

    /**
     * Customer document type (cpf, cnpj)
     *
     * @var string|null
     */
    public readonly ?string $documentType;

    /**
     * Customer status (active, inactive, blocked)
     *
     * @var string
     */
    public readonly string $status;

    /**
     * Customer creation timestamp
     *
     * @var DateTimeImmutable|null
     */
    public readonly ?DateTimeImmutable $createdAt;

    /**
     * Customer last update timestamp
     *
     * @var DateTimeImmutable|null
     */
    public readonly ?DateTimeImmutable $updatedAt;

    /**
     * Additional customer metadata
     *
     * @var array<string, mixed>
     */
    public readonly array $metadata;

    /**
     * Create new Customer DTO instance
     *
     * @param string|null $id Customer unique identifier
     * @param string $name Customer full name
     * @param string $email Customer email address
     * @param string|null $phone Customer phone number
     * @param string|null $document Customer document number
     * @param string|null $documentType Customer document type
     * @param string $status Customer status
     * @param DateTimeImmutable|null $createdAt Customer creation timestamp
     * @param DateTimeImmutable|null $updatedAt Customer update timestamp
     * @param array<string, mixed> $metadata Additional customer metadata
     */
    public function __construct(
        ?string $id,
        string $name,
        string $email,
        ?string $phone = null,
        ?string $document = null,
        ?string $documentType = null,
        string $status = 'active',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->document = $document;
        $this->documentType = $documentType;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->metadata = $metadata;
    }

    /**
     * Create Customer DTO from array data (API response)
     *
     * @param array<string, mixed> $data Customer data from API
     * @return self Customer DTO instance
     *
     * @example Creating from API response
     * ```php
     * $apiResponse = ['id' => '123', 'name' => 'João Silva', 'email' => 'joao@example.com'];
     * $customer = Customer::fromArray($apiResponse);
     * ```
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? null,
            document: $data['document'] ?? null,
            documentType: $data['document_type'] ?? null,
            status: $data['status'] ?? 'active',
            createdAt: isset($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new DateTimeImmutable($data['updated_at']) : null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert Customer DTO to array (for API requests)
     *
     * @return array<string, mixed> Customer data as array
     *
     * @example Converting to API request
     * ```php
     * $customer = new Customer(null, 'João Silva', 'joao@example.com');
     * $requestData = $customer->toArray();
     * // Send $requestData to API
     * ```
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        if ($this->document !== null) {
            $data['document'] = $this->document;
        }

        if ($this->documentType !== null) {
            $data['document_type'] = $this->documentType;
        }

        if ($this->createdAt !== null) {
            $data['created_at'] = $this->createdAt->format('c');
        }

        if ($this->updatedAt !== null) {
            $data['updated_at'] = $this->updatedAt->format('c');
        }

        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * JSON serialization for API communication
     *
     * @return array<string, mixed> Data for JSON encoding
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create Customer DTO from JSON string
     *
     * @param string $json JSON string from API
     * @return self Customer DTO instance
     * @throws \JsonException If JSON is invalid
     *
     * @example Creating from JSON response
     * ```php
     * $jsonResponse = '{"id":"123","name":"João Silva","email":"joao@example.com"}';
     * $customer = Customer::fromJson($jsonResponse);
     * ```
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    /**
     * Convert Customer DTO to JSON string
     *
     * @return string JSON representation
     * @throws \JsonException If encoding fails
     *
     * @example Converting to JSON
     * ```php
     * $customer = new Customer(null, 'João Silva', 'joao@example.com');
     * $json = $customer->toJson();
     * ```
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Check if customer has valid email format
     *
     * @return bool True if email is valid
     */
    public function hasValidEmail(): bool
    {
        return filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if customer is active
     *
     * @return bool True if customer status is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get customer display name for UI
     *
     * @return string Customer name or email if name is empty
     */
    public function getDisplayName(): string
    {
        return !empty($this->name) ? $this->name : $this->email;
    }
} 