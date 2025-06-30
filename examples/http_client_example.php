<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use XGate\Configuration\ConfigurationManager;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;
use XGate\Http\HttpClient;

/**
 * Exemplo de uso do HttpClient da XGATE
 * 
 * Este exemplo demonstra como usar o cliente HTTP do SDK para fazer
 * requisições à API, incluindo tratamento de erros e logging.
 */

echo "=== Exemplo do HttpClient ===\n\n";

try {
    // 1. Configuração do SDK
    echo "1. Configurando o SDK...\n";
    
    $config = ConfigurationManager::fromArray([
        'api_key' => 'example_api_key_12345678901234567890123456789012',
        'base_url' => 'https://jsonplaceholder.typicode.com', // API de exemplo
        'timeout' => 30,
        'debug_mode' => true,
        'max_retries' => 3,
    ]);
    
    echo "   ✅ Configuração criada\n";
    echo "   🔗 Base URL: " . $config->getBaseUrl() . "\n";
    echo "   ⏱️  Timeout: " . $config->getTimeout() . "s\n\n";

    // 2. Configuração do Logger
    echo "2. Configurando logger...\n";
    
    $logger = new Logger('xgate-http-example');
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
    
    echo "   ✅ Logger configurado\n\n";

    // 3. Criação do HttpClient
    echo "3. Criando HttpClient...\n";
    
    $httpClient = new HttpClient($config, $logger);
    
    echo "   ✅ HttpClient criado\n\n";

    // 4. Exemplo de requisição GET
    echo "4. Fazendo requisição GET...\n";
    
    $response = $httpClient->get('/posts/1');
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    
    echo "   ✅ Status: {$statusCode}\n";
    echo "   📄 Resposta: " . substr($body, 0, 100) . "...\n\n";

    // 5. Exemplo de requisição POST
    echo "5. Fazendo requisição POST...\n";
    
    $postData = [
        'title' => 'Exemplo de Post via XGATE SDK',
        'body' => 'Este é um exemplo de como criar um post usando o SDK da XGATE.',
        'userId' => 1
    ];
    
    $response = $httpClient->post('/posts', [
        'json' => $postData,
        'headers' => [
            'Content-Type' => 'application/json; charset=UTF-8'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    
    echo "   ✅ Status: {$statusCode}\n";
    echo "   📄 Post criado: " . substr($body, 0, 100) . "...\n\n";

    // 6. Exemplo de requisição PUT
    echo "6. Fazendo requisição PUT...\n";
    
    $updateData = [
        'id' => 1,
        'title' => 'Título atualizado via XGATE SDK',
        'body' => 'Corpo atualizado usando o SDK.',
        'userId' => 1
    ];
    
    $response = $httpClient->put('/posts/1', [
        'json' => $updateData
    ]);
    
    echo "   ✅ Status: " . $response->getStatusCode() . "\n";
    echo "   📝 Post atualizado\n\n";

    // 7. Exemplo de requisição DELETE
    echo "7. Fazendo requisição DELETE...\n";
    
    $response = $httpClient->delete('/posts/1');
    
    echo "   ✅ Status: " . $response->getStatusCode() . "\n";
    echo "   🗑️  Post removido\n\n";

    // 8. Exemplo de requisição assíncrona
    echo "8. Fazendo requisição assíncrona...\n";
    
    $promise = $httpClient->requestAsync('GET', '/users/1');
    
    echo "   ⏳ Requisição iniciada...\n";
    
    // Simula processamento enquanto a requisição é executada
    usleep(100000); // 100ms
    
    $response = $promise->wait();
    
    echo "   ✅ Status: " . $response->getStatusCode() . "\n";
    echo "   👤 Usuário obtido\n\n";

    echo "✅ Todos os exemplos executados com sucesso!\n\n";

} catch (ApiException $e) {
    echo "❌ Erro da API:\n";
    echo "   Status: " . $e->getStatusCode() . "\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   Resposta: " . $e->getResponseBody() . "\n";
    
    if ($e->isAuthenticationError()) {
        echo "   💡 Dica: Verifique suas credenciais de API\n";
    } elseif ($e->isRateLimitError()) {
        echo "   💡 Dica: Você atingiu o limite de requisições. Aguarde um pouco.\n";
    } elseif ($e->isNotFoundError()) {
        echo "   💡 Dica: O recurso solicitado não foi encontrado\n";
    }
    
    echo "\n";
    
} catch (NetworkException $e) {
    echo "❌ Erro de rede:\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   💡 Sugestão: " . $e->getSuggestion() . "\n";
    
    if ($e->isRetryable()) {
        echo "   🔄 Este erro pode ser resolvido tentando novamente\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Erro inesperado:\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

echo "=== Exemplo concluído ===\n"; 