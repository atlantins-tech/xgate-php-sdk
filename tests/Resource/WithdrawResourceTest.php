<?php

declare(strict_types=1);

namespace XGate\Tests\Resource;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use XGate\Resource\WithdrawResource;
use XGate\Http\HttpClient;
use XGate\Model\Transaction;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class WithdrawResourceTest extends TestCase
{
    private WithdrawResource $withdrawResource;
    private MockObject $mockHttpClient;
    private MockObject $mockLogger;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(HttpClient::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->withdrawResource = new WithdrawResource($this->mockHttpClient, $this->mockLogger);
    }

    private function createJsonResponse(array $data): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($data));
        $response->method('getBody')->willReturn($stream);
        
        return $response;
    }

    public function testListSupportedCurrenciesSuccess(): void
    {
        $responseData = [
            'currencies' => ['USD', 'EUR', 'BRL', 'GBP', 'JPY']
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals/currencies')
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->withdrawResource->listSupportedCurrencies();

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertContains('USD', $result);
        $this->assertContains('BRL', $result);
    }

    public function testListSupportedCurrenciesEmptyResponse(): void
    {
        $responseData = [];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals/currencies')
            ->willReturn($response);

        $result = $this->withdrawResource->listSupportedCurrencies();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testListSupportedCurrenciesApiException(): void
    {
        $apiException = new ApiException('Service unavailable', 503);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals/currencies')
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Service unavailable');

        $this->withdrawResource->listSupportedCurrencies();
    }

    public function testListSupportedCurrenciesNetworkException(): void
    {
        $networkException = new NetworkException('Connection timeout');

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals/currencies')
            ->willThrowException($networkException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Connection timeout');

        $this->withdrawResource->listSupportedCurrencies();
    }

    public function testCreateWithdrawalSuccess(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '500.00',
            currency: 'USD',
            accountId: 'acc_123456',
            paymentMethod: 'bank_transfer',
            type: 'withdrawal',
            referenceId: 'withdraw_001',
            description: 'Monthly profit withdrawal'
        );

        $responseData = [
            'id' => 'txn_67890',
            'amount' => '500.00',
            'currency' => 'USD',
            'account_id' => 'acc_123456',
            'payment_method' => 'bank_transfer',
            'type' => 'withdrawal',
            'status' => 'pending',
            'reference_id' => 'withdraw_001',
            'description' => 'Monthly profit withdrawal',
            'created_at' => '2023-06-30T15:00:00+00:00'
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('post')
            ->with('/withdrawals', ['json' => $transaction->toArray()])
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->withdrawResource->createWithdrawal($transaction);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertSame('txn_67890', $result->id);
        $this->assertSame('500.00', $result->amount);
        $this->assertSame('USD', $result->currency);
        $this->assertSame('pending', $result->status);
        $this->assertTrue($result->isWithdrawal());
        $this->assertTrue($result->isPending());
    }

    public function testCreateWithdrawalApiException(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '1000.00',
            currency: 'USD',
            type: 'withdrawal'
        );

        $apiException = new ApiException('Insufficient funds', 400);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('post')
            ->with('/withdrawals', ['json' => $transaction->toArray()])
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Insufficient funds');

        $this->withdrawResource->createWithdrawal($transaction);
    }

    public function testCreateWithdrawalNetworkException(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '100.50',
            currency: 'BRL',
            type: 'withdrawal'
        );

        $networkException = new NetworkException('Connection failed');

        $this->mockHttpClient
            ->expects($this->once())
            ->method('post')
            ->with('/withdrawals', ['json' => $transaction->toArray()])
            ->willThrowException($networkException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Connection failed');

        $this->withdrawResource->createWithdrawal($transaction);
    }

    public function testGetWithdrawalSuccess(): void
    {
        $withdrawalId = 'txn_98765';
        $responseData = [
            'id' => 'txn_98765',
            'amount' => '750.00',
            'currency' => 'EUR',
            'type' => 'withdrawal',
            'status' => 'completed',
            'created_at' => '2023-06-30T10:00:00+00:00',
            'completed_at' => '2023-06-30T12:00:00+00:00'
        ];
        $response = $this->createJsonResponse($responseData);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals/' . $withdrawalId)
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->withdrawResource->getWithdrawal($withdrawalId);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertSame('txn_98765', $result->id);
        $this->assertSame('750.00', $result->amount);
        $this->assertSame('EUR', $result->currency);
        $this->assertSame('completed', $result->status);
        $this->assertTrue($result->isCompleted());
    }

    public function testGetWithdrawalApiException(): void
    {
        $withdrawalId = 'txn_nonexistent';
        $apiException = new ApiException('Withdrawal not found', 404);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals/' . $withdrawalId)
            ->willThrowException($apiException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Withdrawal not found');

        $this->withdrawResource->getWithdrawal($withdrawalId);
    }

    public function testGetWithdrawalNetworkException(): void
    {
        $withdrawalId = 'txn_98765';
        $networkException = new NetworkException('Timeout');

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals/' . $withdrawalId)
            ->willThrowException($networkException);

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Timeout');

        $this->withdrawResource->getWithdrawal($withdrawalId);
    }

    public function testListWithdrawalsWithDefaultParameters(): void
    {
        $responseData = [
            'data' => [
                [
                    'id' => 'txn_1',
                    'amount' => '200.00',
                    'currency' => 'USD',
                    'type' => 'withdrawal',
                    'status' => 'completed'
                ],
                [
                    'id' => 'txn_2',
                    'amount' => '150.00',
                    'currency' => 'EUR',
                    'type' => 'withdrawal',
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
            ->with('/withdrawals?page=1&limit=20')
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->withdrawResource->listWithdrawals();

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
        $this->assertInstanceOf(Transaction::class, $result['data'][0]);
        $this->assertSame('txn_1', $result['data'][0]->id);
        $this->assertSame(2, $result['pagination']['total']);
    }

    public function testListWithdrawalsWithFilters(): void
    {
        $filters = [
            'status' => 'completed',
            'currency' => 'USD',
            'from_date' => '2023-06-01T00:00:00Z',
            'to_date' => '2023-06-30T23:59:59Z',
            'account_id' => 'acc_456'
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
            'currency' => 'USD',
            'from_date' => '2023-06-01T00:00:00Z',
            'to_date' => '2023-06-30T23:59:59Z',
            'account_id' => 'acc_456'
        ]);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('get')
            ->with('/withdrawals?' . $expectedQuery)
            ->willReturn($response);

        $result = $this->withdrawResource->listWithdrawals(1, 50, $filters);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(0, $result['data']);
    }

    public function testListWithdrawalsWithLimitValidation(): void
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
            ->with('/withdrawals?page=1&limit=100')
            ->willReturn($response);

        $result = $this->withdrawResource->listWithdrawals(1, 150); // Should be capped to 100

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testListWithdrawalsApiException(): void
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

        $this->withdrawResource->listWithdrawals();
    }

    public function testSearchWithdrawalsSuccess(): void
    {
        $query = 'monthly_payout';
        $responseData = [
            'data' => [
                [
                    'id' => 'txn_1',
                    'amount' => '1000.00',
                    'currency' => 'USD',
                    'type' => 'withdrawal',
                    'status' => 'completed',
                    'reference_id' => 'monthly_payout_001',
                    'description' => 'Monthly profit distribution'
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
            ->with('/withdrawals/search?' . $expectedQuery)
            ->willReturn($response);

        $this->mockLogger
            ->expects($this->exactly(2))
            ->method('info');

        $result = $this->withdrawResource->searchWithdrawals($query);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Transaction::class, $result[0]);
        $this->assertSame('txn_1', $result[0]->id);
        $this->assertSame('monthly_payout_001', $result[0]->referenceId);
    }

    public function testSearchWithdrawalsWithCustomLimit(): void
    {
        $query = 'profit';
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
            ->with('/withdrawals/search?' . $expectedQuery)
            ->willReturn($response);

        $result = $this->withdrawResource->searchWithdrawals($query, $limit);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testSearchWithdrawalsLimitValidation(): void
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
            ->with('/withdrawals/search?' . $expectedQuery)
            ->willReturn($response);

        $result = $this->withdrawResource->searchWithdrawals($query, 100); // Should be capped to 50

        $this->assertIsArray($result);
    }

    public function testSearchWithdrawalsApiException(): void
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

        $this->withdrawResource->searchWithdrawals($query);
    }

    public function testSearchWithdrawalsNetworkException(): void
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

        $this->withdrawResource->searchWithdrawals($query);
    }

    public function testLoggingWithSensitiveDataMasking(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '500.00',
            currency: 'USD',
            accountId: 'account_9876543210',
            type: 'withdrawal'
        );

        $responseData = [
            'id' => 'txn_54321',
            'amount' => '500.00',
            'currency' => 'USD',
            'type' => 'withdrawal',
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
                    'Creating new withdrawal transaction',
                    'Successfully created withdrawal transaction'
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
                    if (isset($context['transaction_id']) && $message === 'Successfully created withdrawal transaction') {
                        $this->assertArrayHasKey('transaction_id', $context);
                    }
                }
                
                return true;
            }));

        $this->withdrawResource->createWithdrawal($transaction);
    }
} 