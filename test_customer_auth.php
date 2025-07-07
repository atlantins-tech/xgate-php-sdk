<?php

require_once 'vendor/autoload.php';

use XGate\XGateClient;
use XGate\Configuration\ConfigurationManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Configuração
$config = [
    'base_url' => 'https://api.xgateglobal.com',
    'timeout' => 30,
    'debug' => true,
    'log_requests' => true,
    'retry_attempts' => 3,
];

// Logger
$logger = new Logger('xgate-test');
$handler = new StreamHandler('php://stdout', Logger::DEBUG);
$logger->pushHandler($handler);

// Credenciais
$email = 'metamecadmin02314@gmail.com';
$password = 'leVDtcA9BJ11xI0Dt4F2Ew6Z4B';

try {
    echo "=== TESTE ESPECÍFICO: CustomerResource e Autenticação ===\n\n";

    // Inicializar cliente
    $client = new XGateClient($config, $logger);
    
    // Teste 1: CustomerResource SEM autenticação
    echo "1. Testando CustomerResource SEM autenticação...\n";
    try {
        $customerResource = $client->getCustomerResource();
        
        $testCustomer = $customerResource->create(
            name: 'Teste Sem Auth ' . date('Y-m-d H:i:s'),
            email: 'teste_sem_auth_' . time() . '@example.com',
            phone: '+5511999999999',
            document: '12345678901'
        );
        
        echo "✅ CustomerResource SEM autenticação funcionou!\n";
        echo "   Cliente criado: " . $testCustomer->id . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ CustomerResource SEM autenticação falhou: " . $e->getMessage() . "\n\n";
    }

    // Autenticar
    echo "2. Autenticando...\n";
    $authenticated = $client->authenticate($email, $password);
    
    if (!$authenticated) {
        throw new Exception('Falha na autenticação');
    }
    
    echo "✅ Autenticação bem-sucedida!\n\n";

    // Teste 2: CustomerResource COM autenticação
    echo "3. Testando CustomerResource COM autenticação...\n";
    try {
        $customerResource = $client->getCustomerResource();
        
        $testCustomer = $customerResource->create(
            name: 'Teste Com Auth ' . date('Y-m-d H:i:s'),
            email: 'teste_com_auth_' . time() . '@example.com',
            phone: '+5511999999999',
            document: '12345678901'
        );
        
        echo "✅ CustomerResource COM autenticação funcionou!\n";
        echo "   Cliente criado: " . $testCustomer->id . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ CustomerResource COM autenticação falhou: " . $e->getMessage() . "\n\n";
    }

    // Teste 3: Verificar se HttpClient tem algum header especial
    echo "4. Verificando headers do HttpClient...\n";
    $httpClient = $client->getHttpClient();
    $defaultHeaders = $httpClient->getDefaultHeaders();
    
    echo "Headers padrão do HttpClient:\n";
    foreach ($defaultHeaders as $key => $value) {
        echo "   $key: $value\n";
    }
    echo "\n";

    // Teste 4: Verificar se há diferença na configuração
    echo "5. Verificando configuração...\n";
    $configuration = $client->getConfiguration();
    echo "Base URL: " . $configuration->getBaseUrl() . "\n";
    echo "Timeout: " . $configuration->getTimeout() . "\n";
    echo "Debug: " . ($configuration->isDebugMode() ? 'SIM' : 'NÃO') . "\n\n";

    // Teste 5: Tentar fazer requisição manual com HttpClient
    echo "6. Testando requisição manual com HttpClient...\n";
    try {
        $httpClient = $client->getHttpClient();
        $response = $httpClient->get('/customer');
        
        echo "✅ Requisição manual GET /customer funcionou!\n";
        echo "   Status: " . $response->getStatusCode() . "\n";
        echo "   Body: " . substr($response->getBody()->getContents(), 0, 200) . "...\n\n";
        
    } catch (Exception $e) {
        echo "❌ Requisição manual falhou: " . $e->getMessage() . "\n\n";
    }

} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 