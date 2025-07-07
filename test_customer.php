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
$logger = new Logger('xgate-customer');
$handler = new StreamHandler('php://stdout', Logger::DEBUG);
$logger->pushHandler($handler);

// Credenciais
$email = 'metamecadmin02314@gmail.com';
$password = 'leVDtcA9BJ11xI0Dt4F2Ew6Z4B';

try {
    echo "=== TESTE DO CUSTOMERRESOURCE ===\n\n";

    // Inicializar cliente
    $client = new XGateClient($config, $logger);
    
    // Fazer login
    echo "1. Fazendo login...\n";
    $loginSuccess = $client->authenticate($email, $password);
    
    if ($loginSuccess) {
        echo "   ✅ Login realizado com sucesso!\n\n";
        
        echo "2. Testando CustomerResource - Criação de cliente...\n";
        
        // Testar criação de cliente
        try {
            $customerResource = $client->getCustomerResource();
            
            // Dados do cliente seguindo a documentação oficial
            $customerData = [
                'name' => 'Cliente Teste SDK',
                'phone' => '11999999999',
                'email' => 'cliente.teste.sdk@example.com',
                'document' => '12345678901'
            ];
            
            $customer = $customerResource->create(
                $customerData['name'],
                $customerData['email'],
                $customerData['phone'],
                $customerData['document']
            );
            
            echo "   ✅ Cliente criado com sucesso!\n";
            echo "   Dados do cliente: " . json_encode([
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'document' => $customer->document
            ], JSON_PRETTY_PRINT) . "\n\n";
            
            // Testar busca do cliente criado
            echo "3. Testando busca do cliente criado...\n";
            
            try {
                $retrievedCustomer = $customerResource->get($customer->id);
                echo "   ✅ Cliente recuperado com sucesso!\n";
                echo "   Dados recuperados: " . json_encode([
                    'id' => $retrievedCustomer->id,
                    'name' => $retrievedCustomer->name,
                    'email' => $retrievedCustomer->email
                ], JSON_PRETTY_PRINT) . "\n\n";
            } catch (Exception $e) {
                echo "   ❌ Falha ao recuperar cliente: " . $e->getMessage() . "\n\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Falha ao criar cliente: " . $e->getMessage() . "\n\n";
        }
        
        echo "4. Testando criação de cliente com dados mínimos...\n";
        
        // Testar com dados mínimos (só nome e email)
        try {
            $customerResource = $client->getCustomerResource();
            
            $customer = $customerResource->create(
                'Cliente Minimo',
                'cliente.minimo@example.com'
            );
            
            echo "   ✅ Cliente com dados mínimos criado com sucesso!\n";
            echo "   ID: " . $customer->id . "\n";
            echo "   Nome: " . $customer->name . "\n";
            echo "   Email: " . $customer->email . "\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ Falha ao criar cliente com dados mínimos: " . $e->getMessage() . "\n\n";
        }
        
        echo "5. Testando requisição direta ao endpoint /customer...\n";
        
        // Testar requisição direta ao endpoint
        try {
            $customerData = [
                'name' => 'Cliente Direto',
                'email' => 'cliente.direto@example.com',
                'phone' => '11888888888',
                'document' => '98765432100'
            ];
            
            $response = $client->post('/customer', $customerData);
            echo "   ✅ Requisição direta ao endpoint funcionou!\n";
            echo "   Resposta: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ Falha na requisição direta: " . $e->getMessage() . "\n\n";
        }
        
    } else {
        echo "   ❌ Falha no login\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO TESTE ===\n"; 