<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\Configuration\ConfigurationManager;

/**
 * Exemplo de uso do ConfigurationManager da XGATE
 * 
 * Este exemplo demonstra as diferentes formas de configurar o SDK:
 * 1. Usando variáveis de ambiente
 * 2. Usando arquivo .env personalizado
 * 3. Usando array de configurações
 */

echo "=== Exemplo do ConfigurationManager ===\n\n";

try {
    // Exemplo 1: Configuração via array (para desenvolvimento/testes)
    echo "1. Configuração via array:\n";
    
    $config = ConfigurationManager::fromArray([
        'api_key' => 'test_api_key_12345678901234567890123456789012',
        'environment' => 'development',
        'debug_mode' => true,
        'timeout' => 60,
        'max_retries' => 5,
    ]);
    
    echo "   ✅ API Key: " . $config->toArray(false)['api_key'] . "\n";
    echo "   ✅ Ambiente: " . $config->getEnvironment() . "\n";
    echo "   ✅ Debug: " . ($config->isDebugMode() ? 'Sim' : 'Não') . "\n";
    echo "   ✅ Timeout: " . $config->getTimeout() . "s\n";
    echo "   ✅ É produção? " . ($config->isProduction() ? 'Sim' : 'Não') . "\n\n";

    // Exemplo 2: Configuração via variáveis de ambiente
    echo "2. Configuração via variáveis de ambiente:\n";
    
    // Simula variáveis de ambiente
    $_ENV['XGATE_API_KEY'] = 'env_api_key_12345678901234567890123456789012';
    $_ENV['XGATE_ENVIRONMENT'] = 'production';
    $_ENV['XGATE_DEBUG'] = 'false';
    $_ENV['XGATE_TIMEOUT'] = '45';
    
    $configEnv = new ConfigurationManager();
    $configEnv->validate();
    
    echo "   ✅ API Key: " . $configEnv->toArray(false)['api_key'] . "\n";
    echo "   ✅ Ambiente: " . $configEnv->getEnvironment() . "\n";
    echo "   ✅ Debug: " . ($configEnv->isDebugMode() ? 'Sim' : 'Não') . "\n";
    echo "   ✅ Timeout: " . $configEnv->getTimeout() . "s\n";
    echo "   ✅ É produção? " . ($configEnv->isProduction() ? 'Sim' : 'Não') . "\n\n";

    // Exemplo 3: Configuração com headers e proxy personalizados
    echo "3. Configuração avançada:\n";
    
    $_ENV['XGATE_CUSTOM_HEADERS'] = '{"User-Agent":"XGATE-SDK/1.0","X-Client-Version":"1.0.0"}';
    $_ENV['XGATE_PROXY_SETTINGS'] = '{"host":"proxy.example.com","port":8080}';
    
    $configAdvanced = new ConfigurationManager();
    $configAdvanced->validate();
    
    echo "   ✅ Headers personalizados: " . json_encode($configAdvanced->getCustomHeaders()) . "\n";
    echo "   ✅ Configurações de proxy: " . json_encode($configAdvanced->getProxySettings()) . "\n\n";

    // Exemplo 4: Exportar configurações (sem dados sensíveis)
    echo "4. Exportar configurações (mascaradas):\n";
    
    $configArray = $config->toArray(false);
    foreach ($configArray as $key => $value) {
        $displayValue = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'true' : 'false') : $value);
        echo "   {$key}: {$displayValue}\n";
    }
    
    echo "\n✅ Todos os exemplos executados com sucesso!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== Exemplo de .env ===\n";
echo "Para usar com arquivo .env, crie um arquivo .env na raiz do projeto com:\n\n";
echo "XGATE_API_KEY=your_api_key_here_minimum_32_characters\n";
echo "XGATE_ENVIRONMENT=production\n";
echo "XGATE_DEBUG=false\n";
echo "XGATE_TIMEOUT=30\n";
echo "XGATE_MAX_RETRIES=3\n";
echo "# XGATE_LOG_FILE=/var/log/xgate.log\n";
echo "# XGATE_CUSTOM_HEADERS={\"User-Agent\":\"MyApp/1.0\"}\n";
echo "# XGATE_PROXY_SETTINGS={\"host\":\"proxy.example.com\",\"port\":8080}\n";

echo "\n=== Fim do Exemplo ===\n"; 