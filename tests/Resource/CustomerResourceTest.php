<?php

declare(strict_types=1);

namespace XGate\Tests\Resource;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use XGate\Http\HttpClient;
use XGate\Model\Customer;
use XGate\Resource\CustomerResource;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;

/**
 * Unit tests for CustomerResource
 *
 * @covers \XGate\Resource\CustomerResource
 */
class CustomerResourceTest extends TestCase
{
    private CustomerResource $customerResource;
    private HttpClient|MockObject $httpClient;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->customerResource = new CustomerResource($this->httpClient, $this->logger);
    }

    /**
     * Create a mock ResponseInterface with JSON data
     */
    private function createJsonResponse(array $data): ResponseInterface
    {
        $json = json_encode($data);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($json);
        
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        
        return $response;
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(CustomerResource::class, $this->customerResource);
    }

    public function testCreateWithAllParameters(): void
    {
        $responseData = [
            'id' => 'customer-123',
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '+5511999999999',
            'document' => '12345678901',
            'document_type' => 'cpf',
            'status' => 'active',
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', '/customers', [
                'json' => [
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                    'phone' => '+5511999999999',
                    'document' => '12345678901',
                    'document_type' => 'cpf',
                    'metadata' => ['source' => 'test'],
                ],
            ])
            ->willReturn($this->createJsonResponse($responseData));

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $customer = $this->customerResource->create(
            'João Silva',
            'joao@example.com',
            '+5511999999999',
            '12345678901',
            'cpf',
            ['source' => 'test']
        );

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame('customer-123', $customer->id);
        $this->assertSame('João Silva', $customer->name);
        $this->assertSame('joao@example.com', $customer->email);
        $this->assertSame('+5511999999999', $customer->phone);
        $this->assertSame('12345678901', $customer->document);
        $this->assertSame('cpf', $customer->documentType);
    }

    public function testCreateWithMinimalParameters(): void
    {
        $responseData = [
            'id' => 'customer-123',
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'status' => 'active',
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', '/customers', [
                'json' => [
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                ],
            ])
            ->willReturn($this->createJsonResponse($responseData));

        $customer = $this->customerResource->create('João Silva', 'joao@example.com');

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame('customer-123', $customer->id);
        $this->assertSame('João Silva', $customer->name);
        $this->assertSame('joao@example.com', $customer->email);
    }

    public function testCreateWithApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('API Error'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error');

        $this->customerResource->create('João Silva', 'joao@example.com');
    }

    public function testCreateWithNetworkException(): void
    {
        $this->expectException(NetworkException::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new NetworkException('Network Error'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error');

        $this->customerResource->create('João Silva', 'joao@example.com');
    }

    public function testGet(): void
    {
        $responseData = [
            'id' => 'customer-123',
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'status' => 'active',
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/customers/customer-123')
            ->willReturn($this->createJsonResponse($responseData));

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $customer = $this->customerResource->get('customer-123');

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame('customer-123', $customer->id);
        $this->assertSame('João Silva', $customer->name);
        $this->assertSame('joao@example.com', $customer->email);
    }

    public function testGetWithApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('Customer not found'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error');

        $this->customerResource->get('customer-123');
    }

    public function testUpdate(): void
    {
        $updateData = [
            'name' => 'João Silva Santos',
            'phone' => '+5511888888888',
        ];

        $responseData = [
            'id' => 'customer-123',
            'name' => 'João Silva Santos',
            'email' => 'joao@example.com',
            'phone' => '+5511888888888',
            'status' => 'active',
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('PUT', '/customers/customer-123', [
                'json' => $updateData,
            ])
            ->willReturn($this->createJsonResponse($responseData));

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $customer = $this->customerResource->update('customer-123', $updateData);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame('customer-123', $customer->id);
        $this->assertSame('João Silva Santos', $customer->name);
        $this->assertSame('+5511888888888', $customer->phone);
    }

    public function testUpdateWithApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('Update failed'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error');

        $this->customerResource->update('customer-123', ['name' => 'New Name']);
    }

    public function testDelete(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', '/customers/customer-123')
            ->willReturn($this->createJsonResponse([]));

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $result = $this->customerResource->delete('customer-123');

        $this->assertTrue($result);
    }

    public function testDeleteWithApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('Delete failed'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error');

        $this->customerResource->delete('customer-123');
    }

    public function testListWithDefaults(): void
    {
        $responseData = [
            'data' => [
                [
                    'id' => 'customer-1',
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                    'status' => 'active',
                ],
                [
                    'id' => 'customer-2',
                    'name' => 'Maria Santos',
                    'email' => 'maria@example.com',
                    'status' => 'active',
                ],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => 2,
                'pages' => 1,
            ],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/customers', [
                'query' => [
                    'page' => 1,
                    'limit' => 20,
                ],
            ])
            ->willReturn($this->createJsonResponse($responseData));

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $result = $this->customerResource->list();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('customers', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['customers']);
        $this->assertContainsOnlyInstancesOf(Customer::class, $result['customers']);
        $this->assertSame('customer-1', $result['customers'][0]->id);
        $this->assertSame('customer-2', $result['customers'][1]->id);
        $this->assertSame(2, $result['pagination']['total']);
    }

    public function testListWithCustomParameters(): void
    {
        $responseData = [
            'data' => [
                [
                    'id' => 'customer-1',
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                    'status' => 'active',
                ],
            ],
            'pagination' => [
                'page' => 2,
                'limit' => 5,
                'total' => 10,
                'pages' => 2,
            ],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/customers', [
                'query' => [
                    'page' => 2,
                    'limit' => 5,
                    'status' => 'active',
                    'name' => 'João',
                ],
            ])
            ->willReturn($this->createJsonResponse($responseData));

        $result = $this->customerResource->list(2, 5, ['status' => 'active', 'name' => 'João']);

        $this->assertCount(1, $result['customers']);
        $this->assertSame(2, $result['pagination']['page']);
        $this->assertSame(5, $result['pagination']['limit']);
    }

    public function testListWithEmptyResponse(): void
    {
        $responseData = [
            'data' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => 0,
                'pages' => 0,
            ],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($this->createJsonResponse($responseData));

        $result = $this->customerResource->list();

        $this->assertCount(0, $result['customers']);
        $this->assertSame(0, $result['pagination']['total']);
    }

    public function testListWithApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('List failed'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error');

        $this->customerResource->list();
    }

    public function testSearch(): void
    {
        $responseData = [
            'data' => [
                [
                    'id' => 'customer-1',
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                    'status' => 'active',
                ],
            ],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/customers/search', [
                'query' => [
                    'q' => 'joao@example.com',
                    'limit' => 10,
                ],
            ])
            ->willReturn($this->createJsonResponse($responseData));

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $customers = $this->customerResource->search('joao@example.com');

        $this->assertIsArray($customers);
        $this->assertCount(1, $customers);
        $this->assertContainsOnlyInstancesOf(Customer::class, $customers);
        $this->assertSame('customer-1', $customers[0]->id);
        $this->assertSame('João Silva', $customers[0]->name);
    }

    public function testSearchWithCustomLimit(): void
    {
        $responseData = [
            'data' => [],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/customers/search', [
                'query' => [
                    'q' => 'test',
                    'limit' => 5,
                ],
            ])
            ->willReturn($this->createJsonResponse($responseData));

        $customers = $this->customerResource->search('test', 5);

        $this->assertCount(0, $customers);
    }

    public function testSearchWithApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new ApiException('Search failed'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error');

        $this->customerResource->search('test');
    }

    public function testSearchWithEmptyResponse(): void
    {
        $responseData = [];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($this->createJsonResponse($responseData));

        $customers = $this->customerResource->search('test');

        $this->assertIsArray($customers);
        $this->assertCount(0, $customers);
    }

    public function testSearchWithMissingDataKey(): void
    {
        $responseData = [
            'pagination' => ['total' => 0],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($this->createJsonResponse($responseData));

        $customers = $this->customerResource->search('test');

        $this->assertIsArray($customers);
        $this->assertCount(0, $customers);
    }
} 