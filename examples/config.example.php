<?php

declare(strict_types=1);

/**
 * Arquivo de Configuração de Exemplo para o SDK XGATE
 * 
 * Este arquivo contém todas as configurações disponíveis para o SDK da XGATE.
 * Copie este arquivo para config.php e ajuste os valores conforme necessário.
 * 
 * IMPORTANTE: Nunca commite credenciais reais no controle de versão!
 * Use variáveis de ambiente (.env) para dados sensíveis.
 */

return [
    // === CONFIGURAÇÕES BÁSICAS ===
    
    /**
     * Chave da API (se necessário)
     * Pode ser obtida no painel administrativo da XGATE
     */
    'api_key' => $_ENV['XGATE_API_KEY'] ?? 'your-api-key-here',
    
    /**
     * URL base da API XGATE
     * Produção: https://api.xgate.com
     * Desenvolvimento: https://api-dev.xgate.com
     */
    'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgate.com',
    
    /**
     * Ambiente de execução
     * Valores: 'production', 'development', 'testing'
     */
    'environment' => $_ENV['XGATE_ENVIRONMENT'] ?? 'production',
    
    // === CONFIGURAÇÕES DE AUTENTICAÇÃO ===
    
    /**
     * Credenciais de autenticação
     * Configure no arquivo .env para segurança
     */
    'auth' => [
        'email' => $_ENV['XGATE_EMAIL'] ?? 'seu-email@exemplo.com',
        'password' => $_ENV['XGATE_PASSWORD'] ?? 'sua-senha-segura',
    ],
    
    // === CONFIGURAÇÕES DE REDE ===
    
    /**
     * Timeout para requisições HTTP (em segundos)
     */
    'timeout' => (int) ($_ENV['XGATE_TIMEOUT'] ?? 30),
    
    /**
     * Número máximo de tentativas em caso de falha
     */
    'retries' => (int) ($_ENV['XGATE_RETRIES'] ?? 3),
    
    /**
     * Configurações de proxy (se necessário)
     */
    'proxy' => [
        'http' => $_ENV['XGATE_PROXY_HTTP'] ?? null,
        'https' => $_ENV['XGATE_PROXY_HTTPS'] ?? null,
    ],
    
    // === CONFIGURAÇÕES DE SEGURANÇA ===
    
    /**
     * Verificação SSL
     * Recomendado: true para produção
     */
    'verify_ssl' => filter_var($_ENV['XGATE_VERIFY_SSL'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    /**
     * Cabeçalhos personalizados
     */
    'custom_headers' => [
        'User-Agent' => 'XGATE-PHP-SDK/' . ($_ENV['XGATE_SDK_VERSION'] ?? '1.0.0'),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    
    // === CONFIGURAÇÕES DE LOG ===
    
    /**
     * Habilitar modo debug
     * Mostra informações detalhadas de requisições
     */
    'debug' => filter_var($_ENV['XGATE_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    
    /**
     * Arquivo de log (opcional)
     * Se null, usa o logger padrão
     */
    'log_file' => $_ENV['XGATE_LOG_FILE'] ?? null,
    
    /**
     * Nível de log
     * Valores: 'debug', 'info', 'warning', 'error'
     */
    'log_level' => $_ENV['XGATE_LOG_LEVEL'] ?? 'info',
    
    // === CONFIGURAÇÕES DE CACHE ===
    
    /**
     * Habilitar cache
     */
    'cache_enabled' => filter_var($_ENV['XGATE_CACHE_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    /**
     * TTL do cache (em segundos)
     */
    'cache_ttl' => (int) ($_ENV['XGATE_CACHE_TTL'] ?? 3600),
    
    // === CONFIGURAÇÕES DE WEBHOOK ===
    
    /**
     * URL para callbacks de webhook
     */
    'webhook_url' => $_ENV['XGATE_WEBHOOK_URL'] ?? 'https://seu-site.com/webhook/xgate',
    
    /**
     * Secret para validação de webhook
     */
    'webhook_secret' => $_ENV['XGATE_WEBHOOK_SECRET'] ?? 'seu-secret-do-webhook',
    
    // === CONFIGURAÇÕES DE MOEDAS ===
    
    /**
     * Moeda padrão para transações
     */
    'default_currency' => $_ENV['XGATE_DEFAULT_CURRENCY'] ?? 'BRL',
    
    /**
     * Moedas suportadas
     */
    'supported_currencies' => [
        'BRL', 'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF'
    ],
    
    // === CONFIGURAÇÕES DE RATE LIMITING ===
    
    /**
     * Limite de requisições por minuto
     */
    'rate_limit' => (int) ($_ENV['XGATE_RATE_LIMIT'] ?? 60),
    
    /**
     * Delay entre requisições (em microsegundos)
     */
    'request_delay' => (int) ($_ENV['XGATE_REQUEST_DELAY'] ?? 100000), // 100ms
    
    // === CONFIGURAÇÕES DE DESENVOLVIMENTO ===
    
    /**
     * Modo de teste
     * Quando true, usa dados fictícios
     */
    'test_mode' => filter_var($_ENV['XGATE_TEST_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    
    /**
     * Dados de teste para cliente
     */
    'test_customer' => [
        'name' => 'João Silva Teste',
        'email' => 'joao.teste@exemplo.com',
        'phone' => '+5511999999999',
        'document' => '12345678901',
        'document_type' => 'cpf',
    ],
    
    /**
     * Dados de teste para transação
     */
    'test_transaction' => [
        'amount' => '100.00',
        'currency' => 'BRL',
        'payment_method' => 'crypto_transfer',
        'description' => 'Transação de teste',
    ],
    
    // === CONFIGURAÇÕES AVANÇADAS ===
    
    /**
     * Pool de conexões
     */
    'connection_pool' => [
        'max_connections' => (int) ($_ENV['XGATE_MAX_CONNECTIONS'] ?? 10),
        'connection_timeout' => (int) ($_ENV['XGATE_CONNECTION_TIMEOUT'] ?? 5),
    ],
    
    /**
     * Configurações de retry
     */
    'retry_config' => [
        'max_attempts' => (int) ($_ENV['XGATE_MAX_RETRY_ATTEMPTS'] ?? 3),
        'delay_ms' => (int) ($_ENV['XGATE_RETRY_DELAY_MS'] ?? 1000),
        'backoff_multiplier' => (float) ($_ENV['XGATE_BACKOFF_MULTIPLIER'] ?? 2.0),
    ],
    
    /**
     * Configurações de monitoramento
     */
    'monitoring' => [
        'enable_metrics' => filter_var($_ENV['XGATE_ENABLE_METRICS'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'metrics_endpoint' => $_ENV['XGATE_METRICS_ENDPOINT'] ?? null,
        'health_check_interval' => (int) ($_ENV['XGATE_HEALTH_CHECK_INTERVAL'] ?? 300), // 5 minutos
    ],
    
    // === CONFIGURAÇÕES DE VALIDAÇÃO ===
    
    /**
     * Validação rigorosa de dados
     */
    'strict_validation' => filter_var($_ENV['XGATE_STRICT_VALIDATION'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    /**
     * Validação de CPF/CNPJ
     */
    'validate_documents' => filter_var($_ENV['XGATE_VALIDATE_DOCUMENTS'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    /**
     * Validação de email
     */
    'validate_email' => filter_var($_ENV['XGATE_VALIDATE_EMAIL'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    // === CONFIGURAÇÕES DE FORMATAÇÃO ===
    
    /**
     * Locale para formatação de números
     */
    'locale' => $_ENV['XGATE_LOCALE'] ?? 'pt_BR',
    
    /**
     * Timezone padrão
     */
    'timezone' => $_ENV['XGATE_TIMEZONE'] ?? 'America/Sao_Paulo',
    
    /**
     * Formato de data padrão
     */
    'date_format' => $_ENV['XGATE_DATE_FORMAT'] ?? 'Y-m-d H:i:s',
];

/**
 * Exemplo de uso da configuração:
 * 
 * ```php
 * // Carregar configuração
 * $config = require 'config.php';
 * 
 * // Criar cliente com configuração
 * $client = new XGateClient($config);
 * 
 * // Autenticar
 * $client->authenticate($config['auth']['email'], $config['auth']['password']);
 * ```
 */ 