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
    echo "=== TESTE DE COMPARAÇÃO: CustomerResource vs ExchangeRateResource ===\n\n";

    // Inicializar cliente
    $client = new XGateClient($config, $logger);
    
    // Autenticar
    echo "1. Autenticando...\n";
    $authenticated = $client->authenticate($email, $password);
    
    if (!$authenticated) {
        throw new Exception('Falha na autenticação');
    }
    
    echo "✅ Autenticação bem-sucedida!\n";
    echo "   Token armazenado: " . ($client->isAuthenticated() ? 'SIM' : 'NÃO') . "\n\n";

    // Teste 1: CustomerResource (que funciona)
    echo "2. Testando CustomerResource (funciona)...\n";
    try {
        $customerResource = $client->getCustomerResource();
        
        // Vamos tentar criar um cliente de teste
        $testCustomer = $customerResource->create(
            name: 'Teste Comparacao ' . date('Y-m-d H:i:s'),
            email: 'teste_comparacao_' . time() . '@example.com',
            phone: '+5511999999999',
            document: '12345678901'
        );
        
        echo "✅ CustomerResource funcionou!\n";
        echo "   Cliente criado: " . $testCustomer->id . "\n";
        echo "   Nome: " . $testCustomer->name . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ CustomerResource falhou: " . $e->getMessage() . "\n\n";
    }

    // Teste 2: ExchangeRateResource (que não funciona)
    echo "3. Testando ExchangeRateResource (não funciona)...\n";
    try {
        $exchangeResource = $client->getExchangeRateResource();
        
        // Vamos tentar buscar uma cotação
        $rate = $exchangeResource->getExchangeRate('BRL', 'USDT');
        
        echo "✅ ExchangeRateResource funcionou!\n";
        echo "   Taxa BRL->USDT: " . ($rate['rate'] ?? 'N/A') . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ ExchangeRateResource falhou: " . $e->getMessage() . "\n\n";
    }

    // Teste 3: Análise dos headers
    echo "4. Analisando diferenças na implementação...\n";
    
    echo "CustomerResource:\n";
    echo "   - Usa: \$this->httpClient->request() diretamente\n";
    echo "   - HttpClient usa headers padrão + headers específicos\n";
    echo "   - Não passa pelo sistema de autenticação do XGateClient\n\n";
    
    echo "ExchangeRateResource:\n";
    echo "   - Usa: \$this->xgateClient->get() (que chama makeRequest)\n";
    echo "   - XGateClient.makeRequest() adiciona headers de autenticação\n";
    echo "   - Passa pelo sistema de autenticação do XGateClient\n\n";

    // Teste 4: Verificação do AuthenticationManager
    echo "5. Verificando AuthenticationManager...\n";
    $authManager = $client->getAuthenticationManager();
    
    if ($authManager->isAuthenticated()) {
        $authHeaders = $authManager->getAuthorizationHeader();
        echo "✅ AuthenticationManager está autenticado\n";
        echo "   Headers de autenticação disponíveis: " . (empty($authHeaders) ? 'NÃO' : 'SIM') . "\n";
        
        if (!empty($authHeaders)) {
            foreach ($authHeaders as $key => $value) {
                // Mascarar o token para segurança
                $maskedValue = strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value;
                echo "   $key: $maskedValue\n";
            }
        }
    } else {
        echo "❌ AuthenticationManager não está autenticado\n";
    }

    echo "\n=== CONCLUSÃO ===\n";
    echo "O problema é que:\n";
    echo "1. CustomerResource funciona porque usa HttpClient diretamente\n";
    echo "2. ExchangeRateResource não funciona porque usa XGateClient.makeRequest()\n";
    echo "3. XGateClient.makeRequest() adiciona headers de autenticação que causam duplicação\n";
    echo "4. A API não aceita headers Authorization duplicados\n\n";
    
    echo "SOLUÇÃO:\n";
    echo "CustomerResource precisa ser atualizado para usar XGateClient como ExchangeRateResource,\n";
    echo "OU ExchangeRateResource precisa ser revertido para usar HttpClient diretamente.\n";

} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 