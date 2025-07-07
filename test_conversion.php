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
$logger = new Logger('xgate-conversion');
$handler = new StreamHandler('php://stdout', Logger::DEBUG);
$logger->pushHandler($handler);

// Credenciais
$email = 'metamecadmin02314@gmail.com';
$password = 'leVDtcA9BJ11xI0Dt4F2Ew6Z4B';

try {
    echo "=== TESTE DE CONVERSÃO COM ENDPOINT OFICIAL ===\n\n";

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
        }
        
        echo "3. Testando conversão BRL -> USDT com endpoint oficial...\n";
        
        // Testar conversão usando o método atualizado
        try {
            $exchangeResource = $client->getExchangeRateResource();
            $result = $exchangeResource->convertAmount(100.0, 'BRL', 'USDT');
            echo "   ✅ Conversão funcionou com endpoint oficial!\n";
            echo "   Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Conversão falhou: " . $e->getMessage() . "\n\n";
        }
        
        echo "4. Testando método de conveniência do XGateClient...\n";
        
        // Testar método de conveniência
        try {
            $result = $client->convertAmount(250.0, 'BRL', 'USDT');
            echo "   ✅ Método de conveniência funcionou!\n";
            echo "   Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Método de conveniência falhou: " . $e->getMessage() . "\n\n";
        }
        
        echo "5. Testando com valor menor...\n";
        
        // Testar com valor menor
        try {
            $result = $client->convertAmount(10.90, 'BRL', 'USDT');
            echo "   ✅ Conversão com valor menor funcionou!\n";
            echo "   Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "   ❌ Conversão com valor menor falhou: " . $e->getMessage() . "\n\n";
        }
        
    } else {
        echo "   ❌ Falha no login\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO TESTE ===\n"; 