<?php

declare(strict_types=1);

namespace XGate\Resource;

use Psr\Log\LoggerInterface;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;

/**
 * ExchangeRateResource handles currency exchange rate operations via XGATE API
 *
 * This class provides methods for retrieving real-time exchange rates between
 * different currencies, including fiat to cryptocurrency conversions.
 *
 * @package XGate\Resource
 * @author  XGATE Development Team
 * @since   1.0.1
 *
 * @example Basic exchange rate retrieval
 * ```php
 * $exchangeResource = new ExchangeRateResource($xgateClient, $logger);
 *
 * // Get BRL to USDT exchange rate
 * $rate = $exchangeResource->getExchangeRate('BRL', 'USDT');
 * echo "1 USDT = " . $rate['rate'] . " BRL";
 *
 * // Get multiple rates at once
 * $rates = $exchangeResource->getMultipleRates(['BRL', 'USD'], ['USDT', 'BTC']);
 * ```
 */
class ExchangeRateResource
{
    private const ENDPOINT_EXCHANGE_RATES = '/exchange-rates';
    private const ENDPOINT_CRYPTO_RATES = '/crypto/rates';
    private const ENDPOINT_DEPOSIT_CONVERSION = '/deposit/conversion/tether';
    private const ENDPOINT_COMPANY_CURRENCIES = '/deposit/company/currencies';

    public function __construct(
        private readonly \XGate\XGateClient $xgateClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get exchange rate between two currencies
     *
     * Retrieves the current exchange rate from one currency to another.
     * Supports both fiat-to-fiat and fiat-to-crypto conversions.
     *
     * @param string $fromCurrency Source currency code (e.g., 'BRL', 'USD')
     * @param string $toCurrency Target currency code (e.g., 'USDT', 'BTC')
     *
     * @return array Exchange rate data with rate, timestamp, and metadata
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Get BRL to USDT rate
     * ```php
     * $rate = $exchangeResource->getExchangeRate('BRL', 'USDT');
     * // Returns: [
     * //     'rate' => 5.45,
     * //     'from_currency' => 'BRL',
     * //     'to_currency' => 'USDT',
     * //     'timestamp' => '2025-01-06T10:30:00Z',
     * //     'source' => 'coinmarketcap',
     * //     'expires_at' => '2025-01-06T10:35:00Z'
     * // ]
     * ```
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): array
    {
        $this->logger->info('Getting exchange rate', [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency
        ]);

        try {
            $endpoint = self::ENDPOINT_EXCHANGE_RATES . '/' . strtoupper($fromCurrency) . '/' . strtoupper($toCurrency);
            $data = $this->xgateClient->get($endpoint);

            $this->logger->info('Successfully retrieved exchange rate', [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'rate' => $data['rate'] ?? 'unknown'
            ]);

            return $data;
        } catch (ApiException $e) {
            $this->logger->error('API error while getting exchange rate', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while getting exchange rate', [
                'error' => $e->getMessage(),
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]);
            throw $e;
        }
    }

    /**
     * Get multiple exchange rates at once
     *
     * Retrieves exchange rates for multiple currency pairs in a single request.
     * More efficient than making individual calls for each pair.
     *
     * @param array<string> $fromCurrencies Array of source currency codes
     * @param array<string> $toCurrencies Array of target currency codes
     *
     * @return array Array of exchange rate data for all combinations
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Get multiple rates
     * ```php
     * $rates = $exchangeResource->getMultipleRates(['BRL', 'USD'], ['USDT', 'BTC']);
     * // Returns: [
     * //     'BRL_USDT' => ['rate' => 5.45, ...],
     * //     'BRL_BTC' => ['rate' => 0.000018, ...],
     * //     'USD_USDT' => ['rate' => 1.00, ...],
     * //     'USD_BTC' => ['rate' => 0.000023, ...]
     * // ]
     * ```
     */
    public function getMultipleRates(array $fromCurrencies, array $toCurrencies): array
    {
        $this->logger->info('Getting multiple exchange rates', [
            'from_currencies' => $fromCurrencies,
            'to_currencies' => $toCurrencies,
            'total_pairs' => count($fromCurrencies) * count($toCurrencies)
        ]);

        try {
            $requestData = [
                'from_currencies' => array_map('strtoupper', $fromCurrencies),
                'to_currencies' => array_map('strtoupper', $toCurrencies)
            ];

            $data = $this->xgateClient->post(self::ENDPOINT_EXCHANGE_RATES . '/batch', $requestData);

            $this->logger->info('Successfully retrieved multiple exchange rates', [
                'pairs_count' => count($data['rates'] ?? [])
            ]);

            return $data['rates'] ?? [];
        } catch (ApiException $e) {
            $this->logger->error('API error while getting multiple exchange rates', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'from_currencies' => $fromCurrencies,
                'to_currencies' => $toCurrencies
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while getting multiple exchange rates', [
                'error' => $e->getMessage(),
                'from_currencies' => $fromCurrencies,
                'to_currencies' => $toCurrencies
            ]);
            throw $e;
        }
    }

    /**
     * Get cryptocurrency rates with detailed market data
     *
     * Retrieves detailed exchange rate information for cryptocurrencies,
     * including market cap, volume, and price change data.
     *
     * @param string $cryptoCurrency Cryptocurrency symbol (e.g., 'USDT', 'BTC')
     * @param string $fiatCurrency Fiat currency code (e.g., 'BRL', 'USD')
     *
     * @return array Detailed cryptocurrency rate data
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Get detailed USDT rate
     * ```php
     * $rate = $exchangeResource->getCryptoRate('USDT', 'BRL');
     * // Returns: [
     * //     'rate' => 5.45,
     * //     'crypto_currency' => 'USDT',
     * //     'fiat_currency' => 'BRL',
     * //     'market_cap' => 120000000000,
     * //     'volume_24h' => 45000000000,
     * //     'change_24h' => 0.12,
     * //     'timestamp' => '2025-01-06T10:30:00Z'
     * // ]
     * ```
     */
    public function getCryptoRate(string $cryptoCurrency, string $fiatCurrency): array
    {
        $this->logger->info('Getting cryptocurrency rate', [
            'crypto_currency' => $cryptoCurrency,
            'fiat_currency' => $fiatCurrency
        ]);

        try {
            $endpoint = self::ENDPOINT_CRYPTO_RATES . '/' . strtoupper($cryptoCurrency) . '/' . strtoupper($fiatCurrency);
            $data = $this->xgateClient->get($endpoint);

            $this->logger->info('Successfully retrieved cryptocurrency rate', [
                'crypto_currency' => $cryptoCurrency,
                'fiat_currency' => $fiatCurrency,
                'rate' => $data['rate'] ?? 'unknown'
            ]);

            return $data;
        } catch (ApiException $e) {
            $this->logger->error('API error while getting cryptocurrency rate', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'crypto_currency' => $cryptoCurrency,
                'fiat_currency' => $fiatCurrency
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while getting cryptocurrency rate', [
                'error' => $e->getMessage(),
                'crypto_currency' => $cryptoCurrency,
                'fiat_currency' => $fiatCurrency
            ]);
            throw $e;
        }
    }

    /**
     * Get historical exchange rates for a specific period
     *
     * Retrieves historical exchange rate data for analysis and reporting.
     * Useful for calculating averages or tracking rate changes over time.
     *
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param string $startDate Start date in ISO 8601 format (YYYY-MM-DD)
     * @param string $endDate End date in ISO 8601 format (YYYY-MM-DD)
     * @param string $interval Data interval ('daily', 'hourly', 'weekly')
     *
     * @return array Historical exchange rate data
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Get historical rates
     * ```php
     * $history = $exchangeResource->getHistoricalRates('BRL', 'USDT', '2025-01-01', '2025-01-06', 'daily');
     * // Returns: [
     * //     'from_currency' => 'BRL',
     * //     'to_currency' => 'USDT',
     * //     'interval' => 'daily',
     * //     'data' => [
     * //         ['date' => '2025-01-01', 'rate' => 5.40],
     * //         ['date' => '2025-01-02', 'rate' => 5.42],
     * //         ...
     * //     ]
     * // ]
     * ```
     */
    public function getHistoricalRates(
        string $fromCurrency,
        string $toCurrency,
        string $startDate,
        string $endDate,
        string $interval = 'daily'
    ): array {
        $this->logger->info('Getting historical exchange rates', [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'interval' => $interval
        ]);

        try {
            $queryParams = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'interval' => $interval
            ];

            $endpoint = self::ENDPOINT_EXCHANGE_RATES . '/' . strtoupper($fromCurrency) . '/' . strtoupper($toCurrency) . '/history';
            $data = $this->xgateClient->get($endpoint, ['query' => $queryParams]);

            $this->logger->info('Successfully retrieved historical exchange rates', [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'data_points' => count($data['data'] ?? [])
            ]);

            return $data;
        } catch (ApiException $e) {
            $this->logger->error('API error while getting historical exchange rates', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while getting historical exchange rates', [
                'error' => $e->getMessage(),
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]);
            throw $e;
        }
    }

    /**
     * Convert amount from one currency to another
     *
     * Uses the official XGate API endpoint for USDT conversion.
     * This method follows the official documentation and requires company currencies.
     *
     * @param float $amount Amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     *
     * @return array Conversion result with converted amount and crypto currency
     *
     * @throws ApiException If the API returns an error response
     * @throws NetworkException If there's a network connectivity issue
     *
     * @example Convert BRL to USDT
     * ```php
     * $conversion = $exchangeResource->convertAmount(100.0, 'BRL', 'USDT');
     * // Returns: [
     * //     'amount' => 18.35,
     * //     'crypto' => 'USDT'
     * // ]
     * ```
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): array
    {
        $this->logger->info('Converting amount using official API', [
            'amount' => $amount,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency
        ]);

        try {
            // Primeiro, obter as moedas da empresa
            $currencies = $this->xgateClient->get(self::ENDPOINT_COMPANY_CURRENCIES);
            
            // Encontrar a moeda correspondente
            $currency = null;
            foreach ($currencies as $curr) {
                if (strtoupper($curr['name']) === strtoupper($fromCurrency)) {
                    $currency = $curr;
                    break;
                }
            }
            
            if (!$currency) {
                throw new ApiException("Currency '{$fromCurrency}' not found in company currencies");
            }

            // Fazer a conversão usando o endpoint oficial
            $conversionData = [
                'amount' => $amount,
                'currency' => $currency
            ];

            $result = $this->xgateClient->post(self::ENDPOINT_DEPOSIT_CONVERSION, $conversionData);

            $this->logger->info('Successfully converted amount using official API', [
                'original_amount' => $amount,
                'converted_amount' => $result['amount'] ?? 0,
                'crypto' => $result['crypto'] ?? 'USDT'
            ]);

            return $result;
        } catch (ApiException $e) {
            $this->logger->error('API error while converting amount', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'amount' => $amount,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]);
            throw $e;
        } catch (NetworkException $e) {
            $this->logger->error('Network error while converting amount', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]);
            throw $e;
        }
    }
} 