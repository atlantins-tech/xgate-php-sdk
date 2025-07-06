<?php

declare(strict_types=1);

namespace XGate\Tests\Resource;

use PHPUnit\Framework\TestCase;
use XGate\Resource\CustomerResource;
use XGate\Http\HttpClient;
use XGate\Model\Customer;
use XGate\Exception\ApiException;
use XGate\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Testes específicos para validação de dados do CustomerResource
 * 
 * Este teste documenta o comportamento real da API XGATE em relação à validação
 * de dados de entrada, baseado nos testes de integração realizados.
 */
class CustomerResourceValidationTest extends TestCase
{
    private CustomerResource $customerResource;
    private HttpClient $httpClient;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->customerResource = new CustomerResource($this->httpClient, $this->logger);
    }

    /**
     * Testa que a API XGATE aceita emails inválidos
     * 
     * Baseado nos testes de integração, a API não valida o formato de email
     * e aceita valores como "email-inválido" sem retornar erro.
     */
    public function testApiAcceptsInvalidEmails(): void
    {
        // Simula resposta da API que aceita email inválido
        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')
            ->willReturn(json_encode([
                'customer' => [
                    '_id' => '6869ccee3b850fcb394b6fad',
                    'name' => 'Teste',
                    'email' => 'email-inválido'
                ]
            ]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', '/customer', [
                'json' => [
                    'name' => 'Teste',
                    'email' => 'email-inválido',
                    'document' => '12345678901'
                ]
            ])
            ->willReturn($response);

        // A API aceita emails inválidos - não deve lançar exceção
        $customer = $this->customerResource->create('Teste', 'email-inválido', null, '12345678901');
        
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('email-inválido', $customer->email);
        $this->assertEquals('6869ccee3b850fcb394b6fad', $customer->id);
    }

    /**
     * Testa que a API XGATE rejeita nomes vazios
     * 
     * Baseado nos testes de integração, a API corretamente valida
     * que o nome é obrigatório e retorna erro quando está vazio.
     */
    public function testApiRejectsEmptyNames(): void
    {
        // Simula resposta de erro da API para nome vazio
        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')
            ->willReturn(json_encode([
                'error' => 'Nome do Cliente é obrigatório'
            ]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);
        $response->method('getStatusCode')->willReturn(400);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', '/customer', [
                'json' => [
                    'name' => '',
                    'email' => 'teste@exemplo.com',
                    'document' => '12345678901'
                ]
            ])
            ->willThrowException(new ApiException('Erro da API: Nome do Cliente é obrigatório', 400));

        // A API deve rejeitar nomes vazios
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Nome do Cliente é obrigatório');
        
        $this->customerResource->create('', 'teste@exemplo.com', null, '12345678901');
    }

    /**
     * Testa que a API XGATE aceita documentos inválidos
     * 
     * Baseado nos testes de integração, a API não valida o formato
     * de documento e aceita valores muito curtos como "123".
     */
    public function testApiAcceptsInvalidDocuments(): void
    {
        // Simula resposta da API que aceita documento inválido
        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')
            ->willReturn(json_encode([
                'customer' => [
                    '_id' => '6869ccee3b850fcb394b6fad',
                    'name' => 'Teste',
                    'email' => 'teste@exemplo.com'
                ]
            ]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', '/customer', [
                'json' => [
                    'name' => 'Teste',
                    'email' => 'teste@exemplo.com',
                    'document' => '123'
                ]
            ])
            ->willReturn($response);

        // A API aceita documentos inválidos - não deve lançar exceção
        $customer = $this->customerResource->create('Teste', 'teste@exemplo.com', null, '123');
        
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('teste@exemplo.com', $customer->email);
        $this->assertEquals('6869ccee3b850fcb394b6fad', $customer->id);
    }

    /**
     * Testa criação de cliente com dados válidos
     * 
     * Verifica que clientes com dados válidos são criados com sucesso.
     */
    public function testCreateValidCustomer(): void
    {
        // Simula resposta da API para cliente válido
        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')
            ->willReturn(json_encode([
                'customer' => [
                    '_id' => '6869cf8f3b850fcb394b756c',
                    'name' => 'João Silva',
                    'email' => 'joao.silva@exemplo.com'
                ]
            ]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', '/customer', [
                'json' => [
                    'name' => 'João Silva',
                    'email' => 'joao.silva@exemplo.com',
                    'phone' => '+5511999999999',
                    'document' => '12345678901'
                ]
            ])
            ->willReturn($response);

        $customer = $this->customerResource->create(
            'João Silva',
            'joao.silva@exemplo.com',
            '+5511999999999',
            '12345678901'
        );
        
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('joao.silva@exemplo.com', $customer->email);
        $this->assertEquals('João Silva', $customer->name);
        $this->assertEquals('6869cf8f3b850fcb394b756c', $customer->id);
    }
} 