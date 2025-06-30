<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\Authentication\AuthenticationManager;
use XGate\Http\HttpClient;
use XGate\Configuration\ConfigurationManager;
use XGate\Exception\AuthenticationException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

// Exemplo de uso do AuthenticationManager

echo "=== Exemplo de Autenticação XGATE ===\n\n";

try {
    // 1. Configuração
    $config = new ConfigurationManager([
        'api_key' => 'your-api-key-here',
        'base_url' => 'https://api.xgate.com',
        'timeout' => 30,
    ]);

    // 2. Criar HTTP client
    $httpClient = new HttpClient($config);

    // 3. Criar cache (usando ArrayAdapter para exemplo)
    $cacheAdapter = new ArrayAdapter();
    $cache = new Psr16Cache($cacheAdapter);

    // 4. Criar AuthenticationManager
    $authManager = new AuthenticationManager($httpClient, $cache);

    echo "✓ AuthenticationManager criado com sucesso\n\n";

    // 5. Verificar se já está autenticado
    if ($authManager->isAuthenticated()) {
        echo "✓ Usuário já está autenticado\n";
        echo "Token: " . substr($authManager->getToken() ?? '', 0, 20) . "...\n\n";
    } else {
        echo "ℹ Usuário não está autenticado\n\n";
    }

    // 6. Exemplo de login (use suas credenciais reais)
    $email = 'seu-email@exemplo.com';
    $password = 'sua-senha';

    echo "Tentando fazer login com: {$email}\n";
    
    // Nota: Este exemplo falhará sem credenciais reais
    try {
        if ($authManager->login($email, $password)) {
            echo "✓ Login realizado com sucesso!\n";
            echo "✓ Token armazenado no cache\n\n";

            // 7. Verificar autenticação após login
            if ($authManager->isAuthenticated()) {
                echo "✓ Usuário está autenticado\n";
                
                // 8. Obter header de autorização
                $authHeader = $authManager->getAuthorizationHeader();
                echo "Header de autorização:\n";
                foreach ($authHeader as $key => $value) {
                    echo "  {$key}: " . substr($value, 0, 30) . "...\n";
                }
                echo "\n";

                // 9. Exemplo de logout
                echo "Fazendo logout...\n";
                if ($authManager->logout()) {
                    echo "✓ Logout realizado com sucesso\n";
                    echo "✓ Token removido do cache\n";
                } else {
                    echo "✗ Falha ao fazer logout\n";
                }

                // 10. Verificar se ainda está autenticado
                if (!$authManager->isAuthenticated()) {
                    echo "✓ Usuário não está mais autenticado\n";
                }
            }
        }
    } catch (AuthenticationException $e) {
        echo "✗ Erro de autenticação: " . $e->getMessage() . "\n";
        echo "ℹ Isso é esperado sem credenciais válidas\n\n";
    }

    // 11. Demonstrar erro ao tentar obter header sem autenticação
    try {
        $authManager->getAuthorizationHeader();
    } catch (AuthenticationException $e) {
        echo "✓ Exceção capturada corretamente: " . $e->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do Exemplo ===\n";

// Exemplo de configuração .env para autenticação
echo "\n=== Exemplo de arquivo .env ===\n";
echo "XGATE_API_KEY=sua-chave-api-aqui\n";
echo "XGATE_BASE_URL=https://api.xgate.com\n";
echo "XGATE_TIMEOUT=30\n";
echo "XGATE_DEBUG=false\n"; 