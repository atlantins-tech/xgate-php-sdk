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
$logger = new Logger('xgate-debug');
$handler = new StreamHandler('php://stdout', Logger::DEBUG);
$logger->pushHandler($handler);

// Credenciais
$email = 'metamecadmin02314@gmail.com';
$password = 'leVDtcA9BJ11xI0Dt4F2Ew6Z4B';

try {
    echo "=== DEBUG DE HEADERS ===\n\n";

    // Inicializar cliente
    $client = new XGateClient($config, $logger);
    
    // Fazer login
    echo "1. Fazendo login...\n";
    $loginSuccess = $client->authenticate($email, $password);
    
    if ($loginSuccess) {
        echo "   ✅ Login realizado com sucesso!\n\n";
        
        // Obter o HttpClient para verificar headers padrão
        $httpClient = $client->getHttpClient();
        
        echo "2. Verificando headers padrão do HttpClient...\n";
        
        // Usar reflexão para acessar os headers padrão
        $reflection = new ReflectionClass($httpClient);
        $defaultHeadersProperty = $reflection->getProperty('defaultHeaders');
        $defaultHeadersProperty->setAccessible(true);
        $defaultHeaders = $defaultHeadersProperty->getValue($httpClient);
        
        echo "   Headers padrão: " . json_encode($defaultHeaders, JSON_PRETTY_PRINT) . "\n\n";
        
        // Verificar se há Authorization header
        if (isset($defaultHeaders['Authorization'])) {
            echo "   ✅ Authorization header encontrado: " . $defaultHeaders['Authorization'] . "\n\n";
        } else {
            echo "   ❌ Authorization header NÃO encontrado!\n\n";
        }
        
        echo "3. Testando requisição direta do HttpClient...\n";
        
        // Fazer uma requisição direta com o HttpClient
        try {
            $response = $httpClient->get('/exchange-rates/BRL/USDT');
            echo "   ✅ Requisição direta do HttpClient funcionou!\n";
        } catch (Exception $e) {
            echo "   ❌ Requisição direta do HttpClient falhou: " . $e->getMessage() . "\n";
        }
        
        echo "\n4. Testando requisição via XGateClient.get()...\n";
        
        // Fazer uma requisição via XGateClient
        try {
            $result = $client->get('/exchange-rates/BRL/USDT');
            echo "   ✅ Requisição via XGateClient funcionou!\n";
        } catch (Exception $e) {
            echo "   ❌ Requisição via XGateClient falhou: " . $e->getMessage() . "\n";
        }
        
        echo "\n5. Testando ExchangeRateResource...\n";
        
        // Testar ExchangeRateResource
        try {
            $exchangeResource = $client->getExchangeRateResource();
            $result = $exchangeResource->getExchangeRate('BRL', 'USDT');
            echo "   ✅ ExchangeRateResource funcionou!\n";
        } catch (Exception $e) {
            echo "   ❌ ExchangeRateResource falhou: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "   ❌ Falha no login\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO DEBUG ===\n"; 