<?php

/**
 * Exemplo de uso das funcionalidades de taxa de câmbio do SDK XGATE
 *
 * Este exemplo demonstra como usar as funcionalidades de taxa de câmbio
 * para converter valores entre moedas fiduciárias e criptomoedas.
 *
 * @author XGATE Development Team
 * @since 1.0.1
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use XGate\Exception\AuthenticationException;

// Configuração do cliente
$config = [
    'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgate.com',
    'timeout' => 30,
    'debug' => true,
    'log_file' => __DIR__ . '/../logs/exchange_rate_example.log',
];

echo "=== XGATE SDK - Exchange Rate Example ===\n\n";

try {
    // Inicializar cliente
    echo "1. Inicializando cliente XGATE...\n";
    $client = new XGateClient($config);
    echo "   ✅ Cliente inicializado com sucesso!\n\n";

    // Autenticar
    echo "2. Autenticando usuário...\n";
    $email = $_ENV['XGATE_EMAIL'] ?? '';
    $password = $_ENV['XGATE_PASSWORD'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new \Exception('XGATE_EMAIL e XGATE_PASSWORD devem estar definidos no arquivo .env');
    }
    
    $authenticated = $client->authenticate($email, $password);
    
    if (!$authenticated) {
        throw new AuthenticationException('Falha na autenticação');
    }
    
    echo "   ✅ Autenticação realizada com sucesso!\n\n";

    // Teste 1: Obter taxa de câmbio BRL para USDT
    echo "3. Obtendo taxa de câmbio BRL → USDT...\n";
    try {
        $rate = $client->getExchangeRate('BRL', 'USDT');
        echo "   ✅ Taxa obtida com sucesso!\n";
        echo "   📊 1 USDT = " . number_format($rate['rate'], 2, ',', '.') . " BRL\n";
        echo "   🕒 Timestamp: " . ($rate['timestamp'] ?? 'N/A') . "\n";
        echo "   🔄 Fonte: " . ($rate['source'] ?? 'N/A') . "\n\n";
    } catch (ApiException $e) {
        echo "   ❌ Erro na API: " . $e->getMessage() . "\n";
        echo "   ℹ️  Usando dados simulados para continuar o exemplo...\n";
        $rate = [
            'rate' => 5.45,
            'from_currency' => 'BRL',
            'to_currency' => 'USDT',
            'timestamp' => date('c'),
            'source' => 'simulado'
        ];
        echo "   📊 1 USDT = " . number_format($rate['rate'], 2, ',', '.') . " BRL (simulado)\n\n";
    }

    // Teste 2: Converter valor específico
    echo "4. Convertendo R$ 100,00 para USDT...\n";
    try {
        $conversion = $client->convertAmount(100.0, 'BRL', 'USDT');
        echo "   ✅ Conversão realizada com sucesso!\n";
        echo "   💰 R$ " . number_format($conversion['original_amount'], 2, ',', '.') . " = " . 
             number_format($conversion['converted_amount'], 6, ',', '.') . " USDT\n";
        echo "   📈 Taxa utilizada: " . number_format($conversion['rate'], 2, ',', '.') . " BRL/USDT\n\n";
    } catch (ApiException $e) {
        echo "   ❌ Erro na API: " . $e->getMessage() . "\n";
        echo "   ℹ️  Calculando conversão com dados simulados...\n";
        $convertedAmount = 100.0 / $rate['rate'];
        echo "   💰 R$ 100,00 = " . number_format($convertedAmount, 6, ',', '.') . " USDT (simulado)\n\n";
    }

    // Teste 3: Obter dados detalhados de criptomoeda
    echo "5. Obtendo dados detalhados do USDT...\n";
    try {
        $cryptoData = $client->getCryptoRate('USDT', 'BRL');
        echo "   ✅ Dados obtidos com sucesso!\n";
        echo "   📊 Taxa: " . number_format($cryptoData['rate'], 2, ',', '.') . " BRL\n";
        echo "   📈 Market Cap: " . ($cryptoData['market_cap'] ?? 'N/A') . "\n";
        echo "   📊 Volume 24h: " . ($cryptoData['volume_24h'] ?? 'N/A') . "\n";
        echo "   🔄 Variação 24h: " . ($cryptoData['change_24h'] ?? 'N/A') . "%\n\n";
    } catch (ApiException $e) {
        echo "   ❌ Erro na API: " . $e->getMessage() . "\n";
        echo "   ℹ️  Usando dados simulados...\n";
        echo "   📊 Taxa: " . number_format($rate['rate'], 2, ',', '.') . " BRL (simulado)\n";
        echo "   📈 Market Cap: R$ 650.000.000.000 (simulado)\n";
        echo "   📊 Volume 24h: R$ 245.000.000.000 (simulado)\n";
        echo "   🔄 Variação 24h: +0.12% (simulado)\n\n";
    }

    // Teste 4: Múltiplas conversões
    echo "6. Testando múltiplas conversões...\n";
    $amounts = [10.0, 50.0, 100.0, 500.0, 1000.0];
    
    foreach ($amounts as $amount) {
        try {
            $conversion = $client->convertAmount($amount, 'BRL', 'USDT');
            $convertedAmount = $conversion['converted_amount'];
        } catch (ApiException $e) {
            // Usar dados simulados em caso de erro
            $convertedAmount = $amount / $rate['rate'];
        }
        
        echo "   💰 R$ " . number_format($amount, 2, ',', '.') . " = " . 
             number_format($convertedAmount, 6, ',', '.') . " USDT\n";
    }
    echo "\n";

    // Teste 5: Usar o ExchangeRateResource diretamente
    echo "7. Usando ExchangeRateResource diretamente...\n";
    try {
        $exchangeResource = $client->getExchangeRateResource();
        
        // Teste de múltiplas moedas
        echo "   📊 Testando múltiplas moedas...\n";
        $currencies = [
            ['USD', 'USDT'],
            ['EUR', 'USDT'],
            ['BRL', 'BTC']
        ];
        
        foreach ($currencies as [$from, $to]) {
            try {
                $rate = $exchangeResource->getExchangeRate($from, $to);
                echo "   💱 1 $to = " . number_format($rate['rate'], 6, ',', '.') . " $from\n";
            } catch (ApiException $e) {
                echo "   ❌ Erro ao obter taxa $from → $to: " . $e->getMessage() . "\n";
            }
        }
        
    } catch (ApiException $e) {
        echo "   ❌ Erro ao usar ExchangeRateResource: " . $e->getMessage() . "\n";
    }
    
    echo "\n";

    // Teste 6: Simulação de caso de uso real
    echo "8. Simulação de caso de uso real - Checkout USDT...\n";
    echo "   🛒 Produto: Plano Premium\n";
    echo "   💵 Preço: R$ 29,90\n";
    
    try {
        $checkoutConversion = $client->convertAmount(29.90, 'BRL', 'USDT');
        echo "   ✅ Conversão para checkout realizada!\n";
        echo "   💰 Valor a pagar: " . number_format($checkoutConversion['converted_amount'], 6, ',', '.') . " USDT\n";
        echo "   📈 Taxa aplicada: " . number_format($checkoutConversion['rate'], 2, ',', '.') . " BRL/USDT\n";
        echo "   🕒 Válido até: " . date('d/m/Y H:i:s', strtotime('+15 minutes')) . "\n";
    } catch (ApiException $e) {
        echo "   ❌ Erro na conversão para checkout: " . $e->getMessage() . "\n";
        $checkoutAmount = 29.90 / $rate['rate'];
        echo "   💰 Valor a pagar: " . number_format($checkoutAmount, 6, ',', '.') . " USDT (simulado)\n";
    }

    echo "\n✅ Exemplo concluído com sucesso!\n";

} catch (AuthenticationException $e) {
    echo "❌ Erro de autenticação: " . $e->getMessage() . "\n";
    echo "   Verifique suas credenciais no arquivo .env\n";
} catch (NetworkException $e) {
    echo "❌ Erro de rede: " . $e->getMessage() . "\n";
    echo "   Verifique sua conexão com a internet e a URL da API\n";
} catch (ApiException $e) {
    echo "❌ Erro da API: " . $e->getMessage() . "\n";
    echo "   Código: " . $e->getCode() . "\n";
} catch (\Exception $e) {
    echo "❌ Erro inesperado: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do exemplo ===\n"; 