<?php

declare(strict_types=1);

namespace XGate\Model;

use DateTimeImmutable;
use JsonSerializable;

/**
 * PIX Key Data Transfer Object for XGATE API
 *
 * Simple DTO class for transporting PIX key data between the XGATE API
 * and client applications. Provides JSON serialization/deserialization
 * and basic type safety without complex domain validation.
 *
 * Supports all PIX key types: CPF, CNPJ, Email, Phone, and Random UUID.
 * Each PIX key is unique within the Brazilian Payment System and linked
 * to exactly one transactional account.
 *
 * @package XGate\Model
 * @author XGate PHP SDK Contributors
 *
 * @example Basic PIX key DTO usage
 * ```php
 * // Creating from API response
 * $pixKeyData = json_decode($apiResponse, true);
 * $pixKey = PixKey::fromArray($pixKeyData);
 *
 * // Converting to API request
 * $requestData = $pixKey->toArray();
 * ```
 */
class PixKey implements JsonSerializable
{
    /**
     * PIX key unique identifier
     *
     * @var string|null
     */
    public readonly ?string $id;

    /**
     * PIX key type (cpf, cnpj, email, phone, random)
     *
     * @var string
     */
    public readonly string $type;

    /**
     * PIX key value (the actual key)
     *
     * @var string
     */
    public readonly string $key;

    /**
     * Account holder name
     *
     * @var string|null
     */
    public readonly ?string $accountHolderName;

    /**
     * Account holder document (CPF/CNPJ)
     *
     * @var string|null
     */
    public readonly ?string $accountHolderDocument;

    /**
     * Bank code (ISPB)
     *
     * @var string|null
     */
    public readonly ?string $bankCode;

    /**
     * Bank name
     *
     * @var string|null
     */
    public readonly ?string $bankName;

    /**
     * Account branch
     *
     * @var string|null
     */
    public readonly ?string $branch;

    /**
     * Account number
     *
     * @var string|null
     */
    public readonly ?string $accountNumber;

    /**
     * Account type (checking, savings)
     *
     * @var string|null
     */
    public readonly ?string $accountType;

    /**
     * PIX key status (active, inactive, blocked)
     *
     * @var string
     */
    public readonly string $status;

    /**
     * PIX key creation timestamp
     *
     * @var DateTimeImmutable|null
     */
    public readonly ?DateTimeImmutable $createdAt;

    /**
     * PIX key last update timestamp
     *
     * @var DateTimeImmutable|null
     */
    public readonly ?DateTimeImmutable $updatedAt;

    /**
     * Additional PIX key metadata
     *
     * @var array<string, mixed>
     */
    public readonly array $metadata;

    /**
     * Create new PIX Key DTO instance
     *
     * @param string|null $id PIX key unique identifier
     * @param string $type PIX key type (cpf, cnpj, email, phone, random)
     * @param string $key PIX key value
     * @param string|null $accountHolderName Account holder name
     * @param string|null $accountHolderDocument Account holder document
     * @param string|null $bankCode Bank code (ISPB)
     * @param string|null $bankName Bank name
     * @param string|null $branch Account branch
     * @param string|null $accountNumber Account number
     * @param string|null $accountType Account type
     * @param string $status PIX key status
     * @param DateTimeImmutable|null $createdAt PIX key creation timestamp
     * @param DateTimeImmutable|null $updatedAt PIX key update timestamp
     * @param array<string, mixed> $metadata Additional PIX key metadata
     */
    public function __construct(
        ?string $id,
        string $type,
        string $key,
        ?string $accountHolderName = null,
        ?string $accountHolderDocument = null,
        ?string $bankCode = null,
        ?string $bankName = null,
        ?string $branch = null,
        ?string $accountNumber = null,
        ?string $accountType = null,
        string $status = 'active',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->key = $key;
        $this->accountHolderName = $accountHolderName;
        $this->accountHolderDocument = $accountHolderDocument;
        $this->bankCode = $bankCode;
        $this->bankName = $bankName;
        $this->branch = $branch;
        $this->accountNumber = $accountNumber;
        $this->accountType = $accountType;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->metadata = $metadata;
    }

    /**
     * Create PIX Key DTO from array data (API response)
     *
     * @param array<string, mixed> $data PIX key data from API
     * @return self PIX Key DTO instance
     *
     * @example Creating from API response
     * ```php
     * $apiResponse = ['id' => '123', 'type' => 'email', 'key' => 'user@example.com'];
     * $pixKey = PixKey::fromArray($apiResponse);
     * ```
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            type: $data['type'] ?? '',
            key: $data['key'] ?? '',
            accountHolderName: $data['account_holder_name'] ?? null,
            accountHolderDocument: $data['account_holder_document'] ?? null,
            bankCode: $data['bank_code'] ?? null,
            bankName: $data['bank_name'] ?? null,
            branch: $data['branch'] ?? null,
            accountNumber: $data['account_number'] ?? null,
            accountType: $data['account_type'] ?? null,
            status: $data['status'] ?? 'active',
            createdAt: isset($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new DateTimeImmutable($data['updated_at']) : null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert PIX Key DTO to array (for API requests)
     *
     * @return array<string, mixed> PIX key data as array
     *
     * @example Converting to API request
     * ```php
     * $pixKey = new PixKey(null, 'email', 'user@example.com');
     * $requestData = $pixKey->toArray();
     * // Send $requestData to API
     * ```
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'key' => $this->key,
            'status' => $this->status,
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->accountHolderName !== null) {
            $data['account_holder_name'] = $this->accountHolderName;
        }

        if ($this->accountHolderDocument !== null) {
            $data['account_holder_document'] = $this->accountHolderDocument;
        }

        if ($this->bankCode !== null) {
            $data['bank_code'] = $this->bankCode;
        }

        if ($this->bankName !== null) {
            $data['bank_name'] = $this->bankName;
        }

        if ($this->branch !== null) {
            $data['branch'] = $this->branch;
        }

        if ($this->accountNumber !== null) {
            $data['account_number'] = $this->accountNumber;
        }

        if ($this->accountType !== null) {
            $data['account_type'] = $this->accountType;
        }

        if ($this->createdAt !== null) {
            $data['created_at'] = $this->createdAt->format('Y-m-d\TH:i:s\Z');
        }

        if ($this->updatedAt !== null) {
            $data['updated_at'] = $this->updatedAt->format('Y-m-d\TH:i:s\Z');
        }

        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Serialize PIX Key DTO to JSON format
     *
     * @return array<string, mixed> PIX key data for JSON serialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create PIX Key DTO from JSON string
     *
     * @param string $json JSON string containing PIX key data
     * @return self PIX Key DTO instance
     *
     * @example Creating from JSON
     * ```php
     * $json = '{"type": "email", "key": "user@example.com", "status": "active"}';
     * $pixKey = PixKey::fromJson($json);
     * ```
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON provided for PIX key data');
        }

        return self::fromArray($data);
    }

    /**
     * Convert PIX Key DTO to JSON string
     *
     * @return string JSON representation of PIX key data
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * Check if PIX key type is CPF
     *
     * @return bool True if key type is CPF
     */
    public function isCpf(): bool
    {
        return strtolower($this->type) === 'cpf';
    }

    /**
     * Check if PIX key type is CNPJ
     *
     * @return bool True if key type is CNPJ
     */
    public function isCnpj(): bool
    {
        return strtolower($this->type) === 'cnpj';
    }

    /**
     * Check if PIX key type is email
     *
     * @return bool True if key type is email
     */
    public function isEmail(): bool
    {
        return strtolower($this->type) === 'email';
    }

    /**
     * Check if PIX key type is phone
     *
     * @return bool True if key type is phone
     */
    public function isPhone(): bool
    {
        return strtolower($this->type) === 'phone';
    }

    /**
     * Check if PIX key type is random UUID
     *
     * @return bool True if key type is random
     */
    public function isRandom(): bool
    {
        return strtolower($this->type) === 'random';
    }

    /**
     * Check if PIX key is active
     *
     * @return bool True if PIX key status is active
     */
    public function isActive(): bool
    {
        return strtolower($this->status) === 'active';
    }

    /**
     * Get display name for PIX key (masked for privacy)
     *
     * @return string Masked PIX key for display
     *
     * @example Display names
     * ```php
     * // CPF: 123.456.789-00 -> ***.***.789-**
     * // Email: user@example.com -> u***@example.com
     * // Phone: +5511999999999 -> +55***999999
     * ```
     */
    public function getDisplayName(): string
    {
        switch (strtolower($this->type)) {
            case 'cpf':
                return $this->maskCpf($this->key);
            case 'cnpj':
                return $this->maskCnpj($this->key);
            case 'email':
                return $this->maskEmail($this->key);
            case 'phone':
                return $this->maskPhone($this->key);
            case 'random':
                return substr($this->key, 0, 8) . '...';
            default:
                return '***';
        }
    }

    /**
     * Mask CPF for display
     *
     * @param string $cpf CPF to mask
     * @return string Masked CPF
     */
    private function maskCpf(string $cpf): string
    {
        $cleaned = preg_replace('/\D/', '', $cpf);
        if (strlen($cleaned) === 11) {
            return substr($cleaned, 0, 3) . '.***.' . substr($cleaned, 6, 3) . '-**';
        }
        return '***.***.***-**';
    }

    /**
     * Mask CNPJ for display
     *
     * @param string $cnpj CNPJ to mask
     * @return string Masked CNPJ
     */
    private function maskCnpj(string $cnpj): string
    {
        $cleaned = preg_replace('/\D/', '', $cnpj);
        if (strlen($cleaned) === 14) {
            return substr($cleaned, 0, 2) . '.***.' . substr($cleaned, 5, 3) . '/****-**';
        }
        return '**.***.***/****-**';
    }

    /**
     * Mask email for display
     *
     * @param string $email Email to mask
     * @return string Masked email
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) === 2) {
            $username = $parts[0];
            $domain = $parts[1];
            $maskedUsername = substr($username, 0, 1) . str_repeat('*', max(0, strlen($username) - 2)) . substr($username, -1);
            return $maskedUsername . '@' . $domain;
        }
        return '***@***.***';
    }

    /**
     * Mask phone for display
     *
     * @param string $phone Phone to mask
     * @return string Masked phone
     */
    private function maskPhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);
        if (strlen($cleaned) >= 10) {
            return '+55***' . substr($cleaned, -6);
        }
        return '+55***999999';
    }
} 