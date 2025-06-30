<?php

declare(strict_types=1);

namespace XGate\Resource;

use XGate\Http\HttpClient;
use XGate\Model\Transaction;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use Psr\Log\LoggerInterface;

/**
 * WithdrawResource handles all withdrawal-related operations via XGATE API.
 * 
 * This class provides methods to manage fiat withdrawal transactions, including
 * creating withdrawal requests, retrieving withdrawal status, listing withdrawals
 * with pagination and filtering, and searching withdrawals by reference or description.
 * 
 * Security Features:
 * - Automatic masking of sensitive data in logs (amounts, account IDs)
 * - Comprehensive error handling with ApiException and NetworkException
 * - Input validation and sanitization
 * - Structured logging for audit trails
 * 
 * @package XGate\Resource
 * @author XGATE SDK Team
 * @since 1.0.0
 * 
 * @example
 * ```php
 * use XGate\Resource\WithdrawResource;
 * use XGate\Model\Transaction;
 * 
 * $withdrawResource = new WithdrawResource($httpClient, $logger);
 * 
 * // List supported currencies for withdrawals
 * $currencies = $withdrawResource->listSupportedCurrencies();
 * 
 * // Create a new withdrawal request
 * $transaction = new Transaction(
 *     id: null,
 *     amount: '500.00',
 *     currency: 'USD',
 *     accountId: 'acc_123456',
 *     paymentMethod: 'bank_transfer',
 *     type: 'withdrawal',
 *     referenceId: 'withdraw_001',
 *     description: 'Monthly profit withdrawal'
 * );
 * $result = $withdrawResource->createWithdrawal($transaction);
 * 
 * // Get withdrawal status
 * $withdrawal = $withdrawResource->getWithdrawal('txn_98765');
 * 
 * // List withdrawals with filters
 * $withdrawals = $withdrawResource->listWithdrawals(1, 20, [
 *     'status' => 'completed',
 *     'currency' => 'USD',
 *     'from_date' => '2023-06-01T00:00:00Z'
 * ]);
 * 
 * // Search withdrawals
 * $results = $withdrawResource->searchWithdrawals('profit_withdrawal');
 * ```
 */
class WithdrawResource
{
    private HttpClient $httpClient;
    private LoggerInterface $logger;

    /**
     * Constructor for WithdrawResource.
     * 
     * @param HttpClient $httpClient HTTP client for API communication
     * @param LoggerInterface $logger Logger for audit trails and debugging
     */
    public function __construct(HttpClient $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * List all supported currencies for withdrawal operations.
     * 
     * Retrieves a list of fiat currencies that are supported for withdrawal
     * transactions through the XGATE API. This is useful for validation
     * before creating withdrawal requests.
     * 
     * @return array<string> List of supported currency codes (ISO 4217)
     * 
     * @throws ApiException When the API returns an error response
     * @throws NetworkException When network communication fails
     * 
     * @example
     * ```php
     * try {
     *     $currencies = $withdrawResource->listSupportedCurrencies();
     *     // Returns: ['USD', 'EUR', 'BRL', 'GBP', 'JPY']
     *     
     *     foreach ($currencies as $currency) {
     *         echo "Supported currency: {$currency}\n";
     *     }
     * } catch (ApiException $e) {
     *     echo "API Error: " . $e->getMessage();
     * } catch (NetworkException $e) {
     *     echo "Network Error: " . $e->getMessage();
     * }
     * ```
     */
    public function listSupportedCurrencies(): array
    {
        try {
            $this->logger->info('Retrieving supported currencies for withdrawals');

            $response = $this->httpClient->get('/withdrawals/currencies');
            $data = json_decode($response->getBody()->getContents(), true);

            $currencies = $data['currencies'] ?? [];
            
            $this->logger->info('Successfully retrieved supported currencies for withdrawals', [
                'count' => count($currencies),
                'currencies' => $currencies
            ]);

            return $currencies;
        } catch (ApiException $e) {
            $this->logger->error('Failed to retrieve supported currencies for withdrawals', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while retrieving supported currencies for withdrawals', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new withdrawal request.
     * 
     * Initiates a withdrawal transaction through the XGATE API. The transaction
     * will be processed asynchronously, and you can check its status using
     * getWithdrawal() method.
     * 
     * @param Transaction $transaction Transaction data for the withdrawal request
     * @return Transaction The created withdrawal transaction with assigned ID and status
     * 
     * @throws ApiException When the API returns an error (e.g., insufficient funds, invalid data)
     * @throws NetworkException When network communication fails
     * 
     * @example
     * ```php
     * $transaction = new Transaction(
     *     id: null,
     *     amount: '1000.00',
     *     currency: 'USD',
     *     accountId: 'acc_789',
     *     paymentMethod: 'wire_transfer',
     *     type: 'withdrawal',
     *     referenceId: 'monthly_payout_001',
     *     description: 'Monthly profit distribution',
     *     callbackUrl: 'https://myapp.com/webhooks/withdrawal'
     * );
     * 
     * try {
     *     $result = $withdrawResource->createWithdrawal($transaction);
     *     echo "Withdrawal created with ID: {$result->id}\n";
     *     echo "Status: {$result->status}\n";
     *     echo "Amount: {$result->getFormattedAmount()}\n";
     * } catch (ApiException $e) {
     *     echo "Withdrawal failed: " . $e->getMessage();
     * }
     * ```
     */
    public function createWithdrawal(Transaction $transaction): Transaction
    {
        try {
            // Log the operation with masked sensitive data
            $maskedData = $this->maskSensitiveData($transaction->toArray());
            $this->logger->info('Creating new withdrawal transaction', $maskedData);

            $response = $this->httpClient->post('/withdrawals', ['json' => $transaction->toArray()]);
            $data = json_decode($response->getBody()->getContents(), true);

            $result = Transaction::fromArray($data);

            // Log success with masked data
            $this->logger->info('Successfully created withdrawal transaction', [
                'transaction_id' => $result->id,
                'amount' => $this->maskAmount($result->amount),
                'currency' => $result->currency,
                'status' => $result->status
            ]);

            return $result;
        } catch (ApiException $e) {
            $this->logger->error('Failed to create withdrawal transaction', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'amount' => $this->maskAmount($transaction->amount),
                'currency' => $transaction->currency
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while creating withdrawal transaction', [
                'error' => $e->getMessage(),
                'amount' => $this->maskAmount($transaction->amount),
                'currency' => $transaction->currency
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a specific withdrawal transaction by ID.
     * 
     * Fetches the current status and details of a withdrawal transaction
     * using its unique identifier.
     * 
     * @param string $withdrawalId Unique identifier of the withdrawal transaction
     * @return Transaction The withdrawal transaction details
     * 
     * @throws ApiException When the withdrawal is not found or API error occurs
     * @throws NetworkException When network communication fails
     * 
     * @example
     * ```php
     * try {
     *     $withdrawal = $withdrawResource->getWithdrawal('txn_abc123');
     *     
     *     echo "Withdrawal ID: {$withdrawal->id}\n";
     *     echo "Amount: {$withdrawal->getFormattedAmount()}\n";
     *     echo "Status: {$withdrawal->status}\n";
     *     echo "Created: {$withdrawal->createdAt?->format('Y-m-d H:i:s')}\n";
     *     
     *     if ($withdrawal->isCompleted()) {
     *         echo "Withdrawal completed successfully!\n";
     *     } elseif ($withdrawal->isPending()) {
     *         echo "Withdrawal is still being processed...\n";
     *     } elseif ($withdrawal->isFailed()) {
     *         echo "Withdrawal failed.\n";
     *     }
     * } catch (ApiException $e) {
     *     if ($e->getCode() === 404) {
     *         echo "Withdrawal not found.";
     *     } else {
     *         echo "API Error: " . $e->getMessage();
     *     }
     * }
     * ```
     */
    public function getWithdrawal(string $withdrawalId): Transaction
    {
        try {
            $this->logger->info('Retrieving withdrawal transaction', [
                'withdrawal_id' => $withdrawalId
            ]);

            $response = $this->httpClient->get('/withdrawals/' . $withdrawalId);
            $data = json_decode($response->getBody()->getContents(), true);

            $transaction = Transaction::fromArray($data);

            $this->logger->info('Successfully retrieved withdrawal transaction', [
                'withdrawal_id' => $withdrawalId,
                'amount' => $this->maskAmount($transaction->amount),
                'currency' => $transaction->currency,
                'status' => $transaction->status
            ]);

            return $transaction;
        } catch (ApiException $e) {
            $this->logger->error('Failed to retrieve withdrawal transaction', [
                'withdrawal_id' => $withdrawalId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while retrieving withdrawal transaction', [
                'withdrawal_id' => $withdrawalId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * List withdrawal transactions with pagination and filtering.
     * 
     * Retrieves a paginated list of withdrawal transactions with optional
     * filtering by status, currency, date range, and other criteria.
     * 
     * @param int $page Page number (1-based, default: 1)
     * @param int $limit Number of items per page (1-100, default: 20)
     * @param array<string, mixed> $filters Optional filters for the query
     * @return array{data: Transaction[], pagination: array{page: int, limit: int, total: int, pages: int}}
     * 
     * @throws ApiException When the API returns an error response
     * @throws NetworkException When network communication fails
     * 
     * Available filters:
     * - status: Filter by transaction status ('pending', 'completed', 'failed', 'cancelled')
     * - currency: Filter by currency code (e.g., 'USD', 'EUR')
     * - account_id: Filter by account identifier
     * - from_date: Filter transactions from this date (ISO 8601 format)
     * - to_date: Filter transactions until this date (ISO 8601 format)
     * - payment_method: Filter by payment method ('bank_transfer', 'wire_transfer', etc.)
     * - min_amount: Filter by minimum amount
     * - max_amount: Filter by maximum amount
     * 
     * @example
     * ```php
     * // List recent withdrawals with default pagination
     * $result = $withdrawResource->listWithdrawals();
     * echo "Found {$result['pagination']['total']} withdrawals\n";
     * 
     * foreach ($result['data'] as $withdrawal) {
     *     echo "- {$withdrawal->id}: {$withdrawal->getFormattedAmount()} ({$withdrawal->status})\n";
     * }
     * 
     * // List completed USD withdrawals from last month
     * $filters = [
     *     'status' => 'completed',
     *     'currency' => 'USD',
     *     'from_date' => '2023-05-01T00:00:00Z',
     *     'to_date' => '2023-05-31T23:59:59Z'
     * ];
     * $result = $withdrawResource->listWithdrawals(1, 50, $filters);
     * ```
     */
    public function listWithdrawals(int $page = 1, int $limit = 20, array $filters = []): array
    {
        try {
            // Validate and sanitize parameters
            $page = max(1, $page);
            $limit = max(1, min(100, $limit));

            // Build query parameters
            $queryParams = array_merge([
                'page' => $page,
                'limit' => $limit
            ], $filters);

            $queryString = http_build_query($queryParams);
            
            $this->logger->info('Listing withdrawal transactions', [
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters
            ]);

            $response = $this->httpClient->get('/withdrawals?' . $queryString);
            $data = json_decode($response->getBody()->getContents(), true);

            // Convert transaction data to Transaction objects
            $transactions = [];
            foreach ($data['data'] ?? [] as $transactionData) {
                $transactions[] = Transaction::fromArray($transactionData);
            }

            $result = [
                'data' => $transactions,
                'pagination' => $data['pagination'] ?? [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($transactions),
                    'pages' => 1
                ]
            ];

            $this->logger->info('Successfully listed withdrawal transactions', [
                'count' => count($transactions),
                'page' => $page,
                'total' => $result['pagination']['total']
            ]);

            return $result;
        } catch (ApiException $e) {
            $this->logger->error('Failed to list withdrawal transactions', [
                'page' => $page,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while listing withdrawal transactions', [
                'page' => $page,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Search withdrawal transactions by reference ID or description.
     * 
     * Performs a text search across withdrawal transactions, matching against
     * reference IDs, descriptions, and other searchable fields.
     * 
     * @param string $query Search query (reference ID, description keywords, etc.)
     * @param int $limit Maximum number of results to return (1-50, default: 20)
     * @return Transaction[] Array of matching withdrawal transactions
     * 
     * @throws ApiException When the API returns an error response
     * @throws NetworkException When network communication fails
     * 
     * @example
     * ```php
     * // Search for withdrawals by reference ID
     * $withdrawals = $withdrawResource->searchWithdrawals('monthly_payout');
     * 
     * foreach ($withdrawals as $withdrawal) {
     *     echo "Found: {$withdrawal->id} - {$withdrawal->description}\n";
     *     echo "Reference: {$withdrawal->referenceId}\n";
     *     echo "Amount: {$withdrawal->getFormattedAmount()}\n\n";
     * }
     * 
     * // Search with custom limit
     * $recentWithdrawals = $withdrawResource->searchWithdrawals('profit', 10);
     * ```
     */
    public function searchWithdrawals(string $query, int $limit = 20): array
    {
        try {
            // Validate and sanitize parameters
            $limit = max(1, min(50, $limit));
            
            $queryParams = [
                'q' => $query,
                'limit' => $limit
            ];

            $queryString = http_build_query($queryParams);

            $this->logger->info('Searching withdrawal transactions', [
                'query' => $query,
                'limit' => $limit
            ]);

            $response = $this->httpClient->get('/withdrawals/search?' . $queryString);
            $data = json_decode($response->getBody()->getContents(), true);

            // Convert transaction data to Transaction objects
            $transactions = [];
            foreach ($data['data'] ?? [] as $transactionData) {
                $transactions[] = Transaction::fromArray($transactionData);
            }

            $this->logger->info('Successfully searched withdrawal transactions', [
                'query' => $query,
                'results_count' => count($transactions)
            ]);

            return $transactions;
        } catch (ApiException $e) {
            $this->logger->error('Failed to search withdrawal transactions', [
                'query' => $query,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while searching withdrawal transactions', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mask sensitive data for logging purposes.
     * 
     * This method masks sensitive financial information such as amounts,
     * account IDs, and payment details to prevent sensitive data from
     * appearing in logs while maintaining audit trail functionality.
     * 
     * @param array<string, mixed> $data Raw transaction data
     * @return array<string, mixed> Data with sensitive fields masked
     * 
     * @internal This method is used internally for security logging
     */
    private function maskSensitiveData(array $data): array
    {
        $masked = $data;
        
        // Mask amount (show first and last digit with asterisks in between)
        if (isset($masked['amount'])) {
            $masked['amount'] = $this->maskAmount($masked['amount']);
        }
        
        // Mask account ID (show first 4 and last 4 characters)
        if (isset($masked['account_id']) && strlen($masked['account_id']) > 8) {
            $accountId = $masked['account_id'];
            $masked['account_id'] = substr($accountId, 0, 4) . str_repeat('*', strlen($accountId) - 8) . substr($accountId, -4);
        }
        
        // Mask payment method details if they contain sensitive info
        if (isset($masked['payment_method']) && strlen($masked['payment_method']) > 6) {
            $method = $masked['payment_method'];
            $masked['payment_method'] = substr($method, 0, 3) . '***' . substr($method, -3);
        }

        return $masked;
    }

    /**
     * Mask amount values for secure logging.
     * 
     * @param string $amount Original amount value
     * @return string Masked amount value
     * 
     * @internal This method is used internally for security logging
     */
    private function maskAmount(string $amount): string
    {
        if (strlen($amount) <= 2) {
            return str_repeat('*', strlen($amount));
        }
        
        return $amount[0] . str_repeat('*', strlen($amount) - 2) . substr($amount, -1);
    }
} 