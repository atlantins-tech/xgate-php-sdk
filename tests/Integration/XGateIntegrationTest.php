<?php

declare(strict_types=1);

namespace XGate\Tests\Integration;

use PHPUnit\Framework\TestCase;
use XGate\XGateClient;
use XGate\Resource\CustomerResource;
use XGate\Resource\DepositResource;
use XGate\Model\Customer;
use XGate\Model\Transaction;
use XGate\Exception\AuthenticationException;
use XGate\Exception\ApiException;
use XGate\Exception\XGateException;

/**
 * Teste de Integração do SDK XGATE
 * 
 * Este teste valida o funcionamento completo do SDK em ambiente real,
 * testando o fluxo: autenticação → cadastro cliente → geração depósito
 * 
 * Configuração necessária:
 * - Arquivo .env na raiz do projeto com XGATE_EMAIL e XGATE_PASSWORD
 * - Credenciais válidas da XGATE
 * 
 * Para executar:
 * ```bash
 * vendor/bin/phpunit tests/Integration/XGateIntegrationTest.php --verbose
 * ```
 */
class XGateIntegrationTest extends TestCase
{
    private XGateClient $client;
    private CustomerResource $customerResource;
    private DepositResource $depositResource;
    private string $testEmail;
    private string $testPassword;
    
    /**
     * Configuração inicial do teste
     * Carrega variáveis de ambiente e inicializa cliente
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Carregar variáveis de ambiente
        $this->loadEnvironmentVariables();
        
        // Validar credenciais
        $this->testEmail = $_ENV['XGATE_EMAIL'] ?? '';
        $this->testPassword = $_ENV['XGATE_PASSWORD'] ?? '';
        
        if (empty($this->testEmail) || empty($this->testPassword)) {
            $this->markTestSkipped(
                'Credenciais XGATE não configuradas. Configure XGATE_EMAIL e XGATE_PASSWORD no arquivo .env'
            );
        }
        
        // Inicializar cliente XGATE
        $this->client = new XGateClient([
            'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgate.com',
            'environment' => $_ENV['XGATE_ENVIRONMENT'] ?? 'production',
            'timeout' => 60,
            'debug' => true,
        ]);
        
        // Inicializar recursos
        $this->customerResource = new CustomerResource(
            $this->client->getHttpClient(), 
            $this->client->getLogger()
        );
        
        $this->depositResource = new DepositResource(
            $this->client->getHttpClient(), 
            $this->client->getLogger()
        );
    }
    
    /**
     * Carrega variáveis de ambiente do arquivo .env
     */
    private function loadEnvironmentVariables(): void
    {
        $envFile = __DIR__ . '/../../.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                }
            }
        }
    }
    
    /**
     * Gera dados de teste para cliente
     */
    private function generateTestCustomerData(): array
    {
        $timestamp = time();
        return [
            'name' => 'João Silva Teste Integration',
            'email' => "integration.test.{$timestamp}@exemplo.com",
            'phone' => '+5511999999999',
            'document' => '12345678901',
            'document_type' => 'cpf'
        ];
    }
    
    /**
     * Teste: Inicialização do cliente
     */
    public function testClientInitialization(): void
    {
        $this->assertInstanceOf(XGateClient::class, $this->client);
        $this->assertTrue($this->client->isInitialized());
        $this->assertNotEmpty($this->client->getVersion());
        $this->assertStringContainsString('api.xgate.com', $this->client->getConfiguration()->getBaseUrl());
    }
    
    /**
     * Teste: Autenticação com credenciais reais
     */
    public function testAuthentication(): void
    {
        // Verificar que não está autenticado inicialmente
        $this->assertFalse($this->client->isAuthenticated());
        
        // Realizar autenticação
        $authenticated = $this->client->authenticate($this->testEmail, $this->testPassword);
        
        // Validar autenticação
        $this->assertTrue($authenticated, 'Autenticação deveria ter sido bem-sucedida');
        $this->assertTrue($this->client->isAuthenticated(), 'Cliente deveria estar autenticado');
    }
    
    /**
     * Teste: Falha de autenticação com credenciais inválidas
     */
    public function testAuthenticationFailure(): void
    {
        $this->expectException(AuthenticationException::class);
        
        $this->client->authenticate('email-inexistente@exemplo.com', 'senha-incorreta');
    }
    
    /**
     * Teste: Cadastro e recuperação de cliente
     * 
     * @depends testAuthentication
     */
    public function testCustomerCreationAndRetrieval(): Customer
    {
        // Autenticar primeiro
        $this->client->authenticate($this->testEmail, $this->testPassword);
        
        // Gerar dados de teste
        $testData = $this->generateTestCustomerData();
        
        // Cadastrar cliente
        $customer = $this->customerResource->create(
            name: $testData['name'],
            email: $testData['email'],
            phone: $testData['phone'],
            document: $testData['document'],
            documentType: $testData['document_type']
        );
        
        // Validar cliente criado
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertNotEmpty($customer->id);
        $this->assertEquals($testData['name'], $customer->name);
        $this->assertEquals($testData['email'], $customer->email);
        $this->assertEquals($testData['phone'], $customer->phone);
        $this->assertEquals('active', $customer->status);
        
        // Buscar cliente criado
        $retrievedCustomer = $this->customerResource->get($customer->id);
        
        // Validar dados recuperados
        $this->assertEquals($customer->id, $retrievedCustomer->id);
        $this->assertEquals($customer->name, $retrievedCustomer->name);
        $this->assertEquals($customer->email, $retrievedCustomer->email);
        
        return $customer;
    }
    
    /**
     * Teste: Criação e verificação de depósito
     * 
     * @depends testCustomerCreationAndRetrieval
     */
    public function testDepositCreationAndVerification(): Transaction
    {
        // Autenticar e criar cliente
        $this->client->authenticate($this->testEmail, $this->testPassword);
        
        $testData = $this->generateTestCustomerData();
        $customer = $this->customerResource->create(
            name: $testData['name'],
            email: $testData['email'],
            phone: $testData['phone'],
            document: $testData['document'],
            documentType: $testData['document_type']
        );
        
        // Criar transação de depósito
        $depositTransaction = new Transaction(
            id: null,
            amount: '150.75',
            currency: 'BRL',
            accountId: $customer->id,
            paymentMethod: 'crypto_transfer',
            type: 'deposit',
            referenceId: 'integration_test_' . time(),
            description: 'Depósito de teste via PHPUnit',
            callbackUrl: 'https://webhook.site/phpunit-test'
        );
        
        // Criar depósito
        $createdDeposit = $this->depositResource->createDeposit($depositTransaction);
        
        // Validar depósito criado
        $this->assertInstanceOf(Transaction::class, $createdDeposit);
        $this->assertNotEmpty($createdDeposit->id);
        $this->assertEquals('150.75', $createdDeposit->amount);
        $this->assertEquals('BRL', $createdDeposit->currency);
        $this->assertEquals($customer->id, $createdDeposit->accountId);
        $this->assertEquals('crypto_transfer', $createdDeposit->paymentMethod);
        $this->assertEquals('deposit', $createdDeposit->type);
        $this->assertTrue($createdDeposit->isDeposit());
        $this->assertFalse($createdDeposit->isWithdrawal());
        
        // Verificar status do depósito
        $depositStatus = $this->depositResource->getDeposit($createdDeposit->id);
        
        // Validar status
        $this->assertEquals($createdDeposit->id, $depositStatus->id);
        $this->assertContains($depositStatus->status, ['pending', 'completed', 'processing']);
        $this->assertNotNull($depositStatus->createdAt);
        
        return $createdDeposit;
    }
    
    /**
     * Teste: Listagem de moedas suportadas
     */
    public function testListSupportedCurrencies(): void
    {
        // Autenticar
        $this->client->authenticate($this->testEmail, $this->testPassword);
        
        // Listar moedas suportadas
        $currencies = $this->depositResource->listSupportedCurrencies();
        
        // Validar resposta
        $this->assertIsArray($currencies);
        $this->assertNotEmpty($currencies);
        $this->assertContains('BRL', $currencies, 'BRL deveria estar nas moedas suportadas');
        
        // Verificar se são códigos de moeda válidos (3 caracteres)
        foreach ($currencies as $currency) {
            $this->assertIsString($currency);
            $this->assertEquals(3, strlen($currency), "Código de moeda '{$currency}' deveria ter 3 caracteres");
            $this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $currency, "Código de moeda '{$currency}' deveria ser maiúsculo");
        }
    }
    
    /**
     * Teste: Cenários de erro
     */
    public function testErrorScenarios(): void
    {
        // Autenticar
        $this->client->authenticate($this->testEmail, $this->testPassword);
        
        // Teste 1: Buscar cliente inexistente
        $this->expectException(ApiException::class);
        $this->customerResource->get('cliente-inexistente-123');
    }
    
    /**
     * Teste: Logout
     */
    public function testLogout(): void
    {
        // Autenticar primeiro
        $this->client->authenticate($this->testEmail, $this->testPassword);
        $this->assertTrue($this->client->isAuthenticated());
        
        // Fazer logout
        $logoutSuccess = $this->client->logout();
        
        // Validar logout
        $this->assertTrue($logoutSuccess);
        $this->assertFalse($this->client->isAuthenticated());
    }
    
    /**
     * Teste: Fluxo completo integrado
     * 
     * Este teste executa todo o fluxo em uma única operação para validar
     * que tudo funciona em conjunto sem problemas de estado.
     */
    public function testCompleteIntegratedFlow(): void
    {
        // 1. Autenticação
        $authenticated = $this->client->authenticate($this->testEmail, $this->testPassword);
        $this->assertTrue($authenticated, 'Falha na autenticação');
        
        // 2. Cadastro de cliente
        $testData = $this->generateTestCustomerData();
        $customer = $this->customerResource->create(
            name: $testData['name'],
            email: $testData['email'],
            phone: $testData['phone'],
            document: $testData['document'],
            documentType: $testData['document_type']
        );
        $this->assertNotEmpty($customer->id, 'Cliente não foi criado');
        
        // 3. Criação de depósito
        $depositTransaction = new Transaction(
            id: null,
            amount: '99.99',
            currency: 'BRL',
            accountId: $customer->id,
            paymentMethod: 'crypto_transfer',
            type: 'deposit',
            referenceId: 'complete_flow_test_' . time(),
            description: 'Teste de fluxo completo'
        );
        
        $deposit = $this->depositResource->createDeposit($depositTransaction);
        $this->assertNotEmpty($deposit->id, 'Depósito não foi criado');
        
        // 4. Verificação de status
        $depositStatus = $this->depositResource->getDeposit($deposit->id);
        $this->assertEquals($deposit->id, $depositStatus->id, 'Status do depósito não confere');
        
        // 5. Listagem de moedas
        $currencies = $this->depositResource->listSupportedCurrencies();
        $this->assertNotEmpty($currencies, 'Lista de moedas está vazia');
        
        // 6. Busca de cliente
        $retrievedCustomer = $this->customerResource->get($customer->id);
        $this->assertEquals($customer->id, $retrievedCustomer->id, 'Cliente recuperado não confere');
        
        // 7. Logout
        $logoutSuccess = $this->client->logout();
        $this->assertTrue($logoutSuccess, 'Logout falhou');
        
        // Resultado final
        $this->addToAssertionCount(1); // Marca teste como bem-sucedido
    }
    
    /**
     * Limpeza após cada teste
     */
    protected function tearDown(): void
    {
        // Fazer logout se ainda estiver autenticado
        if (isset($this->client) && $this->client->isAuthenticated()) {
            try {
                $this->client->logout();
            } catch (\Exception $e) {
                // Ignorar erros de logout na limpeza
            }
        }
        
        parent::tearDown();
    }
} 