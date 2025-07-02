<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Exception\{
    XGateException,
    AuthenticationException,
    ValidationException,
    RateLimitException,
    NetworkException,
    ApiException
};

/**
 * Exemplo completo de tratamento de erros do SDK da XGATE
 * 
 * Este exemplo demonstra como capturar e tratar diferentes tipos
 * de exceções que podem ocorrer durante o uso do SDK.
 */

echo "=== Exemplo de Tratamento de Erros ===\n\n";

try {
    // Inicializar cliente
    $client = new XGateClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.xgate.com',
        'environment' => 'production',
        'timeout' => 30,
        'retry_attempts' => 3,
    ]);

    echo "✅ Cliente inicializado com sucesso!\n\n";

    // 1. Exemplo de erro de autenticação
    echo "1. 🔐 Testando erro de autenticação:\n";
    try {
        $client->authenticate('usuario-invalido@exemplo.com', 'senha-errada');
        echo "   ✅ Autenticação bem-sucedida (inesperado!)\n";
    } catch (AuthenticationException $e) {
        echo "   ❌ Erro de autenticação capturado:\n";
        echo "      Mensagem: " . $e->getMessage() . "\n";
        echo "      Código HTTP: " . $e->getCode() . "\n";
        
        // Diferentes tipos de erro de autenticação
        switch ($e->getCode()) {
            case 401:
                echo "      Tipo: Credenciais inválidas\n";
                echo "      Ação sugerida: Verificar email e senha\n";
                break;
            case 403:
                echo "      Tipo: Acesso negado\n";
                echo "      Ação sugerida: Verificar permissões da conta\n";
                break;
            case 429:
                echo "      Tipo: Muitas tentativas de login\n";
                echo "      Ação sugerida: Aguardar antes de tentar novamente\n";
                break;
            default:
                echo "      Tipo: Erro de autenticação genérico\n";
        }
    }
    echo "\n";

    // 2. Exemplo de erro de validação
    echo "2. ✅ Simulando erro de validação:\n";
    try {
        // Simular dados inválidos que causariam ValidationException
        echo "   Tentando criar cliente com dados inválidos...\n";
        
        // Esta seria uma requisição real que falharia na validação
        echo "   \$client->post('/customers', [\n";
        echo "       'name' => '', // Nome vazio - inválido\n";
        echo "       'email' => 'email-invalido', // Email inválido\n";
        echo "       'document' => '123' // CPF muito curto\n";
        echo "   ]);\n";
        
        // Para demonstração, vamos criar uma ValidationException manualmente
        $validationErrors = [
            'name' => ['O campo nome é obrigatório', 'O nome deve ter pelo menos 2 caracteres'],
            'email' => ['O email deve ter um formato válido'],
            'document' => ['O CPF deve ter 11 dígitos', 'O CPF informado é inválido']
        ];
        
        throw new ValidationException(
            'Dados de entrada inválidos',
            422,
            null,
            $validationErrors
        );
        
    } catch (ValidationException $e) {
        echo "   ❌ Erro de validação capturado:\n";
        echo "      Mensagem: " . $e->getMessage() . "\n";
        echo "      Código HTTP: " . $e->getStatusCode() . "\n";
        echo "      Erros de validação:\n";
        
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "        - {$field}:\n";
            foreach ($errors as $error) {
                echo "          • {$error}\n";
            }
        }
        
        // Verificar se há dados sensíveis que foram mascarados
        if ($e->hasSensitiveData()) {
            echo "      ⚠️  Dados sensíveis foram automaticamente mascarados por segurança\n";
        }
    }
    echo "\n";

    // 3. Exemplo de rate limiting
    echo "3. ⏱️  Simulando rate limit:\n";
    try {
        // Simular rate limit excedido
        echo "   Simulando muitas requisições em pouco tempo...\n";
        
        // Para demonstração, criar RateLimitException manualmente
        $headers = [
            'X-RateLimit-Limit' => '100',
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => (string)(time() + 300), // Reset em 5 minutos
            'Retry-After' => '300'
        ];
        
        throw RateLimitException::fromHeaders(
            'Rate limit excedido. Muitas requisições em pouco tempo.',
            429,
            $headers
        );
        
    } catch (RateLimitException $e) {
        echo "   ❌ Rate limit excedido:\n";
        echo "      Mensagem: " . $e->getMessage() . "\n";
        echo "      Limite por janela: " . $e->getLimit() . " requisições\n";
        echo "      Requisições restantes: " . $e->getRemaining() . "\n";
        echo "      Reset em: " . date('H:i:s', $e->getResetTime()) . "\n";
        echo "      Retry após: " . $e->getRetryAfter() . " segundos\n";
        echo "      Uso atual: " . round($e->getUsagePercentage(), 1) . "%\n";
        
        if ($e->hasRetryAfter()) {
            echo "      ⏰ Aguardando automaticamente...\n";
            echo "      (Em produção, o SDK pode fazer retry automático)\n";
            
            // Simular espera (em produção seria sleep($e->getRetryAfter()))
            echo "      sleep(" . $e->getRetryAfter() . ") // Aguardar antes de tentar novamente\n";
        }
    }
    echo "\n";

    // 4. Exemplo de erro de rede
    echo "4. 🌐 Simulando erro de rede:\n";
    try {
        echo "   Tentando conectar a endpoint inacessível...\n";
        
        // Para demonstração, criar NetworkException manualmente
        throw new NetworkException(
            'Falha na conexão com a API da XGATE',
            0,
            null,
            'connection_timeout',
            'Verificar conectividade de rede e status da API',
            true, // É um erro que permite retry
            5 // Delay recomendado para retry (segundos)
        );
        
    } catch (NetworkException $e) {
        echo "   ❌ Erro de rede capturado:\n";
        echo "      Mensagem: " . $e->getMessage() . "\n";
        echo "      Tipo de erro: " . $e->getNetworkErrorType() . "\n";
        echo "      Sugestão: " . $e->getSuggestion() . "\n";
        echo "      Permite retry: " . ($e->isRetryable() ? 'Sim' : 'Não') . "\n";
        
        if ($e->isRetryable()) {
            $delay = $e->getRecommendedRetryDelay();
            echo "      Delay recomendado: {$delay} segundos\n";
            echo "      Implementando retry automático...\n";
            
            // Simular retry logic
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                echo "        Tentativa {$attempt}/3...\n";
                // Em produção: sleep($delay) e tentar requisição novamente
                echo "        (simulando retry após {$delay}s)\n";
                
                if ($attempt < 3) {
                    $delay *= 2; // Exponential backoff
                }
            }
        }
    }
    echo "\n";

    // 5. Exemplo de erro genérico da API
    echo "5. 🔧 Simulando erro genérico da API:\n";
    try {
        echo "   Tentando acessar endpoint que retorna erro 500...\n";
        
        // Para demonstração, criar ApiException manualmente
        $errorResponse = [
            'error' => [
                'code' => 'INTERNAL_SERVER_ERROR',
                'message' => 'Erro interno do servidor',
                'details' => 'Falha temporária no processamento',
                'timestamp' => date('c'),
                'request_id' => 'req_' . uniqid()
            ]
        ];
        
        throw new ApiException(
            'Erro interno do servidor da XGATE',
            500,
            null,
            'INTERNAL_SERVER_ERROR',
            $errorResponse
        );
        
    } catch (ApiException $e) {
        echo "   ❌ Erro da API capturado:\n";
        echo "      Mensagem: " . $e->getMessage() . "\n";
        echo "      Status HTTP: " . $e->getStatusCode() . "\n";
        echo "      Código do erro: " . $e->getApiErrorCode() . "\n";
        
        $details = $e->getErrorDetails();
        if (!empty($details)) {
            echo "      Detalhes do erro:\n";
            echo "        Request ID: " . ($details['error']['request_id'] ?? 'N/A') . "\n";
            echo "        Timestamp: " . ($details['error']['timestamp'] ?? 'N/A') . "\n";
            echo "        Detalhes: " . ($details['error']['details'] ?? 'N/A') . "\n";
        }
        
        // Verificar tipos específicos de erro
        if ($e->isValidationError()) {
            echo "      🔍 Detectado como erro de validação\n";
        } elseif ($e->isRateLimitError()) {
            echo "      ⏱️  Detectado como rate limit\n";
        } elseif ($e->isAuthenticationError()) {
            echo "      🔐 Detectado como erro de autenticação\n";
        } else {
            echo "      🔧 Erro genérico da API\n";
        }
    }
    echo "\n";

    // 6. Tratamento hierárquico de exceções
    echo "6. 🎯 Exemplo de tratamento hierárquico:\n";
    echo "   Demonstrando como capturar diferentes tipos de erro em ordem:\n\n";
    
    echo "   try {\n";
    echo "       \$result = \$client->post('/customers', \$data);\n";
    echo "   } catch (ValidationException \$e) {\n";
    echo "       // Tratar erros de validação específicos\n";
    echo "       handleValidationErrors(\$e->getValidationErrors());\n";
    echo "   } catch (RateLimitException \$e) {\n";
    echo "       // Implementar retry com delay\n";
    echo "       sleep(\$e->getRetryAfter());\n";
    echo "       // Tentar novamente...\n";
    echo "   } catch (AuthenticationException \$e) {\n";
    echo "       // Tentar reautenticar\n";
    echo "       \$client->authenticate(\$email, \$password);\n";
    echo "   } catch (NetworkException \$e) {\n";
    echo "       // Verificar conectividade\n";
    echo "       if (\$e->isRetryable()) {\n";
    echo "           // Retry com backoff exponencial\n";
    echo "       }\n";
    echo "   } catch (ApiException \$e) {\n";
    echo "       // Tratar outros erros da API\n";
    echo "       logApiError(\$e);\n";
    echo "   } catch (XGateException \$e) {\n";
    echo "       // Fallback para erros genéricos do SDK\n";
    echo "       logGenericError(\$e);\n";
    echo "   }\n\n";

    // 7. Logging estruturado de erros
    echo "7. 📊 Logging estruturado de erros:\n";
    
    function logStructuredError(XGateException $e): void {
        $logData = [
            'timestamp' => date('c'),
            'error_type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        
        // Adicionar contexto específico baseado no tipo de exceção
        if ($e instanceof ApiException) {
            $logData['http_status'] = $e->getStatusCode();
            $logData['api_error_code'] = $e->getApiErrorCode();
            $logData['error_details'] = $e->getErrorDetails();
        }
        
        if ($e instanceof ValidationException) {
            $logData['validation_errors'] = $e->getValidationErrors();
            $logData['has_sensitive_data'] = $e->hasSensitiveData();
        }
        
        if ($e instanceof RateLimitException) {
            $logData['rate_limit'] = [
                'limit' => $e->getLimit(),
                'remaining' => $e->getRemaining(),
                'reset_time' => $e->getResetTime(),
                'retry_after' => $e->getRetryAfter(),
            ];
        }
        
        if ($e instanceof NetworkException) {
            $logData['network_error_type'] = $e->getNetworkErrorType();
            $logData['is_retryable'] = $e->isRetryable();
            $logData['retry_delay'] = $e->getRecommendedRetryDelay();
        }
        
        echo "   Log estruturado:\n";
        echo "   " . json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    // Exemplo de log para uma ValidationException
    try {
        throw new ValidationException(
            'Exemplo de erro para log',
            422,
            null,
            ['email' => ['Email é obrigatório']]
        );
    } catch (ValidationException $e) {
        logStructuredError($e);
    }

    echo "\n✅ Exemplo de tratamento de erros concluído!\n";

} catch (XGateException $e) {
    echo "❌ Erro inesperado do SDK: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Contexto adicional se disponível
    $context = $e->getContext();
    if (!empty($context)) {
        echo "   Contexto: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erro crítico inesperado: " . $e->getMessage() . "\n";
    echo "   Tipo: " . get_class($e) . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Fim do Exemplo ===\n";

/**
 * Função auxiliar para demonstrar tratamento de erros de validação
 * 
 * @param array<string, array<string>> $errors Erros de validação por campo
 */
function handleValidationErrors(array $errors): void
{
    echo "Tratando erros de validação:\n";
    foreach ($errors as $field => $fieldErrors) {
        echo "- Campo '{$field}':\n";
        foreach ($fieldErrors as $error) {
            echo "  • {$error}\n";
        }
    }
}

/**
 * Função auxiliar para demonstrar log de erros da API
 * 
 * @param ApiException $e Exceção da API
 */
function logApiError(ApiException $e): void
{
    echo "Registrando erro da API:\n";
    echo "- Status: " . $e->getStatusCode() . "\n";
    echo "- Código: " . $e->getApiErrorCode() . "\n";
    echo "- Mensagem: " . $e->getMessage() . "\n";
}

/**
 * Função auxiliar para demonstrar log de erros genéricos
 * 
 * @param XGateException $e Exceção genérica do SDK
 */
function logGenericError(XGateException $e): void
{
    echo "Registrando erro genérico:\n";
    echo "- Tipo: " . get_class($e) . "\n";
    echo "- Mensagem: " . $e->getMessage() . "\n";
    echo "- Código: " . $e->getCode() . "\n";
} 