<?php

declare(strict_types=1);

namespace XGate\Tests\Model;

use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\TestCase;
use XGate\Model\Transaction;

/**
 * Test suite for Transaction DTO
 *
 * @covers \XGate\Model\Transaction
 */
class TransactionTest extends TestCase
{
    private array $sampleTransactionData;

    protected function setUp(): void
    {
        $this->sampleTransactionData = [
            'id' => 'txn_12345',
            'amount' => '100.50',
            'currency' => 'BRL',
            'account_id' => 'acc_67890',
            'payment_method' => 'bank_transfer',
            'type' => 'deposit',
            'status' => 'completed',
            'reference_id' => 'ref_abc123',
            'description' => 'Test deposit transaction',
            'fees' => '2.50',
            'exchange_rate' => '1.0000',
            'callback_url' => 'https://example.com/callback',
            'created_at' => '2023-06-30T10:00:00+00:00',
            'updated_at' => '2023-06-30T10:05:00+00:00',
            'completed_at' => '2023-06-30T10:05:00+00:00',
            'metadata' => ['source' => 'api', 'version' => '1.0']
        ];
    }

    public function testConstructorWithAllParameters(): void
    {
        $createdAt = new DateTimeImmutable('2023-06-30T10:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2023-06-30T10:05:00+00:00');
        $completedAt = new DateTimeImmutable('2023-06-30T10:05:00+00:00');

        $transaction = new Transaction(
            id: 'txn_12345',
            amount: '100.50',
            currency: 'BRL',
            accountId: 'acc_67890',
            paymentMethod: 'bank_transfer',
            type: 'deposit',
            status: 'completed',
            referenceId: 'ref_abc123',
            description: 'Test deposit transaction',
            fees: '2.50',
            exchangeRate: '1.0000',
            callbackUrl: 'https://example.com/callback',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            completedAt: $completedAt,
            metadata: ['source' => 'api']
        );

        $this->assertSame('txn_12345', $transaction->id);
        $this->assertSame('100.50', $transaction->amount);
        $this->assertSame('BRL', $transaction->currency);
        $this->assertSame('acc_67890', $transaction->accountId);
        $this->assertSame('bank_transfer', $transaction->paymentMethod);
        $this->assertSame('deposit', $transaction->type);
        $this->assertSame('completed', $transaction->status);
        $this->assertSame('ref_abc123', $transaction->referenceId);
        $this->assertSame('Test deposit transaction', $transaction->description);
        $this->assertSame('2.50', $transaction->fees);
        $this->assertSame('1.0000', $transaction->exchangeRate);
        $this->assertSame('https://example.com/callback', $transaction->callbackUrl);
        $this->assertEquals($createdAt, $transaction->createdAt);
        $this->assertEquals($updatedAt, $transaction->updatedAt);
        $this->assertEquals($completedAt, $transaction->completedAt);
        $this->assertSame(['source' => 'api'], $transaction->metadata);
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '50.00',
            currency: 'USD'
        );

        $this->assertNull($transaction->id);
        $this->assertSame('50.00', $transaction->amount);
        $this->assertSame('USD', $transaction->currency);
        $this->assertNull($transaction->accountId);
        $this->assertNull($transaction->paymentMethod);
        $this->assertSame('deposit', $transaction->type);
        $this->assertSame('pending', $transaction->status);
        $this->assertNull($transaction->referenceId);
        $this->assertNull($transaction->description);
        $this->assertNull($transaction->fees);
        $this->assertNull($transaction->exchangeRate);
        $this->assertNull($transaction->callbackUrl);
        $this->assertNull($transaction->createdAt);
        $this->assertNull($transaction->updatedAt);
        $this->assertNull($transaction->completedAt);
        $this->assertSame([], $transaction->metadata);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $transaction = Transaction::fromArray($this->sampleTransactionData);

        $this->assertSame('txn_12345', $transaction->id);
        $this->assertSame('100.50', $transaction->amount);
        $this->assertSame('BRL', $transaction->currency);
        $this->assertSame('acc_67890', $transaction->accountId);
        $this->assertSame('bank_transfer', $transaction->paymentMethod);
        $this->assertSame('deposit', $transaction->type);
        $this->assertSame('completed', $transaction->status);
        $this->assertSame('ref_abc123', $transaction->referenceId);
        $this->assertSame('Test deposit transaction', $transaction->description);
        $this->assertSame('2.50', $transaction->fees);
        $this->assertSame('1.0000', $transaction->exchangeRate);
        $this->assertSame('https://example.com/callback', $transaction->callbackUrl);
        $this->assertEquals(new DateTimeImmutable('2023-06-30T10:00:00+00:00'), $transaction->createdAt);
        $this->assertEquals(new DateTimeImmutable('2023-06-30T10:05:00+00:00'), $transaction->updatedAt);
        $this->assertEquals(new DateTimeImmutable('2023-06-30T10:05:00+00:00'), $transaction->completedAt);
        $this->assertSame(['source' => 'api', 'version' => '1.0'], $transaction->metadata);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $minimalData = [
            'amount' => 75.25,
            'currency' => 'EUR'
        ];

        $transaction = Transaction::fromArray($minimalData);

        $this->assertNull($transaction->id);
        $this->assertSame('75.25', $transaction->amount);
        $this->assertSame('EUR', $transaction->currency);
        $this->assertNull($transaction->accountId);
        $this->assertNull($transaction->paymentMethod);
        $this->assertSame('deposit', $transaction->type);
        $this->assertSame('pending', $transaction->status);
        $this->assertNull($transaction->referenceId);
        $this->assertNull($transaction->description);
        $this->assertNull($transaction->fees);
        $this->assertNull($transaction->exchangeRate);
        $this->assertNull($transaction->callbackUrl);
        $this->assertNull($transaction->createdAt);
        $this->assertNull($transaction->updatedAt);
        $this->assertNull($transaction->completedAt);
        $this->assertSame([], $transaction->metadata);
    }

    public function testFromArrayWithEmptyData(): void
    {
        $transaction = Transaction::fromArray([]);

        $this->assertNull($transaction->id);
        $this->assertSame('0.00', $transaction->amount);
        $this->assertSame('BRL', $transaction->currency);
        $this->assertSame('deposit', $transaction->type);
        $this->assertSame('pending', $transaction->status);
    }

    public function testToArrayWithCompleteData(): void
    {
        $transaction = Transaction::fromArray($this->sampleTransactionData);
        $result = $transaction->toArray();

        $this->assertSame('txn_12345', $result['id']);
        $this->assertSame('100.50', $result['amount']);
        $this->assertSame('BRL', $result['currency']);
        $this->assertSame('acc_67890', $result['account_id']);
        $this->assertSame('bank_transfer', $result['payment_method']);
        $this->assertSame('deposit', $result['type']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame('ref_abc123', $result['reference_id']);
        $this->assertSame('Test deposit transaction', $result['description']);
        $this->assertSame('2.50', $result['fees']);
        $this->assertSame('1.0000', $result['exchange_rate']);
        $this->assertSame('https://example.com/callback', $result['callback_url']);
        $this->assertSame('2023-06-30T10:00:00+00:00', $result['created_at']);
        $this->assertSame('2023-06-30T10:05:00+00:00', $result['updated_at']);
        $this->assertSame('2023-06-30T10:05:00+00:00', $result['completed_at']);
        $this->assertSame(['source' => 'api', 'version' => '1.0'], $result['metadata']);
    }

    public function testToArrayWithMinimalData(): void
    {
        $transaction = new Transaction(
            id: null,
            amount: '25.00',
            currency: 'USD'
        );

        $result = $transaction->toArray();

        $this->assertArrayNotHasKey('id', $result);
        $this->assertSame('25.00', $result['amount']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame('deposit', $result['type']);
        $this->assertSame('pending', $result['status']);
        $this->assertArrayNotHasKey('account_id', $result);
        $this->assertArrayNotHasKey('payment_method', $result);
        $this->assertArrayNotHasKey('reference_id', $result);
        $this->assertArrayNotHasKey('description', $result);
        $this->assertArrayNotHasKey('fees', $result);
        $this->assertArrayNotHasKey('exchange_rate', $result);
        $this->assertArrayNotHasKey('callback_url', $result);
        $this->assertArrayNotHasKey('created_at', $result);
        $this->assertArrayNotHasKey('updated_at', $result);
        $this->assertArrayNotHasKey('completed_at', $result);
        $this->assertArrayNotHasKey('metadata', $result);
    }

    public function testJsonSerialize(): void
    {
        $transaction = Transaction::fromArray($this->sampleTransactionData);
        $result = $transaction->jsonSerialize();

        $this->assertSame($transaction->toArray(), $result);
    }

    public function testFromJsonWithValidJson(): void
    {
        $json = json_encode($this->sampleTransactionData);
        $transaction = Transaction::fromJson($json);

        $this->assertSame('txn_12345', $transaction->id);
        $this->assertSame('100.50', $transaction->amount);
        $this->assertSame('BRL', $transaction->currency);
        $this->assertSame('deposit', $transaction->type);
        $this->assertSame('completed', $transaction->status);
    }

    public function testFromJsonWithInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        Transaction::fromJson('invalid json string');
    }

    public function testToJsonWithValidData(): void
    {
        $transaction = Transaction::fromArray($this->sampleTransactionData);
        $json = $transaction->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('txn_12345', $decoded['id']);
        $this->assertSame('100.50', $decoded['amount']);
        $this->assertSame('BRL', $decoded['currency']);
    }

    public function testIsDeposit(): void
    {
        $depositTransaction = new Transaction(null, '100.00', 'BRL', type: 'deposit');
        $withdrawalTransaction = new Transaction(null, '100.00', 'BRL', type: 'withdrawal');
        $mixedCaseTransaction = new Transaction(null, '100.00', 'BRL', type: 'DEPOSIT');

        $this->assertTrue($depositTransaction->isDeposit());
        $this->assertFalse($withdrawalTransaction->isDeposit());
        $this->assertTrue($mixedCaseTransaction->isDeposit());
    }

    public function testIsWithdrawal(): void
    {
        $depositTransaction = new Transaction(null, '100.00', 'BRL', type: 'deposit');
        $withdrawalTransaction = new Transaction(null, '100.00', 'BRL', type: 'withdrawal');
        $mixedCaseTransaction = new Transaction(null, '100.00', 'BRL', type: 'WITHDRAWAL');

        $this->assertFalse($depositTransaction->isWithdrawal());
        $this->assertTrue($withdrawalTransaction->isWithdrawal());
        $this->assertTrue($mixedCaseTransaction->isWithdrawal());
    }

    public function testIsCompleted(): void
    {
        $completedTransaction = new Transaction(null, '100.00', 'BRL', status: 'completed');
        $pendingTransaction = new Transaction(null, '100.00', 'BRL', status: 'pending');
        $mixedCaseTransaction = new Transaction(null, '100.00', 'BRL', status: 'COMPLETED');

        $this->assertTrue($completedTransaction->isCompleted());
        $this->assertFalse($pendingTransaction->isCompleted());
        $this->assertTrue($mixedCaseTransaction->isCompleted());
    }

    public function testIsPending(): void
    {
        $pendingTransaction = new Transaction(null, '100.00', 'BRL', status: 'pending');
        $completedTransaction = new Transaction(null, '100.00', 'BRL', status: 'completed');
        $mixedCaseTransaction = new Transaction(null, '100.00', 'BRL', status: 'PENDING');

        $this->assertTrue($pendingTransaction->isPending());
        $this->assertFalse($completedTransaction->isPending());
        $this->assertTrue($mixedCaseTransaction->isPending());
    }

    public function testIsFailed(): void
    {
        $failedTransaction = new Transaction(null, '100.00', 'BRL', status: 'failed');
        $completedTransaction = new Transaction(null, '100.00', 'BRL', status: 'completed');
        $mixedCaseTransaction = new Transaction(null, '100.00', 'BRL', status: 'FAILED');

        $this->assertTrue($failedTransaction->isFailed());
        $this->assertFalse($completedTransaction->isFailed());
        $this->assertTrue($mixedCaseTransaction->isFailed());
    }

    public function testGetFormattedAmount(): void
    {
        $transaction = new Transaction(null, '100.50', 'BRL');

        $this->assertSame('100.50 BRL', $transaction->getFormattedAmount());
    }

    public function testGetTotalAmountWithoutFees(): void
    {
        $transaction = new Transaction(null, '100.50', 'BRL');

        $this->assertSame('100.50', $transaction->getTotalAmount());
    }

    public function testGetTotalAmountWithFees(): void
    {
        $transaction = new Transaction(null, '100.50', 'BRL', fees: '2.50');

        $this->assertSame('103.00', $transaction->getTotalAmount());
    }

    public function testGetDisplayNameWithDescription(): void
    {
        $transaction = new Transaction(
            null,
            '100.50',
            'BRL',
            type: 'deposit',
            description: 'Test payment'
        );

        $this->assertSame('Deposit: 100.50 BRL - Test payment', $transaction->getDisplayName());
    }

    public function testGetDisplayNameWithoutDescription(): void
    {
        $transaction = new Transaction(null, '100.50', 'BRL', type: 'withdrawal');

        $this->assertSame('Withdrawal: 100.50 BRL', $transaction->getDisplayName());
    }

    public function testGetDisplayNameWithMixedCaseType(): void
    {
        $transaction = new Transaction(null, '100.50', 'BRL', type: 'DEPOSIT');

        $this->assertSame('Deposit: 100.50 BRL', $transaction->getDisplayName());
    }

    public function testReadonlyProperties(): void
    {
        $transaction = new Transaction(null, '100.00', 'BRL');

        $reflection = new \ReflectionClass($transaction);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    public function testSerializationRoundTrip(): void
    {
        $original = Transaction::fromArray($this->sampleTransactionData);
        $json = $original->toJson();
        $restored = Transaction::fromJson($json);

        $this->assertEquals($original->toArray(), $restored->toArray());
    }

    public function testNumericAmountConversionInFromArray(): void
    {
        $data = ['amount' => 123.45, 'currency' => 'USD'];
        $transaction = Transaction::fromArray($data);

        $this->assertSame('123.45', $transaction->amount);
    }

    public function testFeesConversionInFromArray(): void
    {
        $data = ['amount' => '100.00', 'currency' => 'USD', 'fees' => 5.75];
        $transaction = Transaction::fromArray($data);

        $this->assertSame('5.75', $transaction->fees);
    }

    public function testExchangeRateConversionInFromArray(): void
    {
        $data = ['amount' => '100.00', 'currency' => 'USD', 'exchange_rate' => 1.2345];
        $transaction = Transaction::fromArray($data);

        $this->assertSame('1.2345', $transaction->exchangeRate);
    }
} 