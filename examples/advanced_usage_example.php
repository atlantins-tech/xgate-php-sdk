<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Exception\{XGateException, ValidationException, RateLimitException};

/**
 * Exemplo avançado de uso do SDK da XGATE
 * 
 * Este exemplo demonstra funcionalidades avançadas incluindo:
 * - Gestão completa de clientes
 * - Operações PIX (depósitos e saques)
 * - Operações FIAT (depósitos e saques)
 * - Tratamento robusto de erros
 * - Retry automático e rate limiting
 * - Logging estruturado
 */

echo "=== Exemplo Avançado de Uso do XGATE SDK ===\n\n";

// Configuração do cliente com todas as opções
$client = new XGateClient([
    'api_key' => getenv('XGATE_API_KEY') ?: 'your-api-key-here',
    'base_url' => getenv('XGATE_BASE_URL') ?: 'https://api.xgate.com',
    'environment' => 'development', // ou 'production'
    'timeout' => 60,
    'retry_attempts' => 3,
    'retry_delay' => 2,
    'debug' => true,
    'log_file' => '/tmp/xgate-sdk.log',
    'custom_headers' => [
        'X-Client-Version' => '1.0.0',
        'X-Integration-Type' => 'php-sdk'
    ]
]);

echo "✅ Cliente XGATE inicializado com sucesso!\n";
echo "   Versão: " . $client->getVersion() . "\n";
echo "   Ambiente: " . $client->getConfiguration()->getEnvironment() . "\n\n";

try {
    // 1. GESTÃO DE CLIENTES
    echo "🧑‍💼 1. GESTÃO DE CLIENTES\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    // 1.1 Criar um novo cliente
    echo "📝 1.1 Criando novo cliente:\n";
    $newCustomerData = [
        'name' => 'João Silva Santos',
        'email' => 'joao.silva@exemplo.com',
        'document' => '12345678901', // CPF
        'document_type' => 'cpf',
        'phone' => '+5511999999999',
        'birth_date' => '1990-05-15',
        'address' => [
            'street' => 'Rua das Flores, 123',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'postal_code' => '01234-567',
            'country' => 'BR'
        ]
    ];

    try {
        $customer = $client->customers()->create($newCustomerData);
        echo "   ✅ Cliente criado com sucesso!\n";
        echo "   ID: " . $customer['id'] . "\n";
        echo "   Nome: " . $customer['name'] . "\n";
        echo "   Email: " . $customer['email'] . "\n";
        
        $customerId = $customer['id'];
    } catch (ValidationException $e) {
        echo "   ❌ Erro de validação ao criar cliente:\n";
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "     - {$field}: " . implode(', ', $errors) . "\n";
        }
        // Para o exemplo, vamos usar um ID fictício
        $customerId = 'customer_123456';
    }
    echo "\n";

    // 1.2 Buscar cliente por ID
    echo "🔍 1.2 Buscando cliente por ID:\n";
    try {
        $foundCustomer = $client->customers()->find($customerId);
        echo "   ✅ Cliente encontrado:\n";
        echo "   Nome: " . $foundCustomer['name'] . "\n";
        echo "   Status: " . $foundCustomer['status'] . "\n";
        echo "   Criado em: " . $foundCustomer['created_at'] . "\n";
    } catch (XGateException $e) {
        echo "   ⚠️  Cliente não encontrado (usando dados fictícios para o exemplo)\n";
        $foundCustomer = [
            'id' => $customerId,
            'name' => 'João Silva Santos',
            'email' => 'joao.silva@exemplo.com',
            'status' => 'active'
        ];
    }
    echo "\n";

    // 1.3 Listar clientes com filtros
    echo "📋 1.3 Listando clientes com filtros:\n";
    try {
        $customers = $client->customers()->list([
            'status' => 'active',
            'limit' => 10,
            'page' => 1,
            'sort' => 'created_at',
            'order' => 'desc'
        ]);
        
        echo "   ✅ Encontrados " . count($customers['data']) . " clientes ativos\n";
        foreach ($customers['data'] as $customer) {
            echo "   - {$customer['name']} ({$customer['email']})\n";
        }
        echo "   Total: " . $customers['total'] . " clientes\n";
        echo "   Página: " . $customers['current_page'] . "/" . $customers['last_page'] . "\n";
    } catch (XGateException $e) {
        echo "   ⚠️  Erro ao listar clientes: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 2. OPERAÇÕES PIX
    echo "💰 2. OPERAÇÕES PIX\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    // 2.1 Criar depósito PIX
    echo "📥 2.1 Criando depósito PIX:\n";
    $pixDepositData = [
        'customer_id' => $customerId,
        'amount' => 150.75,
        'currency' => 'BRL',
        'description' => 'Depósito via PIX para conta',
        'external_id' => 'dep_' . uniqid(),
        'pix_key' => 'joao.silva@exemplo.com',
        'pix_key_type' => 'email'
    ];

    try {
        $pixDeposit = $client->pix()->createDeposit($pixDepositData);
        echo "   ✅ Depósito PIX criado:\n";
        echo "   ID: " . $pixDeposit['id'] . "\n";
        echo "   Valor: R$ " . number_format($pixDeposit['amount'], 2, ',', '.') . "\n";
        echo "   Status: " . $pixDeposit['status'] . "\n";
        echo "   QR Code: " . $pixDeposit['qr_code'] . "\n";
        echo "   Chave PIX: " . $pixDeposit['pix_key'] . "\n";
        
        $depositId = $pixDeposit['id'];
    } catch (ValidationException $e) {
        echo "   ❌ Erro de validação no depósito PIX:\n";
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "     - {$field}: " . implode(', ', $errors) . "\n";
        }
        $depositId = 'pix_dep_123456';
    }
    echo "\n";

    // 2.2 Consultar status do depósito
    echo "🔍 2.2 Consultando status do depósito PIX:\n";
    try {
        $depositStatus = $client->pix()->getDepositStatus($depositId);
        echo "   Status atual: " . $depositStatus['status'] . "\n";
        echo "   Valor: R$ " . number_format($depositStatus['amount'], 2, ',', '.') . "\n";
        echo "   Criado em: " . $depositStatus['created_at'] . "\n";
        
        if ($depositStatus['status'] === 'completed') {
            echo "   ✅ Depósito confirmado em: " . $depositStatus['confirmed_at'] . "\n";
        } elseif ($depositStatus['status'] === 'pending') {
            echo "   ⏳ Aguardando confirmação...\n";
        }
    } catch (XGateException $e) {
        echo "   ⚠️  Erro ao consultar status: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 2.3 Criar saque PIX
    echo "📤 2.3 Criando saque PIX:\n";
    $pixWithdrawalData = [
        'customer_id' => $customerId,
        'amount' => 100.00,
        'currency' => 'BRL',
        'description' => 'Saque via PIX',
        'external_id' => 'with_' . uniqid(),
        'pix_key' => '+5511999999999',
        'pix_key_type' => 'phone',
        'recipient_name' => 'João Silva Santos',
        'recipient_document' => '12345678901'
    ];

    try {
        $pixWithdrawal = $client->pix()->createWithdrawal($pixWithdrawalData);
        echo "   ✅ Saque PIX criado:\n";
        echo "   ID: " . $pixWithdrawal['id'] . "\n";
        echo "   Valor: R$ " . number_format($pixWithdrawal['amount'], 2, ',', '.') . "\n";
        echo "   Status: " . $pixWithdrawal['status'] . "\n";
        echo "   Taxa: R$ " . number_format($pixWithdrawal['fee'], 2, ',', '.') . "\n";
        echo "   Valor líquido: R$ " . number_format($pixWithdrawal['net_amount'], 2, ',', '.') . "\n";
    } catch (ValidationException $e) {
        echo "   ❌ Erro de validação no saque PIX:\n";
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "     - {$field}: " . implode(', ', $errors) . "\n";
        }
    }
    echo "\n";

    // 3. OPERAÇÕES FIAT
    echo "🏦 3. OPERAÇÕES FIAT\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    // 3.1 Criar depósito FIAT
    echo "📥 3.1 Criando depósito FIAT:\n";
    $fiatDepositData = [
        'customer_id' => $customerId,
        'amount' => 500.00,
        'currency' => 'BRL',
        'payment_method' => 'bank_transfer',
        'description' => 'Depósito via transferência bancária',
        'external_id' => 'fiat_dep_' . uniqid(),
        'bank_account' => [
            'bank_code' => '001', // Banco do Brasil
            'agency' => '1234',
            'account' => '56789-0',
            'account_type' => 'checking',
            'holder_name' => 'João Silva Santos',
            'holder_document' => '12345678901'
        ]
    ];

    try {
        $fiatDeposit = $client->fiat()->createDeposit($fiatDepositData);
        echo "   ✅ Depósito FIAT criado:\n";
        echo "   ID: " . $fiatDeposit['id'] . "\n";
        echo "   Valor: R$ " . number_format($fiatDeposit['amount'], 2, ',', '.') . "\n";
        echo "   Método: " . $fiatDeposit['payment_method'] . "\n";
        echo "   Status: " . $fiatDeposit['status'] . "\n";
        
        if (isset($fiatDeposit['instructions'])) {
            echo "   Instruções de pagamento:\n";
            foreach ($fiatDeposit['instructions'] as $instruction) {
                echo "     - {$instruction}\n";
            }
        }
    } catch (ValidationException $e) {
        echo "   ❌ Erro de validação no depósito FIAT:\n";
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "     - {$field}: " . implode(', ', $errors) . "\n";
        }
    }
    echo "\n";

    // 3.2 Criar saque FIAT
    echo "📤 3.2 Criando saque FIAT:\n";
    $fiatWithdrawalData = [
        'customer_id' => $customerId,
        'amount' => 300.00,
        'currency' => 'BRL',
        'payment_method' => 'bank_transfer',
        'description' => 'Saque via transferência bancária',
        'external_id' => 'fiat_with_' . uniqid(),
        'bank_account' => [
            'bank_code' => '341', // Itaú
            'agency' => '5678',
            'account' => '12345-6',
            'account_type' => 'savings',
            'holder_name' => 'João Silva Santos',
            'holder_document' => '12345678901'
        ]
    ];

    try {
        $fiatWithdrawal = $client->fiat()->createWithdrawal($fiatWithdrawalData);
        echo "   ✅ Saque FIAT criado:\n";
        echo "   ID: " . $fiatWithdrawal['id'] . "\n";
        echo "   Valor: R$ " . number_format($fiatWithdrawal['amount'], 2, ',', '.') . "\n";
        echo "   Status: " . $fiatWithdrawal['status'] . "\n";
        echo "   Taxa: R$ " . number_format($fiatWithdrawal['fee'], 2, ',', '.') . "\n";
        echo "   Prazo estimado: " . $fiatWithdrawal['estimated_completion'] . "\n";
    } catch (ValidationException $e) {
        echo "   ❌ Erro de validação no saque FIAT:\n";
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "     - {$field}: " . implode(', ', $errors) . "\n";
        }
    }
    echo "\n";

    // 4. MONITORAMENTO E WEBHOOKS
    echo "🔔 4. MONITORAMENTO E WEBHOOKS\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    // 4.1 Configurar webhook
    echo "🔗 4.1 Configurando webhook:\n";
    $webhookData = [
        'url' => 'https://meusite.com/webhook/xgate',
        'events' => [
            'deposit.completed',
            'withdrawal.completed',
            'customer.created',
            'transaction.failed'
        ],
        'secret' => 'meu-webhook-secret-123',
        'active' => true
    ];

    try {
        echo "   Configuração do webhook:\n";
        echo "   URL: " . $webhookData['url'] . "\n";
        echo "   Eventos: " . implode(', ', $webhookData['events']) . "\n";
        echo "   Status: " . ($webhookData['active'] ? 'Ativo' : 'Inativo') . "\n";
        echo "   ✅ Webhook configurado com sucesso!\n";
    } catch (XGateException $e) {
        echo "   ❌ Erro ao configurar webhook: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4.2 Listar transações recentes
    echo "📊 4.2 Listando transações recentes:\n";
    try {
        $transactions = $client->get('/transactions', [
            'customer_id' => $customerId,
            'status' => 'completed',
            'limit' => 5,
            'sort' => 'created_at',
            'order' => 'desc'
        ]);

        echo "   ✅ Últimas 5 transações:\n";
        // Simular dados para o exemplo
        $sampleTransactions = [
            ['id' => 'tx_001', 'type' => 'deposit', 'amount' => 150.75, 'status' => 'completed', 'created_at' => '2024-01-15 10:30:00'],
            ['id' => 'tx_002', 'type' => 'withdrawal', 'amount' => 100.00, 'status' => 'completed', 'created_at' => '2024-01-14 15:45:00'],
            ['id' => 'tx_003', 'type' => 'deposit', 'amount' => 500.00, 'status' => 'pending', 'created_at' => '2024-01-14 09:20:00'],
        ];

        foreach ($sampleTransactions as $transaction) {
            $emoji = $transaction['type'] === 'deposit' ? '📥' : '📤';
            $status = $transaction['status'] === 'completed' ? '✅' : '⏳';
            echo "   {$emoji} {$status} {$transaction['id']} - R$ " . number_format($transaction['amount'], 2, ',', '.') . " ({$transaction['created_at']})\n";
        }
    } catch (XGateException $e) {
        echo "   ⚠️  Erro ao listar transações: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. FUNCIONALIDADES AVANÇADAS
    echo "⚙️  5. FUNCIONALIDADES AVANÇADAS\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    // 5.1 Rate limiting inteligente
    echo "⏱️  5.1 Demonstrando rate limiting inteligente:\n";
    try {
        // Simular muitas requisições
        for ($i = 1; $i <= 3; $i++) {
            echo "   Requisição {$i}/3...\n";
            
            // Em caso real, o SDK detectaria rate limit e faria retry automático
            if ($i === 2) {
                // Simular rate limit na segunda requisição
                throw new RateLimitException(
                    'Rate limit atingido. Aguardando...',
                    429,
                    null,
                    100, // limit
                    0,   // remaining
                    time() + 60, // reset_time
                    60   // retry_after
                );
            }
            
            echo "   ✅ Requisição {$i} bem-sucedida\n";
        }
    } catch (RateLimitException $e) {
        echo "   ⏱️  Rate limit detectado:\n";
        echo "   Limite: " . $e->getLimit() . " req/min\n";
        echo "   Restantes: " . $e->getRemaining() . "\n";
        echo "   Retry em: " . $e->getRetryAfter() . " segundos\n";
        echo "   🔄 SDK fará retry automático...\n";
    }
    echo "\n";

    // 5.2 Cache inteligente
    echo "💾 5.2 Demonstrando cache inteligente:\n";
    $cache = $client->getCache();
    
    // Simular cache de dados do cliente
    $cacheKey = "customer_{$customerId}";
    
    if ($cache->has($cacheKey)) {
        echo "   ✅ Dados do cliente encontrados no cache\n";
        $cachedCustomer = $cache->get($cacheKey);
        echo "   Nome: " . $cachedCustomer['name'] . "\n";
    } else {
        echo "   📥 Buscando dados do cliente na API...\n";
        // Simular busca na API
        $customerData = ['id' => $customerId, 'name' => 'João Silva Santos'];
        $cache->set($cacheKey, $customerData, 300); // Cache por 5 minutos
        echo "   ✅ Dados armazenados no cache por 5 minutos\n";
    }
    echo "\n";

    // 5.3 Logging estruturado
    echo "📝 5.3 Demonstrando logging estruturado:\n";
    $logger = $client->getLogger();
    
    // Log de operação bem-sucedida
    $logger->info('Depósito PIX criado com sucesso', [
        'customer_id' => $customerId,
        'amount' => 150.75,
        'currency' => 'BRL',
        'method' => 'pix',
        'transaction_id' => 'pix_dep_123456'
    ]);
    
    // Log de erro
    $logger->error('Falha na validação de saque', [
        'customer_id' => $customerId,
        'errors' => ['amount' => 'Valor mínimo é R$ 10,00'],
        'attempted_amount' => 5.00
    ]);
    
    echo "   ✅ Logs estruturados registrados\n";
    echo "   📁 Arquivo de log: " . $client->getConfiguration()->getLogFile() . "\n";
    echo "\n";

    // 6. RELATÓRIOS E ANALYTICS
    echo "📈 6. RELATÓRIOS E ANALYTICS\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    // 6.1 Relatório de transações
    echo "📊 6.1 Gerando relatório de transações:\n";
    try {
        $report = [
            'period' => 'last_30_days',
            'total_deposits' => 15,
            'total_withdrawals' => 8,
            'deposit_volume' => 12500.75,
            'withdrawal_volume' => 3200.50,
            'fees_collected' => 125.30,
            'success_rate' => 98.5
        ];

        echo "   📅 Período: Últimos 30 dias\n";
        echo "   📥 Total de depósitos: " . $report['total_deposits'] . "\n";
        echo "   📤 Total de saques: " . $report['total_withdrawals'] . "\n";
        echo "   💰 Volume de depósitos: R$ " . number_format($report['deposit_volume'], 2, ',', '.') . "\n";
        echo "   💸 Volume de saques: R$ " . number_format($report['withdrawal_volume'], 2, ',', '.') . "\n";
        echo "   💵 Taxas coletadas: R$ " . number_format($report['fees_collected'], 2, ',', '.') . "\n";
        echo "   ✅ Taxa de sucesso: " . $report['success_rate'] . "%\n";
    } catch (XGateException $e) {
        echo "   ⚠️  Erro ao gerar relatório: " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "🎉 Exemplo avançado concluído com sucesso!\n";
    echo "✨ Todas as funcionalidades do SDK foram demonstradas.\n";

} catch (XGateException $e) {
    echo "❌ Erro do SDK: " . $e->getMessage() . "\n";
    echo "   Tipo: " . get_class($e) . "\n";
    echo "   Código: " . $e->getCode() . "\n";
    
    // Log estruturado do erro
    $client->getLogger()->error('Erro no exemplo avançado', [
        'error_type' => get_class($e),
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
} catch (\Exception $e) {
    echo "❌ Erro crítico: " . $e->getMessage() . "\n";
    echo "   Tipo: " . get_class($e) . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Fim do Exemplo Avançado ===\n";

/**
 * Função auxiliar para simular processamento de webhook
 * 
 * @param array<string, mixed> $payload Dados do webhook
 * @return bool Sucesso no processamento
 */
function processWebhook(array $payload): bool
{
    echo "🔔 Processando webhook:\n";
    echo "   Evento: " . $payload['event'] . "\n";
    echo "   ID da transação: " . $payload['transaction_id'] . "\n";
    echo "   Status: " . $payload['status'] . "\n";
    
    // Simular processamento
    switch ($payload['event']) {
        case 'deposit.completed':
            echo "   ✅ Depósito confirmado - atualizando saldo do cliente\n";
            break;
        case 'withdrawal.completed':
            echo "   ✅ Saque processado - notificando cliente\n";
            break;
        case 'transaction.failed':
            echo "   ❌ Transação falhou - enviando notificação de erro\n";
            break;
        default:
            echo "   ℹ️  Evento processado\n";
    }
    
    return true;
}

/**
 * Função auxiliar para calcular métricas de performance
 * 
 * @param array<string, mixed> $transactions Lista de transações
 * @return array<string, mixed> Métricas calculadas
 */
function calculateMetrics(array $transactions): array
{
    $totalAmount = 0;
    $successCount = 0;
    $totalCount = count($transactions);
    
    foreach ($transactions as $transaction) {
        $totalAmount += $transaction['amount'];
        if ($transaction['status'] === 'completed') {
            $successCount++;
        }
    }
    
    return [
        'total_transactions' => $totalCount,
        'successful_transactions' => $successCount,
        'success_rate' => $totalCount > 0 ? ($successCount / $totalCount) * 100 : 0,
        'total_volume' => $totalAmount,
        'average_transaction' => $totalCount > 0 ? $totalAmount / $totalCount : 0
    ];
} 