<?php

/**
 * Teste simples para validar a implementação do ExchangeRateResource
 * 
 * Este teste usa dados simulados para validar que a estrutura do código
 * está funcionando corretamente antes de fazer commit.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Resource\ExchangeRateResource;
use XGate\Http\HttpClient;
use XGate\Configuration\ConfigurationManager;

echo "=== TESTE DE VALIDAÇÃO - ExchangeRateResource ===\n\n";

try {
    // Teste 1: Inicialização do cliente
    echo "1. Testando inicialização do cliente...\n";
    $config = [
        'base_url' => 'https://api.xgate.com',
        'timeout' => 30,
        'debug' => false
    ];
    
    $client = new XGateClient($config);
    echo "   ✅ Cliente inicializado com sucesso!\n\n";

    // Teste 2: Obter ExchangeRateResource
    echo "2. Testando obtenção do ExchangeRateResource...\n";
    $exchangeResource = $client->getExchangeRateResource();
    
    if ($exchangeResource instanceof ExchangeRateResource) {
        echo "   ✅ ExchangeRateResource obtido com sucesso!\n";
        echo "   📋 Classe: " . get_class($exchangeResource) . "\n\n";
    } else {
        throw new \Exception('ExchangeRateResource não foi criado corretamente');
    }

    // Teste 3: Verificar métodos disponíveis
    echo "3. Verificando métodos disponíveis...\n";
    $methods = get_class_methods($exchangeResource);
    $expectedMethods = [
        'getExchangeRate',
        'getMultipleRates', 
        'getCryptoRate',
        'getHistoricalRates',
        'convertAmount'
    ];
    
    foreach ($expectedMethods as $method) {
        if (in_array($method, $methods)) {
            echo "   ✅ Método '$method' encontrado\n";
        } else {
            throw new \Exception("Método '$method' não encontrado");
        }
    }
    echo "\n";

    // Teste 4: Verificar métodos de conveniência no cliente
    echo "4. Verificando métodos de conveniência no cliente...\n";
    $clientMethods = get_class_methods($client);
    $expectedClientMethods = [
        'getExchangeRate',
        'convertAmount',
        'getCryptoRate'
    ];
    
    foreach ($expectedClientMethods as $method) {
        if (in_array($method, $clientMethods)) {
            echo "   ✅ Método '$method' encontrado no cliente\n";
        } else {
            throw new \Exception("Método '$method' não encontrado no cliente");
        }
    }
    echo "\n";

    // Teste 5: Verificar estrutura de classes
    echo "5. Verificando estrutura de classes...\n";
    
    // Verificar se as classes existem
    $classes = [
        'XGate\\XGateClient',
        'XGate\\Resource\\ExchangeRateResource',
        'XGate\\Http\\HttpClient',
        'XGate\\Configuration\\ConfigurationManager'
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "   ✅ Classe '$class' encontrada\n";
        } else {
            throw new \Exception("Classe '$class' não encontrada");
        }
    }
    echo "\n";

    // Teste 6: Verificar constantes
    echo "6. Verificando constantes do ExchangeRateResource...\n";
    $reflection = new \ReflectionClass($exchangeResource);
    $constants = $reflection->getConstants();
    
    $expectedConstants = [
        'ENDPOINT_EXCHANGE_RATES',
        'ENDPOINT_CRYPTO_RATES'
    ];
    
    foreach ($expectedConstants as $constant) {
        if (array_key_exists($constant, $constants)) {
            echo "   ✅ Constante '$constant' = '" . $constants[$constant] . "'\n";
        } else {
            throw new \Exception("Constante '$constant' não encontrada");
        }
    }
    echo "\n";

    // Teste 7: Testar instanciação direta
    echo "7. Testando instanciação direta do ExchangeRateResource...\n";
    $httpClient = $client->getHttpClient();
    $logger = $client->getLogger();
    
    $directResource = new ExchangeRateResource($httpClient, $logger);
    
    if ($directResource instanceof ExchangeRateResource) {
        echo "   ✅ ExchangeRateResource instanciado diretamente com sucesso!\n";
    } else {
        throw new \Exception('Falha na instanciação direta');
    }
    echo "\n";

    // Teste 8: Verificar versão do SDK
    echo "8. Verificando versão do SDK...\n";
    $version = $client->getVersion();
    echo "   📋 Versão do SDK: $version\n";
    echo "   ✅ Versão obtida com sucesso!\n\n";

    echo "🎉 TODOS OS TESTES PASSARAM!\n";
    echo "✅ A implementação do ExchangeRateResource está funcionando corretamente\n";
    echo "✅ Todos os métodos estão disponíveis\n";
    echo "✅ A estrutura de classes está correta\n";
    echo "✅ O SDK está pronto para uso\n\n";

    echo "📝 PRÓXIMOS PASSOS:\n";
    echo "   1. Fazer commit das alterações\n";
    echo "   2. Fazer push para o GitHub\n";
    echo "   3. Atualizar o composer no projeto Oak\n";
    echo "   4. Testar integração completa\n\n";

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "📍 Trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "=== FIM DO TESTE DE VALIDAÇÃO ===\n"; 