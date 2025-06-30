<?php

declare(strict_types=1);

namespace XGate\Tests\Model;

use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\TestCase;
use XGate\Model\Customer;

/**
 * Unit tests for Customer DTO
 *
 * @covers \XGate\Model\Customer
 */
class CustomerTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $id = 'customer-123';
        $name = 'João Silva';
        $email = 'joao@example.com';
        $phone = '+5511999999999';
        $document = '12345678901';
        $documentType = 'cpf';
        $status = 'active';
        $createdAt = new DateTimeImmutable('2023-01-01T10:00:00Z');
        $updatedAt = new DateTimeImmutable('2023-01-02T10:00:00Z');
        $metadata = ['source' => 'api'];

        $customer = new Customer(
            $id,
            $name,
            $email,
            $phone,
            $document,
            $documentType,
            $status,
            $createdAt,
            $updatedAt,
            $metadata
        );

        $this->assertSame($id, $customer->id);
        $this->assertSame($name, $customer->name);
        $this->assertSame($email, $customer->email);
        $this->assertSame($phone, $customer->phone);
        $this->assertSame($document, $customer->document);
        $this->assertSame($documentType, $customer->documentType);
        $this->assertSame($status, $customer->status);
        $this->assertSame($createdAt, $customer->createdAt);
        $this->assertSame($updatedAt, $customer->updatedAt);
        $this->assertSame($metadata, $customer->metadata);
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $name = 'João Silva';
        $email = 'joao@example.com';

        $customer = new Customer(null, $name, $email);

        $this->assertNull($customer->id);
        $this->assertSame($name, $customer->name);
        $this->assertSame($email, $customer->email);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->document);
        $this->assertNull($customer->documentType);
        $this->assertSame('active', $customer->status);
        $this->assertNull($customer->createdAt);
        $this->assertNull($customer->updatedAt);
        $this->assertSame([], $customer->metadata);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'id' => 'customer-123',
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '+5511999999999',
            'document' => '12345678901',
            'document_type' => 'cpf',
            'status' => 'active',
            'created_at' => '2023-01-01T10:00:00Z',
            'updated_at' => '2023-01-02T10:00:00Z',
            'metadata' => ['source' => 'api'],
        ];

        $customer = Customer::fromArray($data);

        $this->assertSame('customer-123', $customer->id);
        $this->assertSame('João Silva', $customer->name);
        $this->assertSame('joao@example.com', $customer->email);
        $this->assertSame('+5511999999999', $customer->phone);
        $this->assertSame('12345678901', $customer->document);
        $this->assertSame('cpf', $customer->documentType);
        $this->assertSame('active', $customer->status);
        $this->assertEquals(new DateTimeImmutable('2023-01-01T10:00:00Z'), $customer->createdAt);
        $this->assertEquals(new DateTimeImmutable('2023-01-02T10:00:00Z'), $customer->updatedAt);
        $this->assertSame(['source' => 'api'], $customer->metadata);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ];

        $customer = Customer::fromArray($data);

        $this->assertNull($customer->id);
        $this->assertSame('João Silva', $customer->name);
        $this->assertSame('joao@example.com', $customer->email);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->document);
        $this->assertNull($customer->documentType);
        $this->assertSame('active', $customer->status);
        $this->assertNull($customer->createdAt);
        $this->assertNull($customer->updatedAt);
        $this->assertSame([], $customer->metadata);
    }

    public function testFromArrayWithEmptyData(): void
    {
        $data = [];

        $customer = Customer::fromArray($data);

        $this->assertNull($customer->id);
        $this->assertSame('', $customer->name);
        $this->assertSame('', $customer->email);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->document);
        $this->assertNull($customer->documentType);
        $this->assertSame('active', $customer->status);
        $this->assertNull($customer->createdAt);
        $this->assertNull($customer->updatedAt);
        $this->assertSame([], $customer->metadata);
    }

    public function testToArrayWithCompleteData(): void
    {
        $customer = new Customer(
            'customer-123',
            'João Silva',
            'joao@example.com',
            '+5511999999999',
            '12345678901',
            'cpf',
            'active',
            new DateTimeImmutable('2023-01-01T10:00:00Z'),
            new DateTimeImmutable('2023-01-02T10:00:00Z'),
            ['source' => 'api']
        );

        $array = $customer->toArray();

        // Test that all expected keys and values are present
        $this->assertSame('customer-123', $array['id']);
        $this->assertSame('João Silva', $array['name']);
        $this->assertSame('joao@example.com', $array['email']);
        $this->assertSame('+5511999999999', $array['phone']);
        $this->assertSame('12345678901', $array['document']);
        $this->assertSame('cpf', $array['document_type']);
        $this->assertSame('active', $array['status']);
        $this->assertSame('2023-01-01T10:00:00+00:00', $array['created_at']);
        $this->assertSame('2023-01-02T10:00:00+00:00', $array['updated_at']);
        $this->assertSame(['source' => 'api'], $array['metadata']);
        $this->assertCount(10, $array);
    }

    public function testToArrayWithMinimalData(): void
    {
        $customer = new Customer(null, 'João Silva', 'joao@example.com');

        $array = $customer->toArray();

        $expected = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'status' => 'active',
        ];

        $this->assertSame($expected, $array);
    }

    public function testJsonSerialize(): void
    {
        $customer = new Customer(
            'customer-123',
            'João Silva',
            'joao@example.com',
            '+5511999999999'
        );

        $jsonData = $customer->jsonSerialize();

        // Test that all expected keys and values are present
        $this->assertSame('customer-123', $jsonData['id']);
        $this->assertSame('João Silva', $jsonData['name']);
        $this->assertSame('joao@example.com', $jsonData['email']);
        $this->assertSame('+5511999999999', $jsonData['phone']);
        $this->assertSame('active', $jsonData['status']);
        $this->assertCount(5, $jsonData);
    }

    public function testFromJsonWithValidJson(): void
    {
        $json = '{"id":"customer-123","name":"João Silva","email":"joao@example.com","status":"active"}';

        $customer = Customer::fromJson($json);

        $this->assertSame('customer-123', $customer->id);
        $this->assertSame('João Silva', $customer->name);
        $this->assertSame('joao@example.com', $customer->email);
        $this->assertSame('active', $customer->status);
    }

    public function testFromJsonWithInvalidJson(): void
    {
        $this->expectException(JsonException::class);

        Customer::fromJson('invalid json');
    }

    public function testToJsonWithValidData(): void
    {
        $customer = new Customer(
            'customer-123',
            'João Silva',
            'joao@example.com'
        );

        $json = $customer->toJson();

        // Decode and verify the JSON contains expected data
        $decodedData = json_decode($json, true);
        
        $this->assertSame('customer-123', $decodedData['id']);
        $this->assertSame('João Silva', $decodedData['name']);
        $this->assertSame('joao@example.com', $decodedData['email']);
        $this->assertSame('active', $decodedData['status']);
        $this->assertCount(4, $decodedData);
    }

    public function testHasValidEmailWithValidEmail(): void
    {
        $customer = new Customer(null, 'João Silva', 'joao@example.com');

        $this->assertTrue($customer->hasValidEmail());
    }

    public function testHasValidEmailWithInvalidEmail(): void
    {
        $customer = new Customer(null, 'João Silva', 'invalid-email');

        $this->assertFalse($customer->hasValidEmail());
    }

    public function testIsActiveWithActiveStatus(): void
    {
        $customer = new Customer(null, 'João Silva', 'joao@example.com', null, null, null, 'active');

        $this->assertTrue($customer->isActive());
    }

    public function testIsActiveWithInactiveStatus(): void
    {
        $customer = new Customer(null, 'João Silva', 'joao@example.com', null, null, null, 'inactive');

        $this->assertFalse($customer->isActive());
    }

    public function testGetDisplayNameWithName(): void
    {
        $customer = new Customer(null, 'João Silva', 'joao@example.com');

        $this->assertSame('João Silva', $customer->getDisplayName());
    }

    public function testGetDisplayNameWithEmptyName(): void
    {
        $customer = new Customer(null, '', 'joao@example.com');

        $this->assertSame('joao@example.com', $customer->getDisplayName());
    }

    public function testReadonlyProperties(): void
    {
        $customer = new Customer(null, 'João Silva', 'joao@example.com');

        // Test that properties are readonly (this would cause a fatal error if they weren't)
        $this->assertSame('João Silva', $customer->name);
        $this->assertSame('joao@example.com', $customer->email);
        $this->assertSame('active', $customer->status);
    }

    public function testRoundTripSerialization(): void
    {
        $original = new Customer(
            'customer-123',
            'João Silva',
            'joao@example.com',
            '+5511999999999',
            '12345678901',
            'cpf',
            'active',
            new DateTimeImmutable('2023-01-01T10:00:00Z'),
            new DateTimeImmutable('2023-01-02T10:00:00Z'),
            ['source' => 'api']
        );

        // Convert to JSON and back
        $json = $original->toJson();
        $restored = Customer::fromJson($json);

        $this->assertSame($original->id, $restored->id);
        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->email, $restored->email);
        $this->assertSame($original->phone, $restored->phone);
        $this->assertSame($original->document, $restored->document);
        $this->assertSame($original->documentType, $restored->documentType);
        $this->assertSame($original->status, $restored->status);
        $this->assertEquals($original->createdAt, $restored->createdAt);
        $this->assertEquals($original->updatedAt, $restored->updatedAt);
        $this->assertSame($original->metadata, $restored->metadata);
    }
} 