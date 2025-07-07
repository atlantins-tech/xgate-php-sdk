<?php

require_once 'vendor/autoload.php';

use XGate\XGateClient;
use XGate\Configuration\ConfigurationManager;
use XGate\Resource\DepositResource;
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
$logger = new Logger('xgate-deposit');
$handler = new StreamHandler('php://stdout', Logger::DEBUG);
$logger->pushHandler($handler);

// Credenciais
$email = 'metamecadmin02314@gmail.com';
$password = 'leVDtcA9BJ11xI0Dt4F2Ew6Z4B';

try {
    echo "=== TESTE COMPLETO DO DEPOSITRESOURCE ===\n\n";

    // Inicializar cliente
    $client = new XGateClient($config, $logger);
    
    // Fazer login
    echo "1. Fazendo login...\n";
    $loginSuccess = $client->authenticate($email, $password);
    
    if ($loginSuccess) {
        echo "   ✅ Login realizado com sucesso!\n\n";
        
        echo "2. Testando endpoint de moedas da empresa...\n";
        
        // Testar endpoint de moedas da empresa
        try {
            $currencies = $client->get('/deposit/company/currencies');
            echo "   ✅ Moedas da empresa obtidas com sucesso!\n";
            echo "   Moedas disponíveis: " . json_encode($currencies, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Falha ao obter moedas da empresa: " . $e->getMessage() . "\n\n";
            return;
        }
        
        echo "3. Testando endpoint de criptomoedas da empresa...\n";
        
        // Testar endpoint de criptomoedas da empresa
        try {
            $cryptocurrencies = $client->get('/deposit/company/cryptocurrencies');
            echo "   ✅ Criptomoedas da empresa obtidas com sucesso!\n";
            echo "   Criptomoedas disponíveis: " . json_encode($cryptocurrencies, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Falha ao obter criptomoedas da empresa: " . $e->getMessage() . "\n\n";
            return;
        }
        
        echo "4. Criando um cliente para o teste de depósito...\n";
        
        // Criar um cliente primeiro
        $customerId = null;
        try {
            $customerResource = $client->getCustomerResource();
            $customer = $customerResource->create(
                'Cliente Deposito Teste',
                'cliente.deposito.teste@example.com',
                '11999887766',
                '12345678901'
            );
            $customerId = $customer->id;
            echo "   ✅ Cliente criado para teste: ID = {$customerId}\n\n";
        } catch (Exception $e) {
            echo "   ❌ Falha ao criar cliente: " . $e->getMessage() . "\n\n";
            return;
        }
        
        echo "5. Testando criação de depósito com customerId...\n";
        
        // Testar criação de depósito usando customerId
        try {
            $depositData = [
                'amount' => 10.90,
                'customerId' => $customerId,
                'currency' => $currencies[0],
                'cryptocurrency' => $cryptocurrencies[0]
            ];
            
            $depositResult = $client->post('/deposit', $depositData);
            echo "   ✅ Depósito com customerId criado com sucesso!\n";
            echo "   Resultado: " . json_encode($depositResult, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Falha ao criar depósito com customerId: " . $e->getMessage() . "\n\n";
        }
        
        echo "6. Testando criação de depósito com dados do cliente inline...\n";
        
        // Testar criação de depósito com dados do cliente inline
        try {
            $depositData = [
                'amount' => 25.50,
                'customer' => [
                    'name' => 'Cliente Inline',
                    'phone' => '11888777666',
                    'email' => 'cliente.inline@example.com',
                    'document' => '98765432100'
                ],
                'currency' => $currencies[0],
                'cryptocurrency' => $cryptocurrencies[0]
            ];
            
            $depositResult = $client->post('/deposit', $depositData);
            echo "   ✅ Depósito com cliente inline criado com sucesso!\n";
            echo "   Resultado: " . json_encode($depositResult, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Falha ao criar depósito com cliente inline: " . $e->getMessage() . "\n\n";
        }
        
        echo "7. Testando DepositResource diretamente...\n";
        
        // Testar usando DepositResource diretamente
        try {
            $httpClient = $client->getHttpClient();
            $depositResource = new DepositResource($httpClient, $logger);
            
            // Criar transação usando DepositResource
            $transaction = $depositResource->create([
                'amount' => 50.00,
                'customerId' => $customerId,
                'currency' => $currencies[0],
                'cryptocurrency' => $cryptocurrencies[0]
            ]);
            
            echo "   ✅ DepositResource funcionou diretamente!\n";
            echo "   Transação criada: " . json_encode([
                'id' => $transaction->id,
                'status' => $transaction->status,
                'amount' => $transaction->amount
            ], JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Falha no DepositResource direto: " . $e->getMessage() . "\n\n";
        }
        
    } else {
        echo "   ❌ Falha no login\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO TESTE ===\n"; 