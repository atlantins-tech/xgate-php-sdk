<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;

/**
 * Exemplo básico de uso do SDK da XGATE
 * 
 * Este exemplo demonstra como instanciar o cliente principal
 * e obter informações básicas do SDK.
 */

echo "=== Exemplo Básico do XGATE PHP SDK ===\n\n";

try {
    // Instancia o cliente principal
    $client = new XGateClient();
    
    // Obtém a versão do SDK
    $version = $client->getVersion();
    
    echo "✅ SDK instanciado com sucesso!\n";
    echo "📦 Versão do SDK: {$version}\n";
    echo "\n";
    echo "🚧 Este é um exemplo básico. Mais funcionalidades serão adicionadas nas próximas versões.\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao usar o SDK: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Fim do Exemplo ===\n"; 