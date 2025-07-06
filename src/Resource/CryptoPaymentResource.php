<?php

declare(strict_types=1);

namespace XGate\Resource;

use Psr\Log\LoggerInterface;
use XGate\Http\HttpClient;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;

/**
 * CryptoPaymentResource handles cryptocurrency payment operations via XGATE API
 *
 * This class provides methods for managing cryptocurrency payment transactions,
 * including creating USDT payments, retrieving payment status, and handling
 * cryptocurrency-specific operations.
 *
 * @package XGate\Resource
 * @author  XGATE Development Team
 * @since   1.0.0
 *
 * @example Basic USDT payment creation
 * ```php
 * $cryptoResource = new CryptoPaymentResource($httpClient, $logger);
 *
 * // Create a new USDT payment
 * $paymentData = [
 *     'amount' => 100.50,
 *     'currency' => 'BRL',
 *     'crypto_currency' => 'USDT',
 *     'network' => 'TRC20',
 *     'client_id' => 'client_123',
 *     'order_id' => 'order_456',
 *     'description' => 'Product payment'
 * ];
 *
 * $result = $cryptoResource->createPayment($paymentData);
 * echo "Payment created with ID: " . $result['payment_id'];
 * ```
 */
class CryptoPaymentResource
{
    private const ENDPOINT_CRYPTO_PAYMENTS = '/crypto/payments';
    private const ENDPOINT_PAYMENT_STATUS = '/crypto/payments/{payment_id}/status';

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create a new cryptocurrency payment
     *
     * Initiates a new cryptocurrency payment transaction with the provided details.
     * The payment will generate a wallet address and QR code for the user to complete
     * the transaction.
     *
     * @param array $paymentData Payment data including amount, currency, crypto details
     *
     * @return array The created payment with wallet address, QR code, and transaction details
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Create a USDT payment
     * ```php
     * $paymentData = [
     *     'amount' => 250.00,
     *     'currency' => 'BRL',
     *     'crypto_currency' => 'USDT',
     *     'network' => 'TRC20',
     *     'client_id' => 'client_456',
     *     'order_id' => 'order_789',
     *     'description' => 'Payment for services',
     *     'callback_url' => 'https://myapp.com/webhooks/crypto',
     *     'expiration_minutes' => 15
     * ];
     *
     * $result = $cryptoResource->createPayment($paymentData);
     * 
     * // Returns:
     * // [
     * //     'payment_id' => 'pay_abc123',
     * //     'wallet_address' => 'TQn9Y2khEsLMWD...',
     * //     'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...',
     * //     'amount_crypto' => 45.23,
     * //     'amount_fiat' => 250.00,
     * //     'currency' => 'BRL',
     * //     'crypto_currency' => 'USDT',
     * //     'network' => 'TRC20',
     * //     'exchange_rate' => 5.52,
     * //     'status' => 'pending',
     * //     'expires_at' => '2025-01-06T15:30:00Z',
     * //     'created_at' => '2025-01-06T15:15:00Z'
     * // ]
     * ```
     */
    public function createPayment(array $paymentData): array
    {
        $this->logger->info('Creating new cryptocurrency payment', [
            'amount' => $this->maskSensitiveData((string) $paymentData['amount']),
            'currency' => $paymentData['currency'] ?? 'BRL',
            'crypto_currency' => $paymentData['crypto_currency'] ?? 'USDT',
            'network' => $paymentData['network'] ?? 'TRC20',
            'client_id' => $this->maskSensitiveData($paymentData['client_id'] ?? '')
        ]);

        try {
            $response = $this->httpClient->post(self::ENDPOINT_CRYPTO_PAYMENTS, ['json' => $paymentData]);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $this->logger->info('Successfully created cryptocurrency payment', [
                'payment_id' => $responseData['payment_id'] ?? 'unknown',
                'status' => $responseData['status'] ?? 'unknown',
                'crypto_currency' => $responseData['crypto_currency'] ?? 'USDT',
                'amount_crypto' => $this->maskSensitiveData((string) ($responseData['amount_crypto'] ?? 0)),
                'wallet_address' => $this->maskSensitiveData($responseData['wallet_address'] ?? '')
            ]);

            return $responseData;
        } catch (ApiException $e) {
            $this->logger->error('API error while creating cryptocurrency payment', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'amount' => $this->maskSensitiveData((string) $paymentData['amount']),
                'crypto_currency' => $paymentData['crypto_currency'] ?? 'USDT'
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while creating cryptocurrency payment', [
                'error' => $e->getMessage(),
                'amount' => $this->maskSensitiveData((string) $paymentData['amount']),
                'crypto_currency' => $paymentData['crypto_currency'] ?? 'USDT'
            ]);
            throw $e;
        }
    }

    /**
     * Get payment status by payment ID
     *
     * Retrieves the current status and details of a cryptocurrency payment.
     * Use this method to check if a payment has been completed or is still pending.
     *
     * @param string $paymentId Unique identifier of the payment
     *
     * @return array Payment status with transaction details
     *
     * @throws ApiException If the API returns an error response or payment not found
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Check payment status
     * ```php
     * $paymentId = 'pay_abc123';
     * $status = $cryptoResource->getPaymentStatus($paymentId);
     *
     * // Returns:
     * // [
     * //     'payment_id' => 'pay_abc123',
     * //     'status' => 'completed', // 'pending', 'completed', 'expired', 'failed'
     * //     'transaction_hash' => '0x1234567890abcdef...',
     * //     'confirmations' => 12,
     * //     'required_confirmations' => 6,
     * //     'amount_received' => 45.23,
     * //     'amount_expected' => 45.23,
     * //     'wallet_address' => 'TQn9Y2khEsLMWD...',
     * //     'network' => 'TRC20',
     * //     'completed_at' => '2025-01-06T15:25:00Z'
     * // ]
     *
     * if ($status['status'] === 'completed') {
     *     echo "Payment completed successfully!";
     * }
     * ```
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $this->logger->info('Getting cryptocurrency payment status', [
            'payment_id' => $paymentId
        ]);

        try {
            $endpoint = str_replace('{payment_id}', $paymentId, self::ENDPOINT_PAYMENT_STATUS);
            $response = $this->httpClient->get($endpoint);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $this->logger->info('Successfully retrieved cryptocurrency payment status', [
                'payment_id' => $paymentId,
                'status' => $responseData['status'] ?? 'unknown',
                'confirmations' => $responseData['confirmations'] ?? 0,
                'transaction_hash' => $this->maskSensitiveData($responseData['transaction_hash'] ?? '')
            ]);

            return $responseData;
        } catch (ApiException $e) {
            $this->logger->error('API error while getting cryptocurrency payment status', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'payment_id' => $paymentId
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while getting cryptocurrency payment status', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);
            throw $e;
        }
    }

    /**
     * List cryptocurrency payments with filters
     *
     * Retrieves a paginated list of cryptocurrency payments with optional filters.
     *
     * @param int $page Page number (starting from 1)
     * @param int $limit Number of payments per page (max 100)
     * @param array $filters Optional filters (status, crypto_currency, date_from, date_to)
     *
     * @return array Paginated list of payments
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example List payments with filters
     * ```php
     * $payments = $cryptoResource->listPayments(1, 20, [
     *     'status' => 'completed',
     *     'crypto_currency' => 'USDT',
     *     'date_from' => '2025-01-01',
     *     'date_to' => '2025-01-06'
     * ]);
     *
     * foreach ($payments['data'] as $payment) {
     *     echo "Payment {$payment['payment_id']}: {$payment['status']}\n";
     * }
     * ```
     */
    public function listPayments(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $this->logger->info('Listing cryptocurrency payments', [
            'page' => $page,
            'limit' => $limit,
            'filters' => $this->maskSensitiveFilters($filters)
        ]);

        try {
            $queryParams = array_merge([
                'page' => $page,
                'limit' => min($limit, 100) // Limit to 100 per page
            ], $filters);

            $endpoint = self::ENDPOINT_CRYPTO_PAYMENTS . '?' . http_build_query($queryParams);
            $response = $this->httpClient->get($endpoint);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $this->logger->info('Successfully retrieved cryptocurrency payments list', [
                'page' => $page,
                'limit' => $limit,
                'total' => $responseData['total'] ?? 0,
                'count' => count($responseData['data'] ?? [])
            ]);

            return $responseData;
        } catch (ApiException $e) {
            $this->logger->error('API error while listing cryptocurrency payments', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'page' => $page,
                'limit' => $limit
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while listing cryptocurrency payments', [
                'error' => $e->getMessage(),
                'page' => $page,
                'limit' => $limit
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a pending cryptocurrency payment
     *
     * Cancels a cryptocurrency payment that is still pending.
     * Only payments in 'pending' status can be cancelled.
     *
     * @param string $paymentId Unique identifier of the payment to cancel
     *
     * @return array Cancellation result
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Cancel a payment
     * ```php
     * $result = $cryptoResource->cancelPayment('pay_abc123');
     * 
     * if ($result['cancelled']) {
     *     echo "Payment cancelled successfully";
     * }
     * ```
     */
    public function cancelPayment(string $paymentId): array
    {
        $this->logger->info('Cancelling cryptocurrency payment', [
            'payment_id' => $paymentId
        ]);

        try {
            $endpoint = self::ENDPOINT_CRYPTO_PAYMENTS . '/' . $paymentId . '/cancel';
            $response = $this->httpClient->post($endpoint);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $this->logger->info('Successfully cancelled cryptocurrency payment', [
                'payment_id' => $paymentId,
                'cancelled' => $responseData['cancelled'] ?? false
            ]);

            return $responseData;
        } catch (ApiException $e) {
            $this->logger->error('API error while cancelling cryptocurrency payment', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'payment_id' => $paymentId
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while cancelling cryptocurrency payment', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);
            throw $e;
        }
    }

    /**
     * Mask sensitive data for logging
     *
     * @param string|null $data Data to mask
     * @return string|null Masked data
     */
    private function maskSensitiveData(?string $data): ?string
    {
        if (!$data || strlen($data) <= 8) {
            return $data ? str_repeat('*', strlen($data)) : null;
        }

        return substr($data, 0, 4) . str_repeat('*', strlen($data) - 8) . substr($data, -4);
    }

    /**
     * Mask sensitive data in filter arrays
     *
     * @param array $filters Filters to mask
     * @return array Masked filters
     */
    private function maskSensitiveFilters(array $filters): array
    {
        $masked = $filters;
        
        // Mask sensitive filter values
        $sensitiveKeys = ['client_id', 'wallet_address', 'transaction_hash'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($masked[$key])) {
                $masked[$key] = $this->maskSensitiveData($masked[$key]);
            }
        }
        
        return $masked;
    }
} 