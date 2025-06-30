<?php

declare(strict_types=1);

namespace XGate\Tests\Resource;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use XGate\Http\HttpClient;
use XGate\Model\PixKey;
use XGate\Resource\PixResource;

/**
 * Test cases for PixResource
 *
 * @package XGate\Tests\Resource
 */
class PixResourceTest extends TestCase
{
    private PixResource $pixResource;
    private MockObject|HttpClient $httpClient;
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pixResource = new PixResource($this->httpClient, $this->logger);
    }

    public function testRegisterWithAllParameters(): void
    {
        $responseData = [
            'id' => 'pix-123',
            'type' => 'email',
            'key' => 'user@example.com',
            'account_holder_name' => 'João Silva',
            'account_holder_document' => '12345678901',
            'bank_code' => '001',
            'account_number' => '12345-6',
            'account_type' => 'checking',
            'status' => 'active',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'metadata' => ['source' => 'api']
        ];

        $response = $this->createJsonResponse($responseData);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/pix/keys',
                [
                    'json' => [
                        'type' => 'email',
                        'key' => 'user@example.com',
                        'account_holder_name' => 'João Silva',
                        'account_holder_document' => '12345678901',
                        'bank_code' => '001',
                        'account_number' => '12345-6',
                        'account_type' => 'checking',
                        'metadata' => ['source' => 'api']
                    ]
                ]
            )
            ->willReturn($response);

        $pixKey = $this->pixResource->register(
            type: 'email',
            key: 'user@example.com',
            accountHolderName: 'João Silva',
            accountHolderDocument: '12345678901',
            bankCode: '001',
            accountNumber: '12345-6',
            accountType: 'checking',
            metadata: ['source' => 'api']
        );

        $this->assertInstanceOf(PixKey::class, $pixKey);
        $this->assertSame('pix-123', $pixKey->id);
        $this->assertSame('email', $pixKey->type);
        $this->assertSame('user@example.com', $pixKey->key);
        $this->assertSame('João Silva', $pixKey->accountHolderName);
        $this->assertSame('active', $pixKey->status);
    }

    public function testRegisterWithMinimalParameters(): void
    {
        $responseData = [
            'id' => 'pix-456',
            'type' => 'cpf',
            'key' => '12345678901',
            'status' => 'pending',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z'
        ];

        $response = $this->createJsonResponse($responseData);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/pix/keys',
                [
                    'json' => [
                        'type' => 'cpf',
                        'key' => '12345678901'
                    ]
                ]
            )
            ->willReturn($response);

        $pixKey = $this->pixResource->register('cpf', '12345678901');

        $this->assertInstanceOf(PixKey::class, $pixKey);
        $this->assertSame('pix-456', $pixKey->id);
        $this->assertSame('cpf', $pixKey->type);
        $this->assertSame('12345678901', $pixKey->key);
        $this->assertSame('pending', $pixKey->status);
    }

    public function testRegisterThrowsApiException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('Invalid PIX key format'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid PIX key format');

        $this->pixResource->register('email', 'invalid-email');
    }

    public function testRegisterThrowsNetworkException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new NetworkException('Connection timeout'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Connection timeout');

        $this->pixResource->register('email', 'user@example.com');
    }

    public function testGet(): void
    {
        $responseData = [
            'id' => 'pix-123',
            'type' => 'email',
            'key' => 'user@example.com',
            'status' => 'active',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z'
        ];

        $response = $this->createJsonResponse($responseData);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/pix/keys/pix-123')
            ->willReturn($response);

        $pixKey = $this->pixResource->get('pix-123');

        $this->assertInstanceOf(PixKey::class, $pixKey);
        $this->assertSame('pix-123', $pixKey->id);
        $this->assertSame('email', $pixKey->type);
        $this->assertSame('user@example.com', $pixKey->key);
        $this->assertSame('active', $pixKey->status);
    }

    public function testGetThrowsApiException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('PIX key not found'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('PIX key not found');

        $this->pixResource->get('nonexistent-pix');
    }

    public function testUpdate(): void
    {
        $responseData = [
            'id' => 'pix-123',
            'type' => 'email',
            'key' => 'user@example.com',
            'account_holder_name' => 'João da Silva',
            'status' => 'active',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-02T00:00:00Z'
        ];

        $response = $this->createJsonResponse($responseData);
        
        $updateData = [
            'account_holder_name' => 'João da Silva',
            'metadata' => ['updated_reason' => 'name_change']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('PUT', '/pix/keys/pix-123', ['json' => $updateData])
            ->willReturn($response);

        $pixKey = $this->pixResource->update('pix-123', $updateData);

        $this->assertInstanceOf(PixKey::class, $pixKey);
        $this->assertSame('pix-123', $pixKey->id);
        $this->assertSame('João da Silva', $pixKey->accountHolderName);
    }

    public function testDelete(): void
    {
        $response = $this->createJsonResponse([]);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('DELETE', '/pix/keys/pix-123')
            ->willReturn($response);

        $result = $this->pixResource->delete('pix-123');

        $this->assertTrue($result);
    }

    public function testDeleteThrowsApiException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('Cannot delete active PIX key'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Cannot delete active PIX key');

        $this->pixResource->delete('pix-123');
    }

    public function testListWithDefaults(): void
    {
        $responseData = [
            'data' => [
                [
                    'id' => 'pix-1',
                    'type' => 'email',
                    'key' => 'user1@example.com',
                    'status' => 'active',
                    'created_at' => '2023-01-01T00:00:00Z',
                    'updated_at' => '2023-01-01T00:00:00Z'
                ],
                [
                    'id' => 'pix-2',
                    'type' => 'cpf',
                    'key' => '12345678901',
                    'status' => 'pending',
                    'created_at' => '2023-01-02T00:00:00Z',
                    'updated_at' => '2023-01-02T00:00:00Z'
                ]
            ]
        ];

        $response = $this->createJsonResponse($responseData);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/pix/keys?page=1&limit=20')
            ->willReturn($response);

        $pixKeys = $this->pixResource->list();

        $this->assertIsArray($pixKeys);
        $this->assertCount(2, $pixKeys);
        $this->assertInstanceOf(PixKey::class, $pixKeys[0]);
        $this->assertInstanceOf(PixKey::class, $pixKeys[1]);
        $this->assertSame('pix-1', $pixKeys[0]->id);
        $this->assertSame('pix-2', $pixKeys[1]->id);
    }

    public function testListWithPaginationAndFilters(): void
    {
        $responseData = [
            [
                'id' => 'pix-1',
                'type' => 'email',
                'key' => 'user@example.com',
                'status' => 'active',
                'created_at' => '2023-01-01T00:00:00Z',
                'updated_at' => '2023-01-01T00:00:00Z'
            ]
        ];

        $response = $this->createJsonResponse($responseData);
        
        $filters = ['type' => 'email', 'status' => 'active'];
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/pix/keys?page=2&limit=10&type=email&status=active')
            ->willReturn($response);

        $pixKeys = $this->pixResource->list(2, 10, $filters);

        $this->assertIsArray($pixKeys);
        $this->assertCount(1, $pixKeys);
        $this->assertInstanceOf(PixKey::class, $pixKeys[0]);
        $this->assertSame('email', $pixKeys[0]->type);
        $this->assertSame('active', $pixKeys[0]->status);
    }

    public function testSearch(): void
    {
        $responseData = [
            'results' => [
                [
                    'id' => 'pix-1',
                    'type' => 'email',
                    'key' => 'user@example.com',
                    'status' => 'active',
                    'created_at' => '2023-01-01T00:00:00Z',
                    'updated_at' => '2023-01-01T00:00:00Z'
                ]
            ]
        ];

        $response = $this->createJsonResponse($responseData);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/pix/keys/search?q=example.com&limit=10')
            ->willReturn($response);

        $pixKeys = $this->pixResource->search('example.com');

        $this->assertIsArray($pixKeys);
        $this->assertCount(1, $pixKeys);
        $this->assertInstanceOf(PixKey::class, $pixKeys[0]);
        $this->assertSame('user@example.com', $pixKeys[0]->key);
    }

    public function testSearchWithDirectArrayResponse(): void
    {
        $responseData = [
            [
                'id' => 'pix-1',
                'type' => 'email',
                'key' => 'user@example.com',
                'status' => 'active',
                'created_at' => '2023-01-01T00:00:00Z',
                'updated_at' => '2023-01-01T00:00:00Z'
            ]
        ];

        $response = $this->createJsonResponse($responseData);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/pix/keys/search?q=Jo%C3%A3o&limit=5')
            ->willReturn($response);

        $pixKeys = $this->pixResource->search('João', 5);

        $this->assertIsArray($pixKeys);
        $this->assertCount(1, $pixKeys);
        $this->assertInstanceOf(PixKey::class, $pixKeys[0]);
    }

    public function testFindByKeyFound(): void
    {
        $responseData = [
            'id' => 'pix-123',
            'type' => 'email',
            'key' => 'user@example.com',
            'status' => 'active',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z'
        ];

        $response = $this->createJsonResponse($responseData);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/pix/keys/find?type=email&key=user%40example.com')
            ->willReturn($response);

        $pixKey = $this->pixResource->findByKey('email', 'user@example.com');

        $this->assertInstanceOf(PixKey::class, $pixKey);
        $this->assertSame('pix-123', $pixKey->id);
        $this->assertSame('email', $pixKey->type);
        $this->assertSame('user@example.com', $pixKey->key);
    }

    public function testFindByKeyNotFound(): void
    {
        $response = $this->createJsonResponse([]);
        
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/pix/keys/find?type=email&key=notfound%40example.com')
            ->willReturn($response);

        $pixKey = $this->pixResource->findByKey('email', 'notfound@example.com');

        $this->assertNull($pixKey);
    }

    public function testFindByKeyNotFoundWith404Exception(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('404 Not Found'));

        $pixKey = $this->pixResource->findByKey('email', 'notfound@example.com');

        $this->assertNull($pixKey);
    }

    public function testFindByKeyThrowsNonNotFoundApiException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('Invalid request'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid request');

        $this->pixResource->findByKey('email', 'user@example.com');
    }

    public function testFindByKeyThrowsNetworkException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new NetworkException('Connection timeout'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Connection timeout');

        $this->pixResource->findByKey('email', 'user@example.com');
    }

    /**
     * Create a mock JSON response
     */
    private function createJsonResponse(array $data): MockObject|ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($data));
        $response->method('getBody')->willReturn($stream);
        
        return $response;
    }
} 