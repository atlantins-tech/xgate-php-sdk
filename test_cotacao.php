<?php

require_once __DIR__ . '/vendor/autoload.php';

use XGate\XGateClient;
use XGate\Configuration\ConfigurationManager;

try {
    echo "🚀 Testando cotação BRL/USDT no XGate SDK\n\n";
    
    // Configuração do cliente sem API Key (usa login email/senha)
    $config = [
        'base_url' => 'https://api.xgateglobal.com',
        'environment' => 'production',
        'debug_mode' => true,
        'timeout' => 30
    ];
    
    // Credenciais para login
    $email = 'metamecadmin02314@gmail.com';
    $password = 'leVDtcA9BJ11xI0Dt4F2Ew6Z4B';
    
    echo "📋 Configuração:\n";
    echo "- Email: " . $email . "\n";
    echo "- Base URL: " . $config['base_url'] . "\n";
    echo "- Debug Mode: " . ($config['debug_mode'] ? 'true' : 'false') . "\n\n";
    
    // Cria o cliente
    $client = new XGateClient($config);
    
    echo "🔐 Autenticando com email/senha...\n";
    $authenticated = $client->authenticate($email, $password);
    
    if (!$authenticated) {
        throw new Exception("Falha na autenticação");
    }
    
    echo "✅ Autenticação bem-sucedida!\n\n";
    
    echo "💱 Testando conversão de R$ 100,00 para USDT...\n";
    
    // Testa a conversão
    $conversion = $client->convertAmount(100.0, 'BRL', 'USDT');
    
    echo "✅ Conversão realizada com sucesso!\n";
    echo "📊 Resultado:\n";
    echo "- Valor original: R$ " . number_format($conversion['original_amount'], 2, ',', '.') . "\n";
    echo "- Taxa de câmbio: " . $conversion['rate'] . " BRL/USDT\n";
    echo "- Valor convertido: " . number_format($conversion['converted_amount'], 8) . " USDT\n";
    echo "- Timestamp: " . $conversion['timestamp'] . "\n\n";
    
    echo "🎉 Teste concluído com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📍 Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "🔗 Erro anterior: " . $e->getPrevious()->getMessage() . "\n";
    }
    
    // Adiciona stack trace em modo debug
    echo "\n🔍 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
} 