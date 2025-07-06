<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\Model\Customer;
use XGate\Model\Transaction;
use XGate\Model\PixKey;
use XGate\Exception\ApiException;
use XGate\Exception\ValidationException;
use XGate\Exception\NetworkException;
use XGate\Exception\RateLimitException;
use XGate\Exception\AuthenticationException;

/**
 * Classe Helper para Testes Unitários
 * 
 * Esta classe fornece métodos utilitários para facilitar a criação de testes:
 * - Geração de dados mock
 * - Criação de objetos de teste
 * - Validadores
 * - Formatadores
 * - Simuladores de resposta HTTP
 */
class UnitTestHelper
{
    /**
     * Gera dados de cliente de teste
     */
    public static function createTestCustomerData(int $suffix = null): array
    {
        $suffix = $suffix ?? time();
        
        return [
            'name' => "Cliente Teste {$suffix}",
            'email' => "teste.cliente.{$suffix}@exemplo.com",
            'phone' => "+5511999{$suffix}",
            'document' => self::generateCPF(),
            'document_type' => 'cpf',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Cria um objeto Customer mock
     */
    public static function createMockCustomer(array $data = []): Customer
    {
        $defaultData = self::createTestCustomerData();
        $customerData = array_merge($defaultData, $data);
        
        return new Customer(
            id: $customerData['id'] ?? 'customer_' . uniqid(),
            name: $customerData['name'],
            email: $customerData['email'],
            phone: $customerData['phone'],
            document: $customerData['document'],
            documentType: $customerData['document_type'],
            status: $customerData['status'],
            createdAt: new DateTime($customerData['created_at']),
            updatedAt: new DateTime($customerData['updated_at'])
        );
    }
    
    /**
     * Gera dados de transação de teste
     */
    public static function createTestTransactionData(string $type = 'deposit', int $suffix = null): array
    {
        $suffix = $suffix ?? time();
        
        return [
            'id' => "transaction_{$type}_{$suffix}",
            'amount' => number_format(mt_rand(1000, 50000) / 100, 2, '.', ''),
            'currency' => 'BRL',
            'account_id' => 'customer_' . $suffix,
            'payment_method' => self::getPaymentMethodForType($type),
            'type' => $type,
            'status' => 'pending',
            'reference_id' => "ref_{$type}_{$suffix}",
            'description' => "Transação de teste - {$type}",
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Cria um objeto Transaction mock
     */
    public static function createMockTransaction(string $type = 'deposit', array $data = []): Transaction
    {
        $defaultData = self::createTestTransactionData($type);
        $transactionData = array_merge($defaultData, $data);
        
        return new Transaction(
            id: $transactionData['id'],
            amount: $transactionData['amount'],
            currency: $transactionData['currency'],
            accountId: $transactionData['account_id'],
            paymentMethod: $transactionData['payment_method'],
            type: $transactionData['type'],
            status: $transactionData['status'],
            referenceId: $transactionData['reference_id'],
            description: $transactionData['description'],
            createdAt: new DateTime($transactionData['created_at']),
            updatedAt: new DateTime($transactionData['updated_at'])
        );
    }
    
    /**
     * Gera dados de chave PIX de teste
     */
    public static function createTestPixKeyData(string $type = 'email', int $suffix = null): array
    {
        $suffix = $suffix ?? time();
        
        $keys = [
            'email' => "pix.teste.{$suffix}@exemplo.com",
            'phone' => "+5511999{$suffix}",
            'document' => self::generateCPF(),
            'random' => self::generateRandomPixKey()
        ];
        
        return [
            'id' => "pixkey_{$type}_{$suffix}",
            'key' => $keys[$type] ?? $keys['email'],
            'type' => $type,
            'owner_name' => "Proprietário PIX {$suffix}",
            'owner_document' => self::generateCPF(),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Cria um objeto PixKey mock
     */
    public static function createMockPixKey(string $type = 'email', array $data = []): PixKey
    {
        $defaultData = self::createTestPixKeyData($type);
        $pixKeyData = array_merge($defaultData, $data);
        
        return new PixKey(
            key: $pixKeyData['key'],
            type: $pixKeyData['type'],
            ownerName: $pixKeyData['owner_name'],
            ownerDocument: $pixKeyData['owner_document'],
            id: $pixKeyData['id'] ?? null,
            status: $pixKeyData['status'] ?? 'active',
            createdAt: isset($pixKeyData['created_at']) ? new DateTime($pixKeyData['created_at']) : null
        );
    }
    
    /**
     * Cria resposta HTTP mock para sucesso
     */
    public static function createSuccessResponse(array $data = [], int $statusCode = 200): array
    {
        return [
            'status_code' => $statusCode,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Request-ID' => uniqid('req_'),
                'X-Response-Time' => mt_rand(50, 500) . 'ms'
            ],
            'body' => json_encode([
                'success' => true,
                'data' => $data,
                'message' => 'Operação realizada com sucesso',
                'timestamp' => date('c')
            ])
        ];
    }
    
    /**
     * Cria resposta HTTP mock para erro
     */
    public static function createErrorResponse(
        string $message = 'Erro interno do servidor',
        int $statusCode = 500,
        string $errorCode = 'INTERNAL_ERROR',
        array $details = []
    ): array {
        return [
            'status_code' => $statusCode,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Request-ID' => uniqid('req_'),
                'X-Error-Code' => $errorCode
            ],
            'body' => json_encode([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $message,
                    'details' => $details
                ],
                'timestamp' => date('c')
            ])
        ];
    }
    
    /**
     * Cria resposta HTTP mock para erro de validação
     */
    public static function createValidationErrorResponse(array $fieldErrors = []): array
    {
        $defaultErrors = [
            'email' => ['Email é obrigatório', 'Formato de email inválido'],
            'document' => ['Documento é obrigatório', 'Formato de documento inválido'],
            'amount' => ['Valor é obrigatório', 'Valor deve ser positivo']
        ];
        
        $errors = array_merge($defaultErrors, $fieldErrors);
        
        return [
            'status_code' => 422,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Request-ID' => uniqid('req_'),
                'X-Error-Code' => 'VALIDATION_ERROR'
            ],
            'body' => json_encode([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dados de entrada inválidos',
                    'validation_errors' => $errors
                ],
                'timestamp' => date('c')
            ])
        ];
    }
    
    /**
     * Cria resposta HTTP mock para rate limiting
     */
    public static function createRateLimitResponse(int $retryAfter = 60): array
    {
        return [
            'status_code' => 429,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Request-ID' => uniqid('req_'),
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (time() + $retryAfter),
                'Retry-After' => $retryAfter
            ],
            'body' => json_encode([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Limite de requisições excedido',
                    'retry_after' => $retryAfter
                ],
                'timestamp' => date('c')
            ])
        ];
    }
    
    /**
     * Cria resposta HTTP mock para erro de autenticação
     */
    public static function createAuthenticationErrorResponse(): array
    {
        return [
            'status_code' => 401,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Request-ID' => uniqid('req_'),
                'X-Error-Code' => 'AUTHENTICATION_FAILED'
            ],
            'body' => json_encode([
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_FAILED',
                    'message' => 'Credenciais inválidas ou token expirado'
                ],
                'timestamp' => date('c')
            ])
        ];
    }
    
    /**
     * Valida formato de email
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida formato de CPF (validação básica)
     */
    public static function isValidCPF(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Validação básica dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida formato de CNPJ (validação básica)
     */
    public static function isValidCNPJ(string $cnpj): bool
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verifica se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Validação básica dos dígitos verificadores
        $b = [6, 7, 8, 9, 2, 3, 4, 5, 6, 7, 8, 9];
        
        for ($i = 0, $n = 0; $i < 12; $n += $cnpj[$i] * $b[++$i]);
        
        if ($cnpj[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return false;
        }
        
        for ($i = 0, $n = 0; $i <= 12; $n += $cnpj[$i] * $b[$i++]);
        
        if ($cnpj[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida formato de telefone brasileiro
     */
    public static function isValidPhone(string $phone): bool
    {
        // Remove caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Verifica se tem 10 ou 11 dígitos (com DDD)
        return in_array(strlen($phone), [10, 11]) && preg_match('/^[1-9][1-9]\d{8,9}$/', $phone);
    }
    
    /**
     * Valida valor monetário
     */
    public static function isValidAmount(string $amount): bool
    {
        return is_numeric($amount) && floatval($amount) > 0;
    }
    
    /**
     * Formata valor monetário
     */
    public static function formatAmount(string $amount, string $currency = 'BRL'): string
    {
        $symbols = [
            'BRL' => 'R$ ',
            'USD' => '$ ',
            'EUR' => '€ '
        ];
        
        $symbol = $symbols[$currency] ?? '';
        return $symbol . number_format(floatval($amount), 2, ',', '.');
    }
    
    /**
     * Formata CPF
     */
    public static function formatCPF(string $cpf): string
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    
    /**
     * Formata CNPJ
     */
    public static function formatCNPJ(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }
    
    /**
     * Formata telefone
     */
    public static function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        } elseif (strlen($phone) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        }
        
        return $phone;
    }
    
    /**
     * Gera CPF válido para teste
     */
    public static function generateCPF(): string
    {
        $cpf = '';
        
        // Gera os 9 primeiros dígitos
        for ($i = 0; $i < 9; $i++) {
            $cpf .= mt_rand(0, 9);
        }
        
        // Calcula o primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $cpf .= ($remainder < 2) ? 0 : (11 - $remainder);
        
        // Calcula o segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $cpf .= ($remainder < 2) ? 0 : (11 - $remainder);
        
        return $cpf;
    }
    
    /**
     * Gera CNPJ válido para teste
     */
    public static function generateCNPJ(): string
    {
        $cnpj = '';
        
        // Gera os 12 primeiros dígitos
        for ($i = 0; $i < 12; $i++) {
            $cnpj .= mt_rand(0, 9);
        }
        
        // Calcula os dígitos verificadores
        $b = [6, 7, 8, 9, 2, 3, 4, 5, 6, 7, 8, 9];
        
        for ($i = 0, $n = 0; $i < 12; $n += $cnpj[$i] * $b[++$i]);
        $cnpj .= ((($n %= 11) < 2) ? 0 : 11 - $n);
        
        for ($i = 0, $n = 0; $i <= 12; $n += $cnpj[$i] * $b[$i++]);
        $cnpj .= ((($n %= 11) < 2) ? 0 : 11 - $n);
        
        return $cnpj;
    }
    
    /**
     * Gera chave PIX aleatória
     */
    public static function generateRandomPixKey(): string
    {
        return strtolower(bin2hex(random_bytes(16)));
    }
    
    /**
     * Obtém método de pagamento baseado no tipo de transação
     */
    private static function getPaymentMethodForType(string $type): string
    {
        $methods = [
            'deposit' => 'crypto_transfer',
            'withdraw' => 'bank_transfer',
            'pix_payment' => 'pix',
            'transfer' => 'internal_transfer'
        ];
        
        return $methods[$type] ?? 'unknown';
    }
    
    /**
     * Cria logger mock para testes
     */
    public static function createMockLogger(): object
    {
        return new class {
            private array $logs = [];
            
            public function info(string $message, array $context = []): void
            {
                $this->logs[] = ['level' => 'info', 'message' => $message, 'context' => $context];
            }
            
            public function error(string $message, array $context = []): void
            {
                $this->logs[] = ['level' => 'error', 'message' => $message, 'context' => $context];
            }
            
            public function debug(string $message, array $context = []): void
            {
                $this->logs[] = ['level' => 'debug', 'message' => $message, 'context' => $context];
            }
            
            public function warning(string $message, array $context = []): void
            {
                $this->logs[] = ['level' => 'warning', 'message' => $message, 'context' => $context];
            }
            
            public function getLogs(): array
            {
                return $this->logs;
            }
            
            public function clearLogs(): void
            {
                $this->logs = [];
            }
            
            public function hasLogLevel(string $level): bool
            {
                return !empty(array_filter($this->logs, fn($log) => $log['level'] === $level));
            }
        };
    }
    
    /**
     * Simula delay de rede
     */
    public static function simulateNetworkDelay(int $minMs = 100, int $maxMs = 1000): void
    {
        $delay = mt_rand($minMs, $maxMs) * 1000; // Converte para microsegundos
        usleep($delay);
    }
    
    /**
     * Gera dados de paginação mock
     */
    public static function createMockPagination(int $page = 1, int $perPage = 10, int $total = 100): array
    {
        $totalPages = ceil($total / $perPage);
        
        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1,
            'next_page' => $page < $totalPages ? $page + 1 : null,
            'previous_page' => $page > 1 ? $page - 1 : null
        ];
    }
    
    /**
     * Cria lista mock de dados
     */
    public static function createMockList(string $type, int $count = 10): array
    {
        $items = [];
        
        for ($i = 1; $i <= $count; $i++) {
            switch ($type) {
                case 'customers':
                    $items[] = self::createMockCustomer(['id' => "customer_{$i}"]);
                    break;
                case 'transactions':
                    $items[] = self::createMockTransaction('deposit', ['id' => "transaction_{$i}"]);
                    break;
                case 'pix_keys':
                    $items[] = self::createMockPixKey('email', ['id' => "pixkey_{$i}"]);
                    break;
                default:
                    $items[] = ['id' => "{$type}_{$i}", 'name' => "Item {$i}"];
            }
        }
        
        return $items;
    }
    
    /**
     * Limpa dados de teste
     */
    public static function cleanup(): void
    {
        // Método para limpeza de dados de teste
        // Pode ser implementado conforme necessário
    }
    
    /**
     * Utilitário para converter array em objeto
     */
    public static function arrayToObject(array $array): object
    {
        return json_decode(json_encode($array));
    }
    
    /**
     * Utilitário para converter objeto em array
     */
    public static function objectToArray(object $object): array
    {
        return json_decode(json_encode($object), true);
    }
    
    /**
     * Verifica se uma string é um JSON válido
     */
    public static function isValidJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Gera hash MD5 para teste
     */
    public static function generateTestHash(string $input = null): string
    {
        return md5($input ?? uniqid());
    }
    
    /**
     * Gera timestamp para teste
     */
    public static function generateTestTimestamp(int $offset = 0): string
    {
        return date('Y-m-d H:i:s', time() + $offset);
    }
    
    /**
     * Cria configuração de teste
     */
    public static function createTestConfig(): array
    {
        return [
            'base_url' => 'https://api.xgate.com',
            'environment' => 'test',
            'timeout' => 30,
            'debug' => true,
            'retries' => 3,
            'retry_delay' => 1000,
            'user_agent' => 'XGATE-PHP-SDK-Test/1.0'
        ];
    }
}

// Exemplo de uso
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "=== DEMONSTRAÇÃO DO UNIT TEST HELPER ===\n\n";
    
    // Exemplo 1: Criar cliente mock
    echo "1. Cliente Mock:\n";
    $customer = UnitTestHelper::createMockCustomer();
    echo "   ID: {$customer->id}\n";
    echo "   Nome: {$customer->name}\n";
    echo "   Email: {$customer->email}\n";
    echo "   CPF: " . UnitTestHelper::formatCPF($customer->document) . "\n\n";
    
    // Exemplo 2: Criar transação mock
    echo "2. Transação Mock:\n";
    $transaction = UnitTestHelper::createMockTransaction('deposit');
    echo "   ID: {$transaction->id}\n";
    echo "   Valor: " . UnitTestHelper::formatAmount($transaction->amount) . "\n";
    echo "   Tipo: {$transaction->type}\n";
    echo "   Status: {$transaction->status}\n\n";
    
    // Exemplo 3: Validações
    echo "3. Validações:\n";
    echo "   Email válido: " . (UnitTestHelper::isValidEmail('teste@exemplo.com') ? 'Sim' : 'Não') . "\n";
    echo "   CPF válido: " . (UnitTestHelper::isValidCPF('12345678901') ? 'Sim' : 'Não') . "\n";
    echo "   Valor válido: " . (UnitTestHelper::isValidAmount('100.50') ? 'Sim' : 'Não') . "\n\n";
    
    // Exemplo 4: Resposta HTTP mock
    echo "4. Resposta HTTP Mock:\n";
    $response = UnitTestHelper::createSuccessResponse(['id' => 123, 'name' => 'Teste']);
    echo "   Status: {$response['status_code']}\n";
    echo "   Body: " . substr($response['body'], 0, 100) . "...\n\n";
    
    echo "✅ Demonstração concluída!\n";
} 