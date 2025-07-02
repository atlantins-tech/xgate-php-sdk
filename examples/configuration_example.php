<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Exception\XGateException;

/**
 * Exemplo de configuração completa do SDK da XGATE
 * 
 * Este exemplo demonstra todas as opções de configuração disponíveis
 * para o cliente XGATE, incluindo configurações básicas, avançadas,
 * logging, cache, proxy e outras opções.
 */

echo "=== Exemplo de Configuração Completa do XGATE SDK ===\n\n";

// 1. CONFIGURAÇÃO BÁSICA
echo "1. 🔧 CONFIGURAÇÃO BÁSICA\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "📝 Configuração mínima necessária:\n";
echo "```php\n";
echo "\$client = new XGateClient([\n";
echo "    'api_key' => 'sua-api-key-aqui',\n";
echo "    'environment' => 'development' // ou 'production'\n";
echo "]);\n";
echo "```\n\n";

// Exemplo de configuração básica
try {
    $basicClient = new XGateClient([
        'api_key' => 'test-api-key-basic',
        'environment' => 'development'
    ]);
    
    echo "✅ Cliente básico criado com sucesso!\n";
    echo "   Versão: " . $basicClient->getVersion() . "\n";
    echo "   Base URL: " . $basicClient->getConfiguration()->getBaseUrl() . "\n";
    echo "   Ambiente: " . $basicClient->getConfiguration()->getEnvironment() . "\n";
} catch (XGateException $e) {
    echo "❌ Erro na configuração básica: " . $e->getMessage() . "\n";
}
echo "\n";

// 2. CONFIGURAÇÃO COMPLETA
echo "2. ⚙️  CONFIGURAÇÃO COMPLETA\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "📋 Todas as opções de configuração disponíveis:\n\n";

$completeConfig = [
    // === CONFIGURAÇÕES OBRIGATÓRIAS ===
    'api_key' => getenv('XGATE_API_KEY') ?: 'your-api-key-here',
    'environment' => 'development', // 'development' ou 'production'
    
    // === CONFIGURAÇÕES DE CONEXÃO ===
    'base_url' => 'https://api.xgate.com', // URL base da API
    'timeout' => 60, // Timeout em segundos (padrão: 30)
    'connect_timeout' => 10, // Timeout de conexão (padrão: 5)
    
    // === CONFIGURAÇÕES DE RETRY ===
    'retry_attempts' => 3, // Número de tentativas (padrão: 3)
    'retry_delay' => 2, // Delay inicial entre tentativas em segundos
    'retry_multiplier' => 2.0, // Multiplicador para backoff exponencial
    'retry_max_delay' => 30, // Delay máximo entre tentativas
    
    // === CONFIGURAÇÕES DE DEBUG E LOG ===
    'debug' => true, // Habilitar modo debug (padrão: false)
    'log_level' => 'info', // 'debug', 'info', 'warning', 'error'
    'log_file' => '/tmp/xgate-sdk.log', // Arquivo de log personalizado
    'log_format' => 'json', // 'json' ou 'text'
    
    // === CONFIGURAÇÕES DE CACHE ===
    'cache_enabled' => true, // Habilitar cache (padrão: true)
    'cache_ttl' => 300, // TTL padrão do cache em segundos (5 minutos)
    'cache_prefix' => 'xgate_', // Prefixo das chaves de cache
    
    // === HEADERS PERSONALIZADOS ===
    'custom_headers' => [
        'X-Client-Version' => '1.0.0',
        'X-Integration-Type' => 'php-sdk',
        'X-Application-Name' => 'Minha Aplicação',
        'User-Agent' => 'MeuApp/1.0 XGATE-PHP-SDK/1.0'
    ],
    
    // === CONFIGURAÇÕES DE PROXY ===
    'proxy' => [
        'http' => 'http://proxy.empresa.com:8080',
        'https' => 'http://proxy.empresa.com:8080',
        'no' => ['localhost', '127.0.0.1'] // Hosts que não usam proxy
    ],
    
    // === CONFIGURAÇÕES DE SSL ===
    'ssl_verify' => true, // Verificar certificados SSL (padrão: true)
    'ssl_cert_path' => '/path/to/cert.pem', // Caminho para certificado personalizado
    
    // === CONFIGURAÇÕES DE RATE LIMITING ===
    'rate_limit_enabled' => true, // Habilitar detecção de rate limit
    'rate_limit_auto_retry' => true, // Retry automático em rate limit
    'rate_limit_max_wait' => 300, // Tempo máximo de espera em rate limit (5 min)
    
    // === CONFIGURAÇÕES DE WEBHOOK ===
    'webhook_secret' => 'meu-webhook-secret-123', // Secret para validação de webhooks
    'webhook_tolerance' => 300, // Tolerância de timestamp em segundos
    
    // === CONFIGURAÇÕES AVANÇADAS ===
    'user_agent_suffix' => 'Custom/1.0', // Sufixo personalizado para User-Agent
    'max_redirects' => 5, // Número máximo de redirects (padrão: 5)
    'stream_context' => [], // Contexto de stream personalizado
    'curl_options' => [ // Opções cURL personalizadas
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => 'Custom User Agent'
    ]
];

echo "```php\n";
echo "\$client = new XGateClient([\n";
foreach ($completeConfig as $key => $value) {
    if (is_array($value)) {
        echo "    '{$key}' => [\n";
        foreach ($value as $subKey => $subValue) {
            if (is_string($subValue)) {
                echo "        '{$subKey}' => '{$subValue}',\n";
            } else {
                echo "        '{$subKey}' => {$subValue},\n";
            }
        }
        echo "    ],\n";
    } elseif (is_string($value)) {
        echo "    '{$key}' => '{$value}',\n";
    } elseif (is_bool($value)) {
        echo "    '{$key}' => " . ($value ? 'true' : 'false') . ",\n";
    } else {
        echo "    '{$key}' => {$value},\n";
    }
}
echo "]);\n";
echo "```\n\n";

// Criar cliente com configuração completa
try {
    $completeClient = new XGateClient($completeConfig);
    
    echo "✅ Cliente com configuração completa criado!\n";
    echo "   Timeout: " . $completeClient->getConfiguration()->getTimeout() . "s\n";
    echo "   Retry attempts: " . $completeClient->getConfiguration()->getRetryAttempts() . "\n";
    echo "   Debug: " . ($completeClient->getConfiguration()->isDebugEnabled() ? 'Habilitado' : 'Desabilitado') . "\n";
    echo "   Cache: " . ($completeClient->getConfiguration()->isCacheEnabled() ? 'Habilitado' : 'Desabilitado') . "\n";
} catch (XGateException $e) {
    echo "❌ Erro na configuração completa: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. CONFIGURAÇÕES POR AMBIENTE
echo "3. 🌍 CONFIGURAÇÕES POR AMBIENTE\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Configuração para desenvolvimento
echo "🔧 Configuração para DESENVOLVIMENTO:\n";
$devConfig = [
    'api_key' => 'dev-api-key',
    'environment' => 'development',
    'base_url' => 'https://api-dev.xgate.com',
    'debug' => true,
    'log_level' => 'debug',
    'timeout' => 30,
    'retry_attempts' => 5,
    'ssl_verify' => false, // Para ambientes de teste
];

echo "```php\n";
foreach ($devConfig as $key => $value) {
    if (is_bool($value)) {
        echo "\$config['{$key}'] = " . ($value ? 'true' : 'false') . ";\n";
    } else {
        echo "\$config['{$key}'] = '{$value}';\n";
    }
}
echo "```\n\n";

// Configuração para produção
echo "🚀 Configuração para PRODUÇÃO:\n";
$prodConfig = [
    'api_key' => 'prod-api-key',
    'environment' => 'production',
    'base_url' => 'https://api.xgate.com',
    'debug' => false,
    'log_level' => 'warning',
    'timeout' => 60,
    'retry_attempts' => 3,
    'ssl_verify' => true,
    'log_file' => '/var/log/xgate-sdk.log',
];

echo "```php\n";
foreach ($prodConfig as $key => $value) {
    if (is_bool($value)) {
        echo "\$config['{$key}'] = " . ($value ? 'true' : 'false') . ";\n";
    } else {
        echo "\$config['{$key}'] = '{$value}';\n";
    }
}
echo "```\n\n";

// 4. VARIÁVEIS DE AMBIENTE
echo "4. 🔐 CONFIGURAÇÃO VIA VARIÁVEIS DE AMBIENTE\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "📄 Arquivo .env recomendado:\n";
echo "```env\n";
echo "# XGATE SDK Configuration\n";
echo "XGATE_API_KEY=sua-api-key-aqui\n";
echo "XGATE_ENVIRONMENT=development\n";
echo "XGATE_BASE_URL=https://api.xgate.com\n";
echo "XGATE_TIMEOUT=60\n";
echo "XGATE_DEBUG=true\n";
echo "XGATE_LOG_LEVEL=info\n";
echo "XGATE_LOG_FILE=/var/log/xgate-sdk.log\n";
echo "XGATE_CACHE_ENABLED=true\n";
echo "XGATE_CACHE_TTL=300\n";
echo "XGATE_RETRY_ATTEMPTS=3\n";
echo "XGATE_WEBHOOK_SECRET=seu-webhook-secret\n";
echo "```\n\n";

echo "💻 Carregando configuração do ambiente:\n";
echo "```php\n";
echo "\$client = new XGateClient([\n";
echo "    'api_key' => getenv('XGATE_API_KEY'),\n";
echo "    'environment' => getenv('XGATE_ENVIRONMENT') ?: 'development',\n";
echo "    'base_url' => getenv('XGATE_BASE_URL'),\n";
echo "    'timeout' => (int)getenv('XGATE_TIMEOUT') ?: 30,\n";
echo "    'debug' => filter_var(getenv('XGATE_DEBUG'), FILTER_VALIDATE_BOOLEAN),\n";
echo "    'log_level' => getenv('XGATE_LOG_LEVEL') ?: 'info',\n";
echo "    'log_file' => getenv('XGATE_LOG_FILE'),\n";
echo "    'cache_enabled' => filter_var(getenv('XGATE_CACHE_ENABLED'), FILTER_VALIDATE_BOOLEAN),\n";
echo "    'cache_ttl' => (int)getenv('XGATE_CACHE_TTL') ?: 300,\n";
echo "    'retry_attempts' => (int)getenv('XGATE_RETRY_ATTEMPTS') ?: 3,\n";
echo "    'webhook_secret' => getenv('XGATE_WEBHOOK_SECRET'),\n";
echo "]);\n";
echo "```\n\n";

// Exemplo prático com variáveis de ambiente
$envClient = new XGateClient([
    'api_key' => getenv('XGATE_API_KEY') ?: 'fallback-api-key',
    'environment' => getenv('XGATE_ENVIRONMENT') ?: 'development',
    'debug' => filter_var(getenv('XGATE_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    'timeout' => (int)getenv('XGATE_TIMEOUT') ?: 30,
]);

echo "✅ Cliente configurado via variáveis de ambiente!\n";
echo "   API Key: " . (getenv('XGATE_API_KEY') ? 'Configurada' : 'Usando fallback') . "\n";
echo "   Environment: " . $envClient->getConfiguration()->getEnvironment() . "\n\n";

// 5. VALIDAÇÃO DE CONFIGURAÇÃO
echo "5. ✅ VALIDAÇÃO DE CONFIGURAÇÃO\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "🔍 Verificando configuração do cliente:\n";

function validateClientConfiguration(XGateClient $client): void
{
    $config = $client->getConfiguration();
    
    echo "   📋 Resumo da configuração:\n";
    echo "   - API Key: " . (empty($config->getApiKey()) ? '❌ Não configurada' : '✅ Configurada') . "\n";
    echo "   - Environment: " . $config->getEnvironment() . "\n";
    echo "   - Base URL: " . $config->getBaseUrl() . "\n";
    echo "   - Timeout: " . $config->getTimeout() . "s\n";
    echo "   - Debug: " . ($config->isDebugEnabled() ? '✅ Habilitado' : '❌ Desabilitado') . "\n";
    echo "   - Cache: " . ($config->isCacheEnabled() ? '✅ Habilitado' : '❌ Desabilitado') . "\n";
    echo "   - Retry: " . $config->getRetryAttempts() . " tentativas\n";
    echo "   - Log Level: " . $config->getLogLevel() . "\n";
    
    if ($config->getLogFile()) {
        echo "   - Log File: " . $config->getLogFile() . "\n";
    }
    
    // Verificar se está pronto para produção
    if ($config->getEnvironment() === 'production') {
        echo "\n   🚀 Verificações para produção:\n";
        echo "   - SSL Verify: " . ($config->isSslVerifyEnabled() ? '✅ Habilitado' : '⚠️  Desabilitado') . "\n";
        echo "   - Debug Mode: " . ($config->isDebugEnabled() ? '⚠️  Habilitado (não recomendado)' : '✅ Desabilitado') . "\n";
        echo "   - Log Level: " . ($config->getLogLevel() === 'debug' ? '⚠️  Debug (não recomendado)' : '✅ ' . $config->getLogLevel()) . "\n";
    }
}

validateClientConfiguration($envClient);

echo "\n";

// 6. DICAS DE CONFIGURAÇÃO
echo "6. 💡 DICAS DE CONFIGURAÇÃO\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "📚 Melhores práticas:\n\n";

echo "✅ **Para Desenvolvimento:**\n";
echo "   - Use debug=true para ver requisições detalhadas\n";
echo "   - Configure log_level='debug' para máximo detalhamento\n";
echo "   - Use timeouts maiores para debugging\n";
echo "   - Desabilite SSL verify se necessário para testes\n\n";

echo "✅ **Para Produção:**\n";
echo "   - Sempre use debug=false\n";
echo "   - Configure log_level='warning' ou 'error'\n";
echo "   - Use timeouts apropriados (30-60s)\n";
echo "   - Sempre mantenha SSL verify habilitado\n";
echo "   - Configure logs em arquivo persistente\n\n";

echo "✅ **Para Performance:**\n";
echo "   - Habilite cache para dados que mudam pouco\n";
echo "   - Configure retry_attempts adequadamente\n";
echo "   - Use connection pooling quando possível\n";
echo "   - Configure timeouts baseados na sua aplicação\n\n";

echo "✅ **Para Segurança:**\n";
echo "   - Nunca hardcode API keys no código\n";
echo "   - Use variáveis de ambiente para credenciais\n";
echo "   - Configure webhook secrets adequadamente\n";
echo "   - Use HTTPS sempre em produção\n\n";

echo "✅ **Para Monitoramento:**\n";
echo "   - Configure logs estruturados\n";
echo "   - Use diferentes níveis de log por ambiente\n";
echo "   - Monitore rate limits e timeouts\n";
echo "   - Implemente alertas para erros críticos\n\n";

echo "🎉 Exemplo de configuração concluído!\n";
echo "📖 Consulte a documentação completa para mais detalhes.\n";

echo "\n=== Fim do Exemplo de Configuração ===\n"; 