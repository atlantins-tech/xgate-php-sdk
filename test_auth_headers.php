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
    echo "=== TESTE DE HEADERS DE AUTENTICAÇÃO AUTOMÁTICA ===\n\n";

    // Inicializar cliente
    $client = new XGateClient($config, $logger);
    
    echo "1. Testando recursos ANTES do login (deve falhar com 'Missing Authentication Token'):\n";
    
    // Teste CustomerResource ANTES do login
    try {
        $customerResource = $client->getCustomerResource();
        $result = $customerResource->create('Teste', 'teste@example.com');
        echo "   ❌ CustomerResource funcionou sem autenticação (não deveria!)\n";
    } catch (Exception $e) {
        echo "   ✅ CustomerResource falhou sem autenticação: " . $e->getMessage() . "\n";
    }
    
    // Teste ExchangeRateResource ANTES do login
    try {
        $exchangeResource = $client->getExchangeRateResource();
        $result = $exchangeResource->getExchangeRate('BRL', 'USDT');
        echo "   ❌ ExchangeRateResource funcionou sem autenticação (não deveria!)\n";
    } catch (Exception $e) {
        echo "   ✅ ExchangeRateResource falhou sem autenticação: " . $e->getMessage() . "\n";
    }
    
    echo "\n2. Fazendo login...\n";
    
    // Fazer login
    $loginSuccess = $client->authenticate($email, $password);
    
    if ($loginSuccess) {
        echo "   ✅ Login realizado com sucesso!\n";
        
        echo "\n3. Testando recursos APÓS o login (deve funcionar):\n";
        
        // Teste CustomerResource APÓS login
        try {
            $customerResource = $client->getCustomerResource();
            $result = $customerResource->create('Teste Auth', 'teste.auth@example.com');
            echo "   ✅ CustomerResource funcionou após autenticação!\n";
        } catch (Exception $e) {
            echo "   ❌ CustomerResource ainda falhou após autenticação: " . $e->getMessage() . "\n";
        }
        
        // Teste ExchangeRateResource APÓS login
        try {
            $exchangeResource = $client->getExchangeRateResource();
            $result = $exchangeResource->getExchangeRate('BRL', 'USDT');
            echo "   ✅ ExchangeRateResource funcionou após autenticação!\n";
            echo "   Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        } catch (Exception $e) {
            echo "   ❌ ExchangeRateResource ainda falhou após autenticação: " . $e->getMessage() . "\n";
        }
        
        echo "\n4. Fazendo logout...\n";
        
        // Fazer logout
        $logoutSuccess = $client->logout();
        
        if ($logoutSuccess) {
            echo "   ✅ Logout realizado com sucesso!\n";
            
            echo "\n5. Testando recursos APÓS logout (deve falhar novamente):\n";
            
            // Teste CustomerResource APÓS logout
            try {
                $customerResource = $client->getCustomerResource();
                $result = $customerResource->create('Teste Logout', 'teste.logout@example.com');
                echo "   ❌ CustomerResource funcionou após logout (não deveria!)\n";
            } catch (Exception $e) {
                echo "   ✅ CustomerResource falhou após logout: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ❌ Falha no logout\n";
        }
        
    } else {
        echo "   ❌ Falha no login\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO TESTE ===\n"; 