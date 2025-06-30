<?php

declare(strict_types=1);

namespace XGate\Tests\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use XGate\Model\PixKey;

/**
 * Test cases for PixKey DTO
 *
 * @package XGate\Tests\Model
 */
class PixKeyTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $createdAt = new DateTimeImmutable('2023-01-01T00:00:00Z');
        $updatedAt = new DateTimeImmutable('2023-01-02T00:00:00Z');
        $metadata = ['source' => 'api', 'version' => '1.0'];

        $pixKey = new PixKey(
            id: 'pix-123',
            type: 'email',
            key: 'user@example.com',
            accountHolderName: 'João Silva',
            accountHolderDocument: '12345678901',
            bankCode: '001',
            bankName: 'Banco do Brasil',
            branch: '1234',
            accountNumber: '56789',
            accountType: 'checking',
            status: 'active',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            metadata: $metadata
        );

        $this->assertSame('pix-123', $pixKey->id);
        $this->assertSame('email', $pixKey->type);
        $this->assertSame('user@example.com', $pixKey->key);
        $this->assertSame('João Silva', $pixKey->accountHolderName);
        $this->assertSame('12345678901', $pixKey->accountHolderDocument);
        $this->assertSame('001', $pixKey->bankCode);
        $this->assertSame('Banco do Brasil', $pixKey->bankName);
        $this->assertSame('1234', $pixKey->branch);
        $this->assertSame('56789', $pixKey->accountNumber);
        $this->assertSame('checking', $pixKey->accountType);
        $this->assertSame('active', $pixKey->status);
        $this->assertSame($createdAt, $pixKey->createdAt);
        $this->assertSame($updatedAt, $pixKey->updatedAt);
        $this->assertSame($metadata, $pixKey->metadata);
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $pixKey = new PixKey(
            id: null,
            type: 'cpf',
            key: '12345678901'
        );

        $this->assertNull($pixKey->id);
        $this->assertSame('cpf', $pixKey->type);
        $this->assertSame('12345678901', $pixKey->key);
        $this->assertNull($pixKey->accountHolderName);
        $this->assertNull($pixKey->accountHolderDocument);
        $this->assertNull($pixKey->bankCode);
        $this->assertNull($pixKey->bankName);
        $this->assertNull($pixKey->branch);
        $this->assertNull($pixKey->accountNumber);
        $this->assertNull($pixKey->accountType);
        $this->assertSame('active', $pixKey->status);
        $this->assertNull($pixKey->createdAt);
        $this->assertNull($pixKey->updatedAt);
        $this->assertSame([], $pixKey->metadata);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'id' => 'pix-456',
            'type' => 'phone',
            'key' => '+5511999999999',
            'account_holder_name' => 'Maria Santos',
            'account_holder_document' => '98765432100',
            'bank_code' => '237',
            'bank_name' => 'Bradesco',
            'branch' => '4321',
            'account_number' => '98765',
            'account_type' => 'savings',
            'status' => 'active',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-02T00:00:00Z',
            'metadata' => ['region' => 'SP', 'priority' => 'high']
        ];

        $pixKey = PixKey::fromArray($data);

        $this->assertSame('pix-456', $pixKey->id);
        $this->assertSame('phone', $pixKey->type);
        $this->assertSame('+5511999999999', $pixKey->key);
        $this->assertSame('Maria Santos', $pixKey->accountHolderName);
        $this->assertSame('98765432100', $pixKey->accountHolderDocument);
        $this->assertSame('237', $pixKey->bankCode);
        $this->assertSame('Bradesco', $pixKey->bankName);
        $this->assertSame('4321', $pixKey->branch);
        $this->assertSame('98765', $pixKey->accountNumber);
        $this->assertSame('savings', $pixKey->accountType);
        $this->assertSame('active', $pixKey->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $pixKey->createdAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $pixKey->updatedAt);
        $this->assertSame(['region' => 'SP', 'priority' => 'high'], $pixKey->metadata);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'type' => 'random',
            'key' => '123e4567-e89b-12d3-a456-426614174000'
        ];

        $pixKey = PixKey::fromArray($data);

        $this->assertNull($pixKey->id);
        $this->assertSame('random', $pixKey->type);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $pixKey->key);
        $this->assertSame('active', $pixKey->status);
        $this->assertSame([], $pixKey->metadata);
    }

    public function testToArrayWithCompleteData(): void
    {
        $createdAt = new DateTimeImmutable('2023-01-01T00:00:00Z');
        $updatedAt = new DateTimeImmutable('2023-01-02T00:00:00Z');

        $pixKey = new PixKey(
            id: 'pix-789',
            type: 'cnpj',
            key: '12345678000195',
            accountHolderName: 'Empresa XYZ Ltda',
            accountHolderDocument: '12345678000195',
            bankCode: '104',
            bankName: 'Caixa Econômica Federal',
            branch: '5678',
            accountNumber: '13579',
            accountType: 'checking',
            status: 'active',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            metadata: ['business_type' => 'retail']
        );

        $array = $pixKey->toArray();

        $this->assertSame('pix-789', $array['id']);
        $this->assertSame('cnpj', $array['type']);
        $this->assertSame('12345678000195', $array['key']);
        $this->assertSame('Empresa XYZ Ltda', $array['account_holder_name']);
        $this->assertSame('12345678000195', $array['account_holder_document']);
        $this->assertSame('104', $array['bank_code']);
        $this->assertSame('Caixa Econômica Federal', $array['bank_name']);
        $this->assertSame('5678', $array['branch']);
        $this->assertSame('13579', $array['account_number']);
        $this->assertSame('checking', $array['account_type']);
        $this->assertSame('active', $array['status']);
        $this->assertSame('2023-01-01T00:00:00Z', $array['created_at']);
        $this->assertSame('2023-01-02T00:00:00Z', $array['updated_at']);
        $this->assertSame(['business_type' => 'retail'], $array['metadata']);
    }

    public function testToArrayWithMinimalData(): void
    {
        $pixKey = new PixKey(
            id: null,
            type: 'email',
            key: 'test@example.com'
        );

        $array = $pixKey->toArray();

        $this->assertArrayNotHasKey('id', $array);
        $this->assertSame('email', $array['type']);
        $this->assertSame('test@example.com', $array['key']);
        $this->assertSame('active', $array['status']);
        $this->assertArrayNotHasKey('account_holder_name', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('metadata', $array);
    }

    public function testJsonSerialize(): void
    {
        $pixKey = new PixKey(
            id: 'pix-json',
            type: 'email',
            key: 'json@example.com'
        );

        $jsonData = $pixKey->jsonSerialize();
        $expectedArray = $pixKey->toArray();

        $this->assertSame($expectedArray, $jsonData);
    }

    public function testFromJsonWithValidData(): void
    {
        $json = '{"id":"pix-json","type":"email","key":"json@example.com","status":"active"}';

        $pixKey = PixKey::fromJson($json);

        $this->assertSame('pix-json', $pixKey->id);
        $this->assertSame('email', $pixKey->type);
        $this->assertSame('json@example.com', $pixKey->key);
        $this->assertSame('active', $pixKey->status);
    }

    public function testFromJsonWithInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON provided for PIX key data');

        PixKey::fromJson('invalid json');
    }

    public function testToJson(): void
    {
        $pixKey = new PixKey(
            id: 'pix-tojson',
            type: 'phone',
            key: '+5511987654321'
        );

        $json = $pixKey->toJson();
        $decodedData = json_decode($json, true);

        $this->assertSame($pixKey->toArray(), $decodedData);
    }

    public function testIsCpf(): void
    {
        $pixKeyCpf = new PixKey(null, 'cpf', '12345678901');
        $pixKeyEmail = new PixKey(null, 'email', 'test@example.com');

        $this->assertTrue($pixKeyCpf->isCpf());
        $this->assertFalse($pixKeyEmail->isCpf());
    }

    public function testIsCnpj(): void
    {
        $pixKeyCnpj = new PixKey(null, 'cnpj', '12345678000195');
        $pixKeyPhone = new PixKey(null, 'phone', '+5511999999999');

        $this->assertTrue($pixKeyCnpj->isCnpj());
        $this->assertFalse($pixKeyPhone->isCnpj());
    }

    public function testIsEmail(): void
    {
        $pixKeyEmail = new PixKey(null, 'email', 'test@example.com');
        $pixKeyRandom = new PixKey(null, 'random', '123e4567-e89b-12d3-a456-426614174000');

        $this->assertTrue($pixKeyEmail->isEmail());
        $this->assertFalse($pixKeyRandom->isEmail());
    }

    public function testIsPhone(): void
    {
        $pixKeyPhone = new PixKey(null, 'phone', '+5511999999999');
        $pixKeyCpf = new PixKey(null, 'cpf', '12345678901');

        $this->assertTrue($pixKeyPhone->isPhone());
        $this->assertFalse($pixKeyCpf->isPhone());
    }

    public function testIsRandom(): void
    {
        $pixKeyRandom = new PixKey(null, 'random', '123e4567-e89b-12d3-a456-426614174000');
        $pixKeyEmail = new PixKey(null, 'email', 'test@example.com');

        $this->assertTrue($pixKeyRandom->isRandom());
        $this->assertFalse($pixKeyEmail->isRandom());
    }

    public function testIsActive(): void
    {
        $pixKeyActive = new PixKey(null, 'email', 'active@example.com', status: 'active');
        $pixKeyInactive = new PixKey(null, 'email', 'inactive@example.com', status: 'inactive');

        $this->assertTrue($pixKeyActive->isActive());
        $this->assertFalse($pixKeyInactive->isActive());
    }

    public function testGetDisplayNameForCpf(): void
    {
        $pixKey = new PixKey(null, 'cpf', '12345678901');
        $displayName = $pixKey->getDisplayName();

        $this->assertSame('123.***.789-**', $displayName);
    }

    public function testGetDisplayNameForCnpj(): void
    {
        $pixKey = new PixKey(null, 'cnpj', '12345678000195');
        $displayName = $pixKey->getDisplayName();

        $this->assertSame('12.***.678/****-**', $displayName);
    }

    public function testGetDisplayNameForEmail(): void
    {
        $pixKey = new PixKey(null, 'email', 'user@example.com');
        $displayName = $pixKey->getDisplayName();

        $this->assertSame('u**r@example.com', $displayName);
    }

    public function testGetDisplayNameForPhone(): void
    {
        $pixKey = new PixKey(null, 'phone', '+5511999999999');
        $displayName = $pixKey->getDisplayName();

        $this->assertSame('+55***999999', $displayName);
    }

    public function testGetDisplayNameForRandom(): void
    {
        $pixKey = new PixKey(null, 'random', '123e4567-e89b-12d3-a456-426614174000');
        $displayName = $pixKey->getDisplayName();

        $this->assertSame('123e4567...', $displayName);
    }

    public function testGetDisplayNameForUnknownType(): void
    {
        $pixKey = new PixKey(null, 'unknown', 'some-value');
        $displayName = $pixKey->getDisplayName();

        $this->assertSame('***', $displayName);
    }

    public function testCaseInsensitiveTypeChecking(): void
    {
        $pixKeyUpperCase = new PixKey(null, 'EMAIL', 'test@example.com');
        $pixKeyMixedCase = new PixKey(null, 'CpF', '12345678901');

        $this->assertTrue($pixKeyUpperCase->isEmail());
        $this->assertTrue($pixKeyMixedCase->isCpf());
    }
} 