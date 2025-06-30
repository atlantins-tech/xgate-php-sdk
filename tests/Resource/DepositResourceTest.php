<?php

declare(strict_types=1);

namespace XGate\Tests\Resource;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use XGate\Http\HttpClient;
use XGate\Model\Transaction;
use XGate\Resource\DepositResource;

/**
 * Test suite for DepositResource
 *
 * @covers \XGate\Resource\DepositResource
 */
class DepositResourceTest extends TestCase
{
    private MockObject|HttpClient $mockHttpClient;
    private MockObject|LoggerInterface $mockLogger;
    private DepositResource $depositResource;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(HttpClient::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->depositResource = new DepositResource($this->mockHttpClient, $this->mockLogger);
    }

    private function createJsonResponse(array $data): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('getContents')
            ->willReturn(json_encode($data));

        $response->method('getBody')
            ->willReturn($stream);

        return $response;
    }

    public function testListSupportedCurrenciesSuccess(): void
    {
        $responseData = [
            'currencies' => ['BRL', 'USD', 'EUR', 'GBP']
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/currencies')
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->depositResource->listSupportedCurrencies();

        $this->assertSame(['BRL', 'USD', 'EUR', 'GBP'], $result);
    }

    public function testListSupportedCurrenciesEmptyResponse(): void
    {
        $responseData = [];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/currencies')
            ->willReturn($response);

        $result = $this->depositResource->listSupportedCurrencies();

        $this->assertSame([], $result);
    }

    public function testListSupportedCurrenciesApiException(): void
    {
        $apiException = new ApiException('API Error', 400);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/currencies')
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('API Error');

        $this->depositResource->listSupportedCurrencies();
    }

    public function testListSupportedCurrenciesNetworkException(): void
    {
        $networkException = new NetworkException('Network Error');

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/currencies')
            ->willThrowException($networkException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Network Error');

        $this->depositResource->listSupportedCurrencies();
    }

    public function testCreateDepositSuccess(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '100.50',
            currency: 'BRL',
            accountId: 'acc_123',
            paymentMethod: 'bank_transfer',
            type: 'deposit',
            description: 'Test deposit'
        );

        $responseData = [
            'id' => 'txn_12345',
            'amount' => '100.50',
            'currency' => 'BRL',
            'account_id' => 'acc_123',
            'payment_method' => 'bank_transfer',
            'type' => 'deposit',
            'status' => 'pending',
            'description' => 'Test deposit',
            'created_at' => '2023-06-30T10:00:00+00:00'
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('post')
            ->with('/deposits', ['json' => $transaction->toArray()])
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->depositResource->createDeposit($transaction);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertSame('txn_12345', $result->id);
        $this->assertSame('100.50', $result->amount);
        $this->assertSame('BRL', $result->currency);
        $this->assertSame('pending', $result->status);
    }

    public function testCreateDepositApiException(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '100.50',
            currency: 'BRL',
            type: 'deposit'
        );

        $apiException = new ApiException('Validation Error', 400);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('post')
            ->with('/deposits', ['json' => $transaction->toArray()])
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Validation Error');

        $this->depositResource->createDeposit($transaction);
    }

    public function testCreateDepositNetworkException(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '100.50',
            currency: 'BRL',
            type: 'deposit'
        );

        $networkException = new NetworkException('Connection failed');

        $this->mockHttpClient
            ->expects($this->once())
            ->method('post')
            ->with('/deposits', ['json' => $transaction->toArray()])
            ->willThrowException($networkException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Connection failed');

        $this->depositResource->createDeposit($transaction);
    }

    public function testGetDepositSuccess(): void
    {
        $depositId = 'txn_12345';
        $responseData = [
            'id' => 'txn_12345',
            'amount' => '100.50',
            'currency' => 'BRL',
            'type' => 'deposit',
            'status' => 'completed',
            'created_at' => '2023-06-30T10:00:00+00:00',
            'completed_at' => '2023-06-30T10:05:00+00:00'
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/' . $depositId)
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->depositResource->getDeposit($depositId);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertSame('txn_12345', $result->id);
        $this->assertSame('100.50', $result->amount);
        $this->assertSame('BRL', $result->currency);
        $this->assertSame('completed', $result->status);
        $this->assertTrue($result->isCompleted());
    }

    public function testGetDepositApiException(): void
    {
        $depositId = 'txn_nonexistent';
        $apiException = new ApiException('Deposit not found', 404);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/' . $depositId)
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Deposit not found');

        $this->depositResource->getDeposit($depositId);
    }

    public function testGetDepositNetworkException(): void
    {
        $depositId = 'txn_12345';
        $networkException = new NetworkException('Timeout');

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/' . $depositId)
            ->willThrowException($networkException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Timeout');

        $this->depositResource->getDeposit($depositId);
    }

    public function testListDepositsWithDefaultParameters(): void
    {
        $responseData = [
            'data' => [
                [
                    'id' => 'txn_1',
                    'amount' => '100.00',
                    'currency' => 'BRL',
                    'type' => 'deposit',
                    'status' => 'completed'
                ],
                [
                    'id' => 'txn_2',
                    'amount' => '200.00',
                    'currency' => 'USD',
                    'type' => 'deposit',
                    'status' => 'pending'
                ]
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => 2,
                'pages' => 1
            ]
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits?page=1&limit=20')
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->depositResource->listDeposits();

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
        $this->assertInstanceOf(Transaction::class, $result['data'][0]);
        $this->assertSame('txn_1', $result['data'][0]->id);
        $this->assertSame(2, $result['pagination']['total']);
    }

    public function testListDepositsWithFilters(): void
    {
        $filters = [
            'status' => 'completed',
            'currency' => 'BRL',
            'from_date' => '2023-06-01T00:00:00Z',
            'to_date' => '2023-06-30T23:59:59Z',
            'account_id' => 'acc_123'
        ];

        $responseData = [
            'data' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => 0,
                'pages' => 0
            ]
        ];
        $response = $this->createJsonResponse($responseData);

        $expectedQuery = http_build_query([
            'page' => 1,
            'limit' => 50,
            'status' => 'completed',
            'currency' => 'BRL',
            'from_date' => '2023-06-01T00:00:00Z',
            'to_date' => '2023-06-30T23:59:59Z',
            'account_id' => 'acc_123'
        ]);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits?' . $expectedQuery)
            ->willReturn($response);

        $result = $this->depositResource->listDeposits(1, 50, $filters);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(0, $result['data']);
    }

    public function testListDepositsWithLimitValidation(): void
    {
        $responseData = [
            'data' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 100,
                'total' => 0,
                'pages' => 0
            ]
        ];
        $response = $this->createJsonResponse($responseData);

        // Test that limit is capped at 100
        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits?page=1&limit=100')
            ->willReturn($response);

        $result = $this->depositResource->listDeposits(1, 150); // Should be capped to 100

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testListDepositsApiException(): void
    {
        $apiException = new ApiException('Server error', 500);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Server error');

        $this->depositResource->listDeposits();
    }

    public function testSearchDepositsSuccess(): void
    {
        $query = 'invoice_123';
        $responseData = [
            'data' => [
                [
                    'id' => 'txn_1',
                    'amount' => '100.00',
                    'currency' => 'BRL',
                    'type' => 'deposit',
                    'status' => 'completed',
                    'reference_id' => 'invoice_123',
                    'description' => 'Payment for invoice 123'
                ]
            ]
        ];
        $response = $this->createJsonResponse($responseData);

        $expectedQuery = http_build_query([
            'q' => $query,
            'limit' => 20
        ]);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/search?' . $expectedQuery)
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->depositResource->searchDeposits($query);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Transaction::class, $result[0]);
        $this->assertSame('txn_1', $result[0]->id);
        $this->assertSame('invoice_123', $result[0]->referenceId);
    }

    public function testSearchDepositsWithCustomLimit(): void
    {
        $query = 'test';
        $limit = 10;
        $responseData = ['data' => []];
        $response = $this->createJsonResponse($responseData);

        $expectedQuery = http_build_query([
            'q' => $query,
            'limit' => $limit
        ]);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/search?' . $expectedQuery)
            ->willReturn($response);

        $result = $this->depositResource->searchDeposits($query, $limit);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testSearchDepositsLimitValidation(): void
    {
        $query = 'test';
        $responseData = ['data' => []];
        $response = $this->createJsonResponse($responseData);

        // Test that limit is capped at 50
        $expectedQuery = http_build_query([
            'q' => $query,
            'limit' => 50
        ]);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/deposits/search?' . $expectedQuery)
            ->willReturn($response);

        $result = $this->depositResource->searchDeposits($query, 100); // Should be capped to 50

        $this->assertIsArray($result);
    }

    public function testSearchDepositsApiException(): void
    {
        $query = 'test';
        $apiException = new ApiException('Search failed', 400);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Search failed');

        $this->depositResource->searchDeposits($query);
    }

    public function testSearchDepositsNetworkException(): void
    {
        $query = 'test';
        $networkException = new NetworkException('Network timeout');

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->willThrowException($networkException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Network timeout');

        $this->depositResource->searchDeposits($query);
    }

    public function testLoggingWithSensitiveDataMasking(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '100.50',
            currency: 'BRL',
            accountId: 'account_1234567890',
            type: 'deposit'
        );

        $responseData = [
            'id' => 'txn_12345',
            'amount' => '100.50',
            'currency' => 'BRL',
            'type' => 'deposit',
            'status' => 'pending'
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Verify that sensitive data is masked in logs
        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info')
            ->with($this->callback(function ($message, $context = null) {
                // Verificar se é uma das duas mensagens esperadas
                $validMessages = [
                    'Creating new deposit transaction',
                    'Successfully created deposit transaction'
                ];
                
                if (!in_array($message, $validMessages)) {
                    return false;
                }
                
                // Se tem contexto, verificar mascaramento
                if ($context !== null && is_array($context)) {
                    if (isset($context['amount'])) {
                        $this->assertStringContains('*', $context['amount']);
                    }
                    if (isset($context['account_id'])) {
                        $this->assertStringContains('*', $context['account_id']);
                    }
                    if (isset($context['transaction_id']) && $message === 'Successfully created deposit transaction') {
                        $this->assertArrayHasKey('transaction_id', $context);
                    }
                }
                
                return true;
            }));

        $this->depositResource->createDeposit($transaction);
    }
} 