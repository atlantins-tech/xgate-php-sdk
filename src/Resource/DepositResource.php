<?php

declare(strict_types=1);

namespace XGate\Resource;

use Psr\Log\LoggerInterface;
use XGate\XGateClient;
use XGate\Model\Transaction;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;

/**
 * DepositResource handles fiat deposit operations via XGATE API
 *
 * This class provides methods for managing financial deposit transactions,
 * including creating deposits, retrieving status, and listing supported currencies.
 *
 * @package XGate\Resource
 * @author  XGATE Development Team
 * @since   1.0.0
 *
 * @example Basic deposit creation
 * ```php
 * $depositResource = new DepositResource($xgateClient, $logger);
 *
 * // Create a new deposit transaction
 * $transaction = new Transaction(
 *     id: null,
 *     amount: '100.50',
 *     currency: 'BRL',
 *     accountId: 'acc_123',
 *     paymentMethod: 'bank_transfer',
 *     type: 'deposit',
 *     description: 'Invoice payment'
 * );
 *
 * $result = $depositResource->createDeposit($transaction);
 * echo "Deposit created with ID: " . $result->id;
 * ```
 */
class DepositResource
{
    private const ENDPOINT_DEPOSITS = '/deposits';
    private const ENDPOINT_CURRENCIES = '/deposits/currencies';

    public function __construct(
        private readonly XGateClient $xgateClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * List all supported currencies for fiat deposits
     *
     * Retrieves the list of fiat currencies that can be used for deposit operations.
     * This is useful for validating currency codes before creating deposits.
     *
     * @return array<int, string> Array of supported currency codes (ISO 4217)
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example List supported currencies
     * ```php
     * $currencies = $depositResource->listSupportedCurrencies();
     * // Returns: ['BRL', 'USD', 'EUR', 'GBP']
     * 
     * foreach ($currencies as $currency) {
     *     echo "Supported currency: {$currency}\n";
     * }
     * ```
     */
    public function listSupportedCurrencies(): array
    {
        $this->logger->info('Listing supported currencies for deposits');

        try {
            $response = $this->xgateClient->get(self::ENDPOINT_CURRENCIES);
            $data = json_decode($response->getBody()->getContents(), true);

            $this->logger->info('Successfully retrieved supported currencies', [
                'count' => count($data['currencies'] ?? [])
            ]);

            return $data['currencies'] ?? [];
        } catch (ApiException $e) {
            $this->logger->error('API error while listing supported currencies', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while listing supported currencies', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new fiat deposit transaction
     *
     * Initiates a new deposit transaction with the provided details.
     * The transaction will be processed asynchronously and status can be checked
     * using the getDeposit() method.
     *
     * @param Transaction $transaction Transaction data for the deposit
     *
     * @return Transaction The created transaction with server-assigned ID and timestamps
     *
     * @throws ApiException If the API returns an error response (validation, etc.)
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Create a deposit transaction
     * ```php
     * $transaction = new Transaction(
     *     id: null,
     *     amount: '250.00',
     *     currency: 'BRL',
     *     accountId: 'acc_456',
     *     paymentMethod: 'bank_transfer',
     *     type: 'deposit',
     *     referenceId: 'invoice_789',
     *     description: 'Payment for services',
     *     callbackUrl: 'https://myapp.com/webhooks/deposit'
     * );
     *
     * $result = $depositResource->createDeposit($transaction);
     * 
     * if ($result->isPending()) {
     *     echo "Deposit is being processed. ID: {$result->id}";
     * }
     * ```
     */
    public function createDeposit(Transaction $transaction): Transaction
    {
        $this->logger->info('Creating new deposit transaction', [
            'amount' => $this->maskSensitiveData($transaction->amount),
            'currency' => $transaction->currency,
            'type' => $transaction->type,
            'account_id' => $this->maskSensitiveData($transaction->accountId)
        ]);

        try {
            $requestData = $transaction->toArray();
            $response = $this->xgateClient->post(self::ENDPOINT_DEPOSITS, ['json' => $requestData]);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $createdTransaction = Transaction::fromArray($responseData);

            $this->logger->info('Successfully created deposit transaction', [
                'transaction_id' => $createdTransaction->id,
                'status' => $createdTransaction->status,
                'amount' => $this->maskSensitiveData($createdTransaction->amount),
                'currency' => $createdTransaction->currency
            ]);

            return $createdTransaction;
        } catch (ApiException $e) {
            $this->logger->error('API error while creating deposit transaction', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'amount' => $this->maskSensitiveData($transaction->amount),
                'currency' => $transaction->currency
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while creating deposit transaction', [
                'error' => $e->getMessage(),
                'amount' => $this->maskSensitiveData($transaction->amount),
                'currency' => $transaction->currency
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a specific deposit transaction by ID
     *
     * Fetches the current status and details of a deposit transaction.
     * Use this method to check the status of previously created deposits.
     *
     * @param string $depositId Unique identifier of the deposit transaction
     *
     * @return Transaction The deposit transaction with current status
     *
     * @throws ApiException If the API returns an error response or deposit not found
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Check deposit status
     * ```php
     * $depositId = 'txn_12345';
     * $deposit = $depositResource->getDeposit($depositId);
     *
     * switch (true) {
     *     case $deposit->isPending():
     *         echo "Deposit is still being processed";
     *         break;
     *     case $deposit->isCompleted():
     *         echo "Deposit completed successfully!";
     *         break;
     *     case $deposit->isFailed():
     *         echo "Deposit failed";
     *         break;
     * }
     * ```
     */
    public function getDeposit(string $depositId): Transaction
    {
        $this->logger->info('Retrieving deposit transaction', [
            'deposit_id' => $depositId
        ]);

        try {
            $endpoint = self::ENDPOINT_DEPOSITS . '/' . urlencode($depositId);
            $response = $this->xgateClient->get($endpoint);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $transaction = Transaction::fromArray($responseData);

            $this->logger->info('Successfully retrieved deposit transaction', [
                'deposit_id' => $depositId,
                'status' => $transaction->status,
                'amount' => $this->maskSensitiveData($transaction->amount),
                'currency' => $transaction->currency
            ]);

            return $transaction;
        } catch (ApiException $e) {
            $this->logger->error('API error while retrieving deposit transaction', [
                'deposit_id' => $depositId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while retrieving deposit transaction', [
                'deposit_id' => $depositId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * List deposit transactions with pagination and filtering
     *
     * Retrieves a paginated list of deposit transactions with optional filtering
     * by status, currency, or date range.
     *
     * @param int $page Page number (1-based)
     * @param int $limit Number of results per page (max 100)
     * @param array<string, mixed> $filters Optional filters:
     *                                      - status: Filter by transaction status
     *                                      - currency: Filter by currency code
     *                                      - from_date: Start date (ISO 8601)
     *                                      - to_date: End date (ISO 8601)
     *                                      - account_id: Filter by account ID
     *
     * @return array{
     *     data: Transaction[],
     *     pagination: array{
     *         page: int,
     *         limit: int,
     *         total: int,
     *         pages: int
     *     }
     * } Paginated list of deposit transactions
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example List recent deposits with pagination
     * ```php
     * $filters = [
     *     'status' => 'completed',
     *     'currency' => 'BRL',
     *     'from_date' => '2023-06-01T00:00:00Z'
     * ];
     *
     * $result = $depositResource->listDeposits(1, 20, $filters);
     *
     * echo "Found {$result['pagination']['total']} deposits\n";
     * foreach ($result['data'] as $deposit) {
     *     echo "Deposit {$deposit->id}: {$deposit->getFormattedAmount()}\n";
     * }
     * ```
     */
    public function listDeposits(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $this->logger->info('Listing deposit transactions', [
            'page' => $page,
            'limit' => $limit,
            'filters' => $this->maskSensitiveFilters($filters)
        ]);

        try {
            $queryParams = [
                'page' => max(1, $page),
                'limit' => min(100, max(1, $limit))
            ];

            // Add filters to query parameters
            if (isset($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            if (isset($filters['currency'])) {
                $queryParams['currency'] = $filters['currency'];
            }
            if (isset($filters['from_date'])) {
                $queryParams['from_date'] = $filters['from_date'];
            }
            if (isset($filters['to_date'])) {
                $queryParams['to_date'] = $filters['to_date'];
            }
            if (isset($filters['account_id'])) {
                $queryParams['account_id'] = $filters['account_id'];
            }

            $endpoint = self::ENDPOINT_DEPOSITS . '?' . http_build_query($queryParams);
            $response = $this->xgateClient->get($endpoint);
            $responseData = json_decode($response->getBody()->getContents(), true);

            // Convert transaction data to Transaction DTOs
            $transactions = [];
            foreach ($responseData['data'] ?? [] as $transactionData) {
                $transactions[] = Transaction::fromArray($transactionData);
            }

            $result = [
                'data' => $transactions,
                'pagination' => $responseData['pagination'] ?? [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($transactions),
                    'pages' => 1
                ]
            ];

            $this->logger->info('Successfully listed deposit transactions', [
                'count' => count($transactions),
                'page' => $page,
                'total' => $result['pagination']['total']
            ]);

            return $result;
        } catch (ApiException $e) {
            $this->logger->error('API error while listing deposit transactions', [
                'page' => $page,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while listing deposit transactions', [
                'page' => $page,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Search deposit transactions by reference ID or description
     *
     * Searches for deposit transactions using reference ID or description text.
     * Useful for finding transactions related to specific orders or invoices.
     *
     * @param string $query Search query (reference ID or description text)
     * @param int $limit Maximum number of results to return (max 50)
     *
     * @return Transaction[] Array of matching deposit transactions
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Search deposits by reference
     * ```php
     * $deposits = $depositResource->searchDeposits('invoice_123', 10);
     *
     * if (empty($deposits)) {
     *     echo "No deposits found for invoice_123";
     * } else {
     *     echo "Found " . count($deposits) . " deposits";
     *     foreach ($deposits as $deposit) {
     *         echo "Deposit: {$deposit->getDisplayName()}\n";
     *     }
     * }
     * ```
     */
    public function searchDeposits(string $query, int $limit = 20): array
    {
        $this->logger->info('Searching deposit transactions', [
            'query' => $this->maskSensitiveData($query),
            'limit' => $limit
        ]);

        try {
            $queryParams = [
                'q' => $query,
                'limit' => min(50, max(1, $limit))
            ];

            $endpoint = self::ENDPOINT_DEPOSITS . '/search?' . http_build_query($queryParams);
            $response = $this->xgateClient->get($endpoint);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $transactions = [];
            foreach ($responseData['data'] ?? [] as $transactionData) {
                $transactions[] = Transaction::fromArray($transactionData);
            }

            $this->logger->info('Successfully searched deposit transactions', [
                'query' => $this->maskSensitiveData($query),
                'count' => count($transactions)
            ]);

            return $transactions;
        } catch (ApiException $e) {
            $this->logger->error('API error while searching deposit transactions', [
                'query' => $this->maskSensitiveData($query),
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while searching deposit transactions', [
                'query' => $this->maskSensitiveData($query),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mask sensitive data for logging purposes
     *
     * @param string|null $data Data to mask
     * @return string|null Masked data or null if input was null
     */
    private function maskSensitiveData(?string $data): ?string
    {
        if ($data === null || strlen($data) <= 4) {
            return $data;
        }

        return substr($data, 0, 2) . str_repeat('*', strlen($data) - 4) . substr($data, -2);
    }

    /**
     * Mask sensitive data in filter arrays for logging
     *
     * @param array<string, mixed> $filters Filters to mask
     * @return array<string, mixed> Masked filters
     */
    private function maskSensitiveFilters(array $filters): array
    {
        $masked = $filters;
        
        if (isset($masked['account_id'])) {
            $masked['account_id'] = $this->maskSensitiveData((string) $masked['account_id']);
        }

        return $masked;
    }
} 