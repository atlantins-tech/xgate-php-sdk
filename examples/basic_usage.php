<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Exception\AuthenticationException;
use XGate\Exception\XGateException;

/**
 * Exemplo básico de uso do SDK da XGATE
 * 
 * Este exemplo demonstra como instanciar o cliente principal
 * e obter informações básicas do SDK.
 */

echo "=== Exemplo de Uso Básico do XGateClient ===\n\n";

try {
    // 1. Configuração básica do cliente
    echo "1. Inicializando cliente...\n";
    $client = new XGateClient([
        'api_key' => 'your-api-key-here',
        'base_url' => 'https://api.xgate.com',
        'environment' => 'development',
        'timeout' => 30,
        'debug' => true,
    ]);

    echo "✅ Cliente inicializado com sucesso!\n";
    echo "   Versão do SDK: " . $client->getVersion() . "\n";
    echo "   Base URL: " . $client->getConfiguration()->getBaseUrl() . "\n";
    echo "   Ambiente: " . $client->getConfiguration()->getEnvironment() . "\n\n";

    // 2. Tentativa de autenticação
    echo "2. Realizando autenticação...\n";
    try {
        $authenticated = $client->authenticate('usuario@exemplo.com', 'senha123');
        
        if ($authenticated) {
            echo "✅ Autenticação realizada com sucesso!\n";
        } else {
            echo "❌ Falha na autenticação\n";
        }
    } catch (AuthenticationException $e) {
        echo "❌ Erro de autenticação: " . $e->getMessage() . "\n";
        echo "   (Isso é esperado no exemplo - credenciais fictícias)\n";
    }

    echo "   Status de autenticação: " . ($client->isAuthenticated() ? 'Autenticado' : 'Não autenticado') . "\n\n";

    // 3. Exemplos de requisições HTTP (sem autenticação real)
    echo "3. Exemplos de requisições HTTP:\n\n";

    // GET - Buscar dados
    echo "   📥 Exemplo de requisição GET:\n";
    echo "   \$users = \$client->get('/users', [\n";
    echo "       'query' => ['limit' => 10, 'page' => 1]\n";
    echo "   ]);\n";
    echo "   // Retornaria: array com lista de usuários\n\n";

    // POST - Criar recurso
    echo "   📤 Exemplo de requisição POST:\n";
    echo "   \$newUser = \$client->post('/users', [\n";
    echo "       'name' => 'João Silva',\n";
    echo "       'email' => 'joao@exemplo.com',\n";
    echo "       'role' => 'user'\n";
    echo "   ]);\n";
    echo "   // Retornaria: array com dados do usuário criado\n\n";

    // PUT - Atualizar recurso
    echo "   🔄 Exemplo de requisição PUT:\n";
    echo "   \$updatedUser = \$client->put('/users/123', [\n";
    echo "       'name' => 'João Santos',\n";
    echo "       'role' => 'admin'\n";
    echo "   ]);\n";
    echo "   // Retornaria: array com dados do usuário atualizado\n\n";

    // PATCH - Atualização parcial
    echo "   🔧 Exemplo de requisição PATCH:\n";
    echo "   \$result = \$client->patch('/users/123', [\n";
    echo "       'status' => 'active'\n";
    echo "   ]);\n";
    echo "   // Retornaria: array com dados atualizados\n\n";

    // DELETE - Remover recurso
    echo "   🗑️  Exemplo de requisição DELETE:\n";
    echo "   \$result = \$client->delete('/users/123');\n";
    echo "   // Retornaria: array com confirmação da remoção\n\n";

    // 4. Tratamento de erros
    echo "4. Tratamento de erros:\n";
    echo "   try {\n";
    echo "       \$data = \$client->get('/endpoint-inexistente');\n";
    echo "   } catch (XGateException \$e) {\n";
    echo "       echo 'Erro na API: ' . \$e->getMessage();\n";
    echo "   }\n\n";

    // 5. Acesso a componentes internos
    echo "5. Acesso a componentes internos:\n";
    echo "   // Acessar configuração\n";
    echo "   \$config = \$client->getConfiguration();\n";
    echo "   echo 'Timeout: ' . \$config->getTimeout() . \"s\";\n";
    echo "   Timeout atual: " . $client->getConfiguration()->getTimeout() . "s\n\n";

    echo "   // Acessar logger\n";
    echo "   \$logger = \$client->getLogger();\n";
    echo "   \$logger->info('Operação personalizada realizada');\n\n";

    echo "   // Acessar cache\n";
    echo "   \$cache = \$client->getCache();\n";
    echo "   \$cache->set('custom_key', 'valor', 3600);\n\n";

    // 6. Workflow completo de autenticação
    echo "6. Workflow completo de autenticação:\n";
    echo "   // Verificar se está autenticado\n";
    echo "   if (!\$client->isAuthenticated()) {\n";
    echo "       // Fazer login\n";
    echo "       \$client->authenticate('user@example.com', 'password');\n";
    echo "   }\n\n";
    echo "   // Fazer requisições autenticadas\n";
    echo "   \$protectedData = \$client->get('/protected-endpoint');\n\n";
    echo "   // Fazer logout quando necessário\n";
    echo "   \$client->logout();\n\n";

    // 7. Configuração avançada
    echo "7. Configuração avançada:\n";
    echo "   \$client = new XGateClient([\n";
    echo "       'api_key' => 'your-api-key',\n";
    echo "       'base_url' => 'https://api.xgate.com',\n";
    echo "       'environment' => 'production',\n";
    echo "       'timeout' => 60,\n";
    echo "       'retries' => 5,\n";
    echo "       'debug' => false,\n";
    echo "       'log_file' => '/var/log/xgate-sdk.log',\n";
    echo "       'custom_headers' => [\n";
    echo "           'X-Custom-Header' => 'valor'\n";
    echo "       ],\n";
    echo "       'proxy' => [\n";
    echo "           'http' => 'http://proxy.empresa.com:8080'\n";
    echo "       ]\n";
    echo "   ]);\n\n";

    echo "✅ Exemplo concluído com sucesso!\n";

} catch (XGateException $e) {
    echo "❌ Erro do SDK: " . $e->getMessage() . "\n";
    echo "   Código: " . $e->getCode() . "\n";
    if ($e->getPrevious()) {
        echo "   Erro anterior: " . $e->getPrevious()->getMessage() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Erro inesperado: " . $e->getMessage() . "\n";
}

echo "\n=== Fim do Exemplo ===\n"; 