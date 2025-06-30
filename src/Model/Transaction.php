<?php

declare(strict_types=1);

namespace XGate\Model;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Transaction Data Transfer Object for XGATE API
 *
 * Simple DTO class for transporting transaction data between the XGATE API
 * and client applications. Provides JSON serialization/deserialization
 * and basic type safety without complex domain validation.
 *
 * Supports both deposit and withdrawal operations with comprehensive
 * transaction metadata for audit and reconciliation purposes.
 *
 * @package XGate\Model
 * @author XGate PHP SDK Contributors
 *
 * @example Basic transaction DTO usage
 * ```php
 * // Creating from API response
 * $transactionData = json_decode($apiResponse, true);
 * $transaction = Transaction::fromArray($transactionData);
 *
 * // Converting to API request
 * $requestData = $transaction->toArray();
 * ```
 */
class Transaction implements JsonSerializable
{
    /**
     * Transaction unique identifier
     *
     * @var string|null
     */
    public readonly ?string $id;

    /**
     * Transaction amount (decimal as string for precision)
     *
     * @var string
     */
    public readonly string $amount;

    /**
     * Transaction currency (ISO 4217 code)
     *
     * @var string
     */
    public readonly string $currency;

    /**
     * Account or wallet identifier
     *
     * @var string|null
     */
    public readonly ?string $accountId;

    /**
     * Payment method used for transaction
     *
     * @var string|null
     */
    public readonly ?string $paymentMethod;

    /**
     * Transaction type (deposit, withdrawal)
     *
     * @var string
     */
    public readonly string $type;

    /**
     * Transaction status (pending, completed, failed, cancelled)
     *
     * @var string
     */
    public readonly string $status;

    /**
     * External reference or order ID
     *
     * @var string|null
     */
    public readonly ?string $referenceId;

    /**
     * Transaction description or note
     *
     * @var string|null
     */
    public readonly ?string $description;

    /**
     * Transaction fees (decimal as string for precision)
     *
     * @var string|null
     */
    public readonly ?string $fees;

    /**
     * Exchange rate applied (decimal as string for precision)
     *
     * @var string|null
     */
    public readonly ?string $exchangeRate;

    /**
     * Callback URL for transaction notifications
     *
     * @var string|null
     */
    public readonly ?string $callbackUrl;

    /**
     * Transaction creation timestamp
     *
     * @var DateTimeImmutable|null
     */
    public readonly ?DateTimeImmutable $createdAt;

    /**
     * Transaction last update timestamp
     *
     * @var DateTimeImmutable|null
     */
    public readonly ?DateTimeImmutable $updatedAt;

    /**
     * Transaction completion timestamp
     *
     * @var DateTimeImmutable|null
     */
    public readonly ?DateTimeImmutable $completedAt;

    /**
     * Additional transaction metadata
     *
     * @var array<string, mixed>
     */
    public readonly array $metadata;

    /**
     * Create new Transaction DTO instance
     *
     * @param string|null $id Transaction unique identifier
     * @param string $amount Transaction amount (decimal as string)
     * @param string $currency Transaction currency (ISO 4217 code)
     * @param string|null $accountId Account or wallet identifier
     * @param string|null $paymentMethod Payment method used
     * @param string $type Transaction type (deposit, withdrawal)
     * @param string $status Transaction status
     * @param string|null $referenceId External reference or order ID
     * @param string|null $description Transaction description
     * @param string|null $fees Transaction fees (decimal as string)
     * @param string|null $exchangeRate Exchange rate applied (decimal as string)
     * @param string|null $callbackUrl Callback URL for notifications
     * @param DateTimeImmutable|null $createdAt Transaction creation timestamp
     * @param DateTimeImmutable|null $updatedAt Transaction update timestamp
     * @param DateTimeImmutable|null $completedAt Transaction completion timestamp
     * @param array<string, mixed> $metadata Additional transaction metadata
     */
    public function __construct(
        ?string $id,
        string $amount,
        string $currency,
        ?string $accountId = null,
        ?string $paymentMethod = null,
        string $type = 'deposit',
        string $status = 'pending',
        ?string $referenceId = null,
        ?string $description = null,
        ?string $fees = null,
        ?string $exchangeRate = null,
        ?string $callbackUrl = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $completedAt = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->accountId = $accountId;
        $this->paymentMethod = $paymentMethod;
        $this->type = $type;
        $this->status = $status;
        $this->referenceId = $referenceId;
        $this->description = $description;
        $this->fees = $fees;
        $this->exchangeRate = $exchangeRate;
        $this->callbackUrl = $callbackUrl;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->completedAt = $completedAt;
        $this->metadata = $metadata;
    }

    /**
     * Create Transaction DTO from array data (API response)
     *
     * @param array<string, mixed> $data Transaction data from API
     * @return self Transaction DTO instance
     *
     * @example Creating from API response
     * ```php
     * $apiResponse = ['id' => '123', 'amount' => '100.50', 'currency' => 'BRL', 'type' => 'deposit'];
     * $transaction = Transaction::fromArray($apiResponse);
     * ```
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            amount: (string) ($data['amount'] ?? '0.00'),
            currency: $data['currency'] ?? 'BRL',
            accountId: $data['account_id'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            type: $data['type'] ?? 'deposit',
            status: $data['status'] ?? 'pending',
            referenceId: $data['reference_id'] ?? null,
            description: $data['description'] ?? null,
            fees: isset($data['fees']) ? (string) $data['fees'] : null,
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : null,
            callbackUrl: $data['callback_url'] ?? null,
            createdAt: isset($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new DateTimeImmutable($data['updated_at']) : null,
            completedAt: isset($data['completed_at']) ? new DateTimeImmutable($data['completed_at']) : null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert Transaction DTO to array (for API requests)
     *
     * @return array<string, mixed> Transaction data as array
     *
     * @example Converting to API request
     * ```php
     * $transaction = new Transaction(null, '100.50', 'BRL', null, null, 'deposit');
     * $requestData = $transaction->toArray();
     * // Send $requestData to API
     * ```
     */
    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'type' => $this->type,
            'status' => $this->status,
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->accountId !== null) {
            $data['account_id'] = $this->accountId;
        }

        if ($this->paymentMethod !== null) {
            $data['payment_method'] = $this->paymentMethod;
        }

        if ($this->referenceId !== null) {
            $data['reference_id'] = $this->referenceId;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->fees !== null) {
            $data['fees'] = $this->fees;
        }

        if ($this->exchangeRate !== null) {
            $data['exchange_rate'] = $this->exchangeRate;
        }

        if ($this->callbackUrl !== null) {
            $data['callback_url'] = $this->callbackUrl;
        }

        if ($this->createdAt !== null) {
            $data['created_at'] = $this->createdAt->format('c');
        }

        if ($this->updatedAt !== null) {
            $data['updated_at'] = $this->updatedAt->format('c');
        }

        if ($this->completedAt !== null) {
            $data['completed_at'] = $this->completedAt->format('c');
        }

        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * JSON serialization for JsonSerializable interface
     *
     * @return array<string, mixed> Transaction data for JSON encoding
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create Transaction DTO from JSON string
     *
     * @param string $json JSON string containing transaction data
     * @return self Transaction DTO instance
     *
     * @throws \JsonException If JSON is invalid
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    /**
     * Convert Transaction DTO to JSON string
     *
     * @return string JSON representation of transaction
     *
     * @throws \JsonException If JSON encoding fails
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * Check if transaction is a deposit
     *
     * @return bool True if transaction is a deposit
     */
    public function isDeposit(): bool
    {
        return strtolower($this->type) === 'deposit';
    }

    /**
     * Check if transaction is a withdrawal
     *
     * @return bool True if transaction is a withdrawal
     */
    public function isWithdrawal(): bool
    {
        return strtolower($this->type) === 'withdrawal';
    }

    /**
     * Check if transaction is completed
     *
     * @return bool True if transaction is completed
     */
    public function isCompleted(): bool
    {
        return strtolower($this->status) === 'completed';
    }

    /**
     * Check if transaction is pending
     *
     * @return bool True if transaction is pending
     */
    public function isPending(): bool
    {
        return strtolower($this->status) === 'pending';
    }

    /**
     * Check if transaction failed
     *
     * @return bool True if transaction failed
     */
    public function isFailed(): bool
    {
        return strtolower($this->status) === 'failed';
    }

    /**
     * Get formatted amount with currency
     *
     * @return string Formatted amount (e.g., "100.50 BRL")
     */
    public function getFormattedAmount(): string
    {
        return sprintf('%s %s', $this->amount, $this->currency);
    }

    /**
     * Get total amount including fees
     *
     * @return string Total amount with fees (decimal as string)
     */
    public function getTotalAmount(): string
    {
        if ($this->fees === null) {
            return $this->amount;
        }

        return bcadd($this->amount, $this->fees, 2);
    }

    /**
     * Get transaction display name for UI
     *
     * @return string Transaction display name
     */
    public function getDisplayName(): string
    {
        $type = ucfirst(strtolower($this->type));
        $amount = $this->getFormattedAmount();
        
        if ($this->description !== null) {
            return sprintf('%s: %s - %s', $type, $amount, $this->description);
        }

        return sprintf('%s: %s', $type, $amount);
    }
} 