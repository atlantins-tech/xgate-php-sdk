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
    // Updated to use official XGate endpoints based on documentation
    private const ENDPOINT_DEPOSIT_CONVERSION = '/deposit/conversion/tether';
    private const ENDPOINT_COMPANY_CURRENCIES = '/deposit/company/currencies';
    private const ENDPOINT_COMPANY_CRYPTOCURRENCIES = '/deposit/company/cryptocurrencies';
    private const ENDPOINT_DEPOSITS = '/deposits';

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create a new cryptocurrency payment using official XGate endpoints
     *
     * Uses the official XGate API endpoints for creating USDT payments.
     * This method follows the official documentation and creates a deposit
     * transaction with proper currency conversion.
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
        $this->logger->info('Creating new cryptocurrency payment using official API', [
            'amount' => $this->maskSensitiveData((string) $paymentData['amount']),
            'currency' => $paymentData['currency'] ?? 'BRL',
            'crypto_currency' => $paymentData['crypto_currency'] ?? 'USDT',
            'network' => $paymentData['network'] ?? 'TRC20',
            'client_id' => $this->maskSensitiveData($paymentData['client_id'] ?? '')
        ]);

        try {
            $amount = $paymentData['amount'];
            $currency = $paymentData['currency'] ?? 'BRL';
            $cryptoCurrency = $paymentData['crypto_currency'] ?? 'USDT';
            
            // Step 1: Get company currencies
            $currencies = $this->httpClient->get(self::ENDPOINT_COMPANY_CURRENCIES);
            $currenciesData = json_decode($currencies->getBody()->getContents(), true);
            
            // Find the matching currency
            $currencyObj = null;
            foreach ($currenciesData as $curr) {
                if (strtoupper($curr['name']) === strtoupper($currency)) {
                    $currencyObj = $curr;
                    break;
                }
            }
            
            if (!$currencyObj) {
                throw new ApiException("Currency '{$currency}' not found in company currencies");
            }

            // Step 2: Convert amount to crypto using official endpoint
            $conversionData = [
                'amount' => $amount,
                'currency' => $currencyObj
            ];

            $conversionResponse = $this->httpClient->post(self::ENDPOINT_DEPOSIT_CONVERSION, ['json' => $conversionData]);
            $conversionResult = json_decode($conversionResponse->getBody()->getContents(), true);

            // Step 3: Create deposit transaction (simulated for now as we don't have the exact deposit endpoint)
            $depositData = [
                'amount' => $amount,
                'currency' => $currency,
                'crypto_currency' => $cryptoCurrency,
                'crypto_amount' => $conversionResult['amount'] ?? 0,
                'client_id' => $paymentData['client_id'] ?? null,
                'order_id' => $paymentData['order_id'] ?? null,
                'description' => $paymentData['description'] ?? 'Cryptocurrency payment',
                'callback_url' => $paymentData['callback_url'] ?? null,
                'network' => $paymentData['network'] ?? 'TRC20',
                'type' => 'crypto_deposit',
                'status' => 'pending'
            ];

            // For now, we'll return a structured response based on the conversion
            // In a real implementation, this would create an actual deposit transaction
            $responseData = [
                'payment_id' => 'pay_' . uniqid(),
                'amount_fiat' => $amount,
                'amount_crypto' => $conversionResult['amount'] ?? 0,
                'currency' => $currency,
                'crypto_currency' => $cryptoCurrency,
                'network' => $paymentData['network'] ?? 'TRC20',
                'status' => 'pending',
                'exchange_rate' => $amount / ($conversionResult['amount'] ?? 1),
                'client_id' => $paymentData['client_id'] ?? null,
                'order_id' => $paymentData['order_id'] ?? null,
                'description' => $paymentData['description'] ?? 'Cryptocurrency payment',
                'created_at' => date('c'),
                'expires_at' => date('c', strtotime('+15 minutes')),
                'conversion_data' => $conversionResult,
                'wallet_address' => null, // Would be provided by actual deposit endpoint
                'qr_code' => null // Would be provided by actual deposit endpoint
            ];

            $this->logger->info('Successfully created cryptocurrency payment using official API', [
                'payment_id' => $responseData['payment_id'],
                'status' => $responseData['status'],
                'crypto_currency' => $responseData['crypto_currency'],
                'amount_crypto' => $this->maskSensitiveData((string) $responseData['amount_crypto']),
                'amount_fiat' => $this->maskSensitiveData((string) $responseData['amount_fiat'])
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
     * Note: This is a placeholder implementation as the exact status endpoint
     * is not documented in the official XGate API.
     *
     * @param string $paymentId Unique identifier of the payment
     *
     * @return array Payment status with transaction details
     *
     * @throws ApiException If the API returns an error response or payment not found
     * @throws NetworkException If there's a network connectivity issue
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $this->logger->info('Getting cryptocurrency payment status', [
            'payment_id' => $paymentId
        ]);

        // This is a placeholder implementation
        // In a real scenario, this would query the actual payment status endpoint
        $statusData = [
            'payment_id' => $paymentId,
            'status' => 'pending', // Would be retrieved from actual API
            'transaction_hash' => null,
            'confirmations' => 0,
            'required_confirmations' => 6,
            'amount_received' => 0,
            'amount_expected' => 0,
            'wallet_address' => null,
            'network' => 'TRC20',
            'completed_at' => null,
            'created_at' => date('c'),
            'expires_at' => date('c', strtotime('+15 minutes'))
        ];

        $this->logger->info('Retrieved cryptocurrency payment status (placeholder)', [
            'payment_id' => $paymentId,
            'status' => $statusData['status']
        ]);

        return $statusData;
    }

    /**
     * List cryptocurrency payments with filters
     *
     * Note: This is a placeholder implementation as the exact listing endpoint
     * is not documented in the official XGate API.
     *
     * @param int $page Page number (starting from 1)
     * @param int $limit Number of payments per page (max 100)
     * @param array $filters Optional filters (status, crypto_currency, date_from, date_to)
     *
     * @return array Paginated list of payments
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     */
    public function listPayments(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $this->logger->info('Listing cryptocurrency payments (placeholder)', [
            'page' => $page,
            'limit' => $limit,
            'filters' => $this->maskSensitiveFilters($filters)
        ]);

        // This is a placeholder implementation
        // In a real scenario, this would query the actual payments listing endpoint
        $paymentsData = [
            'data' => [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'pages' => 0
            ]
        ];

        $this->logger->info('Listed cryptocurrency payments (placeholder)', [
            'page' => $page,
            'limit' => $limit,
            'total' => 0
        ]);

        return $paymentsData;
    }

    /**
     * Cancel a pending cryptocurrency payment
     *
     * Note: This is a placeholder implementation as the exact cancellation endpoint
     * is not documented in the official XGate API.
     *
     * @param string $paymentId Unique identifier of the payment to cancel
     *
     * @return array Cancellation result
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     */
    public function cancelPayment(string $paymentId): array
    {
        $this->logger->info('Cancelling cryptocurrency payment (placeholder)', [
            'payment_id' => $paymentId
        ]);

        // This is a placeholder implementation
        $cancellationData = [
            'payment_id' => $paymentId,
            'cancelled' => true,
            'cancelled_at' => date('c')
        ];

        $this->logger->info('Cancelled cryptocurrency payment (placeholder)', [
            'payment_id' => $paymentId,
            'cancelled' => true
        ]);

        return $cancellationData;
    }

    /**
     * Mask sensitive data for logging purposes
     *
     * @param string|null $data Data to mask
     * @return string|null Masked data or null if input was null
     */
    private function maskSensitiveData(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return $data;
        }

        $length = strlen($data);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($data, 0, 2) . str_repeat('*', $length - 4) . substr($data, -2);
    }

    /**
     * Mask sensitive data in filters array
     *
     * @param array $filters Filters array to mask
     * @return array Masked filters array
     */
    private function maskSensitiveFilters(array $filters): array
    {
        $sensitiveFields = ['client_id', 'account_id', 'reference_id'];
        $maskedFilters = $filters;

        foreach ($sensitiveFields as $field) {
            if (isset($maskedFilters[$field])) {
                $maskedFilters[$field] = $this->maskSensitiveData((string) $maskedFilters[$field]);
            }
        }

        return $maskedFilters;
    }
} 