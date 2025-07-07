<?php

require_once 'vendor/autoload.php';

use XGate\XGateClient;
use XGate\Configuration\ConfigurationManager;
use XGate\Resource\DepositResource;
use XGate\Model\Transaction;
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
    echo "=== TESTE DO DEPOSITRESOURCE ===\n\n";

    // Inicializar cliente
    $client = new XGateClient($config, $logger);
    
    // Autenticar
    echo "1. Autenticando...\n";
    $authenticated = $client->authenticate($email, $password);
    
    if (!$authenticated) {
        throw new Exception('Falha na autenticação');
    }
    
    echo "✅ Autenticação bem-sucedida!\n\n";

    // Teste 1: Criar DepositResource manualmente (como seria usado)
    echo "2. Testando DepositResource...\n";
    try {
        $httpClient = $client->getHttpClient();
        $depositResource = new DepositResource($httpClient, $logger);
        
        // Tentar listar moedas suportadas
        $currencies = $depositResource->listSupportedCurrencies();
        
        echo "✅ DepositResource funcionou!\n";
        echo "   Moedas suportadas: " . implode(', ', $currencies) . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ DepositResource falhou: " . $e->getMessage() . "\n\n";
    }

    // Teste 2: Tentar criar um depósito
    echo "3. Testando criação de depósito...\n";
    try {
        $httpClient = $client->getHttpClient();
        $depositResource = new DepositResource($httpClient, $logger);
        
        $transaction = new Transaction(
            id: null,
            amount: '100.50',
            currency: 'BRL',
            accountId: 'acc_test_123',
            paymentMethod: 'bank_transfer',
            type: 'deposit',
            referenceId: 'test_deposit_' . time(),
            description: 'Teste de depósito'
        );
        
        $result = $depositResource->createDeposit($transaction);
        
        echo "✅ Criação de depósito funcionou!\n";
        echo "   ID do depósito: " . $result->id . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Criação de depósito falhou: " . $e->getMessage() . "\n\n";
    }

    echo "=== CONCLUSÃO ===\n";
    echo "Se DepositResource falhar com 'No token provided', confirma que todos os resources\n";
    echo "que usam HttpClient diretamente têm o mesmo problema de autenticação.\n";

} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 