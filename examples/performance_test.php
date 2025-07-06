<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/unit_test_helper.php';

use XGate\XGateClient;
use XGate\Resource\CustomerResource;
use XGate\Resource\DepositResource;
use XGate\Resource\WithdrawResource;
use XGate\Resource\PixResource;
use XGate\Model\Customer;
use XGate\Model\Transaction;
use XGate\Exception\XGateException;

/**
 * Teste de Performance do SDK XGATE
 * 
 * Este arquivo implementa testes específicos de performance:
 * - Tempo de resposta das operações
 * - Throughput de requisições
 * - Uso de memória
 * - Operações em lote
 * - Simulação de carga
 * - Métricas de concorrência
 */

class PerformanceTester
{
    private XGateClient $client;
    private CustomerResource $customerResource;
    private DepositResource $depositResource;
    private WithdrawResource $withdrawResource;
    private PixResource $pixResource;
    private array $metrics = [];
    private array $createdCustomers = [];
    
    public function __construct()
    {
        $this->loadEnvironment();
        $this->initializeClient();
        $this->initializeResources();
    }
    
    /**
     * Carrega variáveis de ambiente
     */
    private function loadEnvironment(): void
    {
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new RuntimeException("Arquivo .env não encontrado: {$envFile}");
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remove aspas duplas ou simples do valor
                $value = trim($value, '"\'');
                
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
    
    /**
     * Inicializa o cliente XGATE
     */
    private function initializeClient(): void
    {
        $this->client = new XGateClient([
            'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgate.com',
            'environment' => $_ENV['XGATE_ENVIRONMENT'] ?? 'development',
            'timeout' => 60,
            'debug' => false, // Desabilita debug para performance
        ]);
    }
    
    /**
     * Inicializa recursos
     */
    private function initializeResources(): void
    {
        $httpClient = $this->client->getHttpClient();
        $logger = $this->client->getLogger();
        
        $this->customerResource = new CustomerResource($httpClient, $logger);
        $this->depositResource = new DepositResource($httpClient, $logger);
        $this->withdrawResource = new WithdrawResource($httpClient, $logger);
        $this->pixResource = new PixResource($httpClient, $logger);
    }
    
    /**
     * Executa todos os testes de performance
     */
    public function runAllTests(): void
    {
        echo "=== TESTE DE PERFORMANCE - SDK XGATE ===\n\n";
        
        $tests = [
            'testAuthenticationPerformance' => 'Performance de Autenticação',
            'testIndividualOperations' => 'Performance de Operações Individuais',
            'testBatchOperations' => 'Performance de Operações em Lote',
            'testConcurrentOperations' => 'Performance de Operações Concorrentes',
            'testMemoryUsage' => 'Uso de Memória',
            'testLoadTest' => 'Teste de Carga',
        ];
        
        foreach ($tests as $method => $description) {
            echo "⚡ Executando: {$description}\n";
            try {
                $this->$method();
                echo "   ✅ Concluído\n\n";
            } catch (Exception $e) {
                echo "   ❌ Falhou: " . $e->getMessage() . "\n\n";
            }
        }
        
        $this->printPerformanceReport();
        $this->cleanup();
    }
    
    /**
     * Testa performance de autenticação
     */
    private function testAuthenticationPerformance(): void
    {
        $email = $_ENV['XGATE_EMAIL'] ?? null;
        $password = $_ENV['XGATE_PASSWORD'] ?? null;
        
        if (!$email || !$password) {
            throw new RuntimeException("Credenciais não configuradas");
        }
        
        $iterations = 5;
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            // Cria novo cliente para cada teste
            $testClient = new XGateClient([
                'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgate.com',
                'environment' => $_ENV['XGATE_ENVIRONMENT'] ?? 'development',
                'timeout' => 60,
                'debug' => false,
            ]);
            
            $startTime = microtime(true);
            $testClient->authenticate($email, $password);
            $endTime = microtime(true);
            
            $times[] = $endTime - $startTime;
            
            // Pequena pausa entre testes
            usleep(100000); // 100ms
        }
        
        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);
        
        $this->metrics['authentication'] = [
            'avg_time' => $avgTime,
            'min_time' => $minTime,
            'max_time' => $maxTime,
            'iterations' => $iterations,
        ];
        
        echo "   📊 Tempo médio: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "   ⚡ Tempo mínimo: " . number_format($minTime * 1000, 2) . "ms\n";
        echo "   🐌 Tempo máximo: " . number_format($maxTime * 1000, 2) . "ms\n";
    }
    
    /**
     * Testa performance de operações individuais
     */
    private function testIndividualOperations(): void
    {
        // Autentica primeiro
        $this->client->authenticate($_ENV['XGATE_EMAIL'], $_ENV['XGATE_PASSWORD']);
        
        $operations = [
            'customer_create' => function() {
                $data = UnitTestHelper::generateCustomerData();
                $customer = $this->customerResource->create($data);
                $this->createdCustomers[] = $customer->getId();
                return $customer;
            },
            'customer_get' => function() {
                if (empty($this->createdCustomers)) {
                    throw new RuntimeException("Nenhum cliente criado para teste");
                }
                $customerId = $this->createdCustomers[array_rand($this->createdCustomers)];
                return $this->customerResource->get($customerId);
            },
            'customer_list' => function() {
                return $this->customerResource->list();
            },
            'deposit_currencies' => function() {
                return $this->depositResource->listSupportedCurrencies();
            },
            'withdraw_currencies' => function() {
                return $this->withdrawResource->listSupportedCurrencies();
            },
        ];
        
        foreach ($operations as $operationName => $operation) {
            $iterations = 3;
            $times = [];
            
            for ($i = 0; $i < $iterations; $i++) {
                try {
                    $startTime = microtime(true);
                    $operation();
                    $endTime = microtime(true);
                    
                    $times[] = $endTime - $startTime;
                    
                    // Pequena pausa entre operações
                    usleep(200000); // 200ms
                } catch (Exception $e) {
                    echo "   ⚠️  Erro na operação {$operationName}: " . $e->getMessage() . "\n";
                    continue;
                }
            }
            
            if (!empty($times)) {
                $avgTime = array_sum($times) / count($times);
                $this->metrics['individual_operations'][$operationName] = [
                    'avg_time' => $avgTime,
                    'iterations' => count($times),
                ];
                
                echo "   📊 {$operationName}: " . number_format($avgTime * 1000, 2) . "ms\n";
            }
        }
    }
    
    /**
     * Testa performance de operações em lote
     */
    private function testBatchOperations(): void
    {
        $this->client->authenticate($_ENV['XGATE_EMAIL'], $_ENV['XGATE_PASSWORD']);
        
        $batchSizes = [5, 10, 20];
        
        foreach ($batchSizes as $batchSize) {
            echo "   🔄 Testando lote de {$batchSize} clientes\n";
            
            $startTime = microtime(true);
            $createdCount = 0;
            
            for ($i = 0; $i < $batchSize; $i++) {
                try {
                    $customerData = UnitTestHelper::generateCustomerData();
                    $customer = $this->customerResource->create($customerData);
                    $this->createdCustomers[] = $customer->getId();
                    $createdCount++;
                } catch (Exception $e) {
                    echo "     ⚠️  Erro ao criar cliente {$i}: " . $e->getMessage() . "\n";
                }
                
                // Pequena pausa para evitar rate limiting
                usleep(50000); // 50ms
            }
            
            $totalTime = microtime(true) - $startTime;
            $avgTimePerItem = $totalTime / $createdCount;
            $throughput = $createdCount / $totalTime;
            
            $this->metrics['batch_operations'][$batchSize] = [
                'total_time' => $totalTime,
                'avg_time_per_item' => $avgTimePerItem,
                'throughput' => $throughput,
                'created_count' => $createdCount,
                'success_rate' => ($createdCount / $batchSize) * 100,
            ];
            
            echo "     📊 Tempo total: " . number_format($totalTime * 1000, 2) . "ms\n";
            echo "     📊 Tempo médio por item: " . number_format($avgTimePerItem * 1000, 2) . "ms\n";
            echo "     📊 Throughput: " . number_format($throughput, 2) . " ops/seg\n";
            echo "     📊 Taxa de sucesso: " . number_format(($createdCount / $batchSize) * 100, 1) . "%\n";
        }
    }
    
    /**
     * Testa operações concorrentes (simulação)
     */
    private function testConcurrentOperations(): void
    {
        $this->client->authenticate($_ENV['XGATE_EMAIL'], $_ENV['XGATE_PASSWORD']);
        
        echo "   🔄 Simulando operações concorrentes\n";
        
        $operations = [];
        $concurrentCount = 5;
        
        // Prepara operações para execução "concorrente"
        for ($i = 0; $i < $concurrentCount; $i++) {
            $operations[] = function() {
                return $this->customerResource->list();
            };
        }
        
        $startTime = microtime(true);
        $results = [];
        
        // Executa operações sequencialmente (PHP não tem threading nativo)
        foreach ($operations as $index => $operation) {
            try {
                $opStartTime = microtime(true);
                $result = $operation();
                $opEndTime = microtime(true);
                
                $results[] = [
                    'index' => $index,
                    'time' => $opEndTime - $opStartTime,
                    'success' => true,
                ];
            } catch (Exception $e) {
                $results[] = [
                    'index' => $index,
                    'time' => 0,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $avgTime = array_sum(array_column($results, 'time')) / count($results);
        
        $this->metrics['concurrent_operations'] = [
            'total_time' => $totalTime,
            'avg_time' => $avgTime,
            'success_count' => $successCount,
            'total_operations' => count($operations),
            'success_rate' => ($successCount / count($operations)) * 100,
        ];
        
        echo "     📊 Tempo total: " . number_format($totalTime * 1000, 2) . "ms\n";
        echo "     📊 Tempo médio por operação: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "     📊 Operações bem-sucedidas: {$successCount}/" . count($operations) . "\n";
        echo "     📊 Taxa de sucesso: " . number_format(($successCount / count($operations)) * 100, 1) . "%\n";
    }
    
    /**
     * Testa uso de memória
     */
    private function testMemoryUsage(): void
    {
        $this->client->authenticate($_ENV['XGATE_EMAIL'], $_ENV['XGATE_PASSWORD']);
        
        $initialMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        echo "   📊 Memória inicial: " . $this->formatBytes($initialMemory) . "\n";
        echo "   📊 Pico de memória: " . $this->formatBytes($peakMemory) . "\n";
        
        // Cria múltiplos clientes para testar uso de memória
        $clientCount = 10;
        $customers = [];
        
        for ($i = 0; $i < $clientCount; $i++) {
            try {
                $customerData = UnitTestHelper::generateCustomerData();
                $customer = $this->customerResource->create($customerData);
                $customers[] = $customer;
                $this->createdCustomers[] = $customer->getId();
            } catch (Exception $e) {
                echo "     ⚠️  Erro ao criar cliente para teste de memória: " . $e->getMessage() . "\n";
            }
        }
        
        $memoryAfterCreation = memory_get_usage(true);
        $peakMemoryAfter = memory_get_peak_usage(true);
        
        $memoryIncrease = $memoryAfterCreation - $initialMemory;
        $peakIncrease = $peakMemoryAfter - $peakMemory;
        
        $this->metrics['memory_usage'] = [
            'initial_memory' => $initialMemory,
            'memory_after_operations' => $memoryAfterCreation,
            'memory_increase' => $memoryIncrease,
            'peak_memory_initial' => $peakMemory,
            'peak_memory_after' => $peakMemoryAfter,
            'peak_increase' => $peakIncrease,
            'objects_created' => count($customers),
        ];
        
        echo "   📊 Memória após operações: " . $this->formatBytes($memoryAfterCreation) . "\n";
        echo "   📊 Aumento de memória: " . $this->formatBytes($memoryIncrease) . "\n";
        echo "   📊 Novo pico de memória: " . $this->formatBytes($peakMemoryAfter) . "\n";
        echo "   📊 Aumento do pico: " . $this->formatBytes($peakIncrease) . "\n";
        echo "   📊 Objetos criados: " . count($customers) . "\n";
        
        // Libera memória
        unset($customers);
        gc_collect_cycles();
        
        $memoryAfterCleanup = memory_get_usage(true);
        echo "   📊 Memória após limpeza: " . $this->formatBytes($memoryAfterCleanup) . "\n";
    }
    
    /**
     * Teste de carga
     */
    private function testLoadTest(): void
    {
        $this->client->authenticate($_ENV['XGATE_EMAIL'], $_ENV['XGATE_PASSWORD']);
        
        echo "   🔄 Executando teste de carga\n";
        
        $duration = 30; // 30 segundos
        $startTime = microtime(true);
        $endTime = $startTime + $duration;
        
        $operationCount = 0;
        $errorCount = 0;
        $times = [];
        
        while (microtime(true) < $endTime) {
            try {
                $opStartTime = microtime(true);
                $this->customerResource->list();
                $opEndTime = microtime(true);
                
                $times[] = $opEndTime - $opStartTime;
                $operationCount++;
                
                // Pequena pausa para simular uso real
                usleep(100000); // 100ms
            } catch (Exception $e) {
                $errorCount++;
                echo "     ⚠️  Erro durante teste de carga: " . $e->getMessage() . "\n";
                
                // Pausa maior em caso de erro
                sleep(1);
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        $avgTime = !empty($times) ? array_sum($times) / count($times) : 0;
        $throughput = $operationCount / $totalTime;
        $errorRate = ($errorCount / ($operationCount + $errorCount)) * 100;
        
        $this->metrics['load_test'] = [
            'duration' => $totalTime,
            'operation_count' => $operationCount,
            'error_count' => $errorCount,
            'avg_time' => $avgTime,
            'throughput' => $throughput,
            'error_rate' => $errorRate,
        ];
        
        echo "     📊 Duração: " . number_format($totalTime, 2) . " segundos\n";
        echo "     📊 Operações executadas: {$operationCount}\n";
        echo "     📊 Erros: {$errorCount}\n";
        echo "     📊 Tempo médio por operação: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "     📊 Throughput: " . number_format($throughput, 2) . " operações/segundo\n";
        echo "     📊 Taxa de erro: " . number_format($errorRate, 2) . "%\n";
    }
    
    /**
     * Imprime relatório de performance
     */
    private function printPerformanceReport(): void
    {
        echo "📊 RELATÓRIO DE PERFORMANCE\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Resumo geral
        $this->printAuthenticationSummary();
        $this->printOperationsSummary();
        $this->printBatchSummary();
        $this->printConcurrentSummary();
        $this->printMemorySummary();
        $this->printLoadTestSummary();
        
        // Recomendações
        $this->printRecommendations();
    }
    
    private function printAuthenticationSummary(): void
    {
        if (!isset($this->metrics['authentication'])) return;
        
        $auth = $this->metrics['authentication'];
        echo "🔐 AUTENTICAÇÃO\n";
        echo "   Tempo médio: " . number_format($auth['avg_time'] * 1000, 2) . "ms\n";
        echo "   Tempo mínimo: " . number_format($auth['min_time'] * 1000, 2) . "ms\n";
        echo "   Tempo máximo: " . number_format($auth['max_time'] * 1000, 2) . "ms\n";
        echo "   Iterações: " . $auth['iterations'] . "\n\n";
    }
    
    private function printOperationsSummary(): void
    {
        if (!isset($this->metrics['individual_operations'])) return;
        
        echo "⚡ OPERAÇÕES INDIVIDUAIS\n";
        foreach ($this->metrics['individual_operations'] as $operation => $data) {
            echo "   " . str_replace('_', ' ', ucfirst($operation)) . ": " . 
                 number_format($data['avg_time'] * 1000, 2) . "ms\n";
        }
        echo "\n";
    }
    
    private function printBatchSummary(): void
    {
        if (!isset($this->metrics['batch_operations'])) return;
        
        echo "📦 OPERAÇÕES EM LOTE\n";
        foreach ($this->metrics['batch_operations'] as $batchSize => $data) {
            echo "   Lote de {$batchSize} itens:\n";
            echo "     Tempo total: " . number_format($data['total_time'] * 1000, 2) . "ms\n";
            echo "     Tempo por item: " . number_format($data['avg_time_per_item'] * 1000, 2) . "ms\n";
            echo "     Throughput: " . number_format($data['throughput'], 2) . " ops/seg\n";
            echo "     Taxa de sucesso: " . number_format($data['success_rate'], 1) . "%\n";
        }
        echo "\n";
    }
    
    private function printConcurrentSummary(): void
    {
        if (!isset($this->metrics['concurrent_operations'])) return;
        
        $concurrent = $this->metrics['concurrent_operations'];
        echo "🔄 OPERAÇÕES CONCORRENTES\n";
        echo "   Tempo total: " . number_format($concurrent['total_time'] * 1000, 2) . "ms\n";
        echo "   Tempo médio: " . number_format($concurrent['avg_time'] * 1000, 2) . "ms\n";
        echo "   Sucessos: " . $concurrent['success_count'] . "/" . $concurrent['total_operations'] . "\n";
        echo "   Taxa de sucesso: " . number_format($concurrent['success_rate'], 1) . "%\n\n";
    }
    
    private function printMemorySummary(): void
    {
        if (!isset($this->metrics['memory_usage'])) return;
        
        $memory = $this->metrics['memory_usage'];
        echo "💾 USO DE MEMÓRIA\n";
        echo "   Memória inicial: " . $this->formatBytes($memory['initial_memory']) . "\n";
        echo "   Memória após operações: " . $this->formatBytes($memory['memory_after_operations']) . "\n";
        echo "   Aumento de memória: " . $this->formatBytes($memory['memory_increase']) . "\n";
        echo "   Pico de memória: " . $this->formatBytes($memory['peak_memory_after']) . "\n";
        echo "   Objetos criados: " . $memory['objects_created'] . "\n\n";
    }
    
    private function printLoadTestSummary(): void
    {
        if (!isset($this->metrics['load_test'])) return;
        
        $load = $this->metrics['load_test'];
        echo "🚀 TESTE DE CARGA\n";
        echo "   Duração: " . number_format($load['duration'], 2) . " segundos\n";
        echo "   Operações: " . $load['operation_count'] . "\n";
        echo "   Erros: " . $load['error_count'] . "\n";
        echo "   Throughput: " . number_format($load['throughput'], 2) . " ops/seg\n";
        echo "   Taxa de erro: " . number_format($load['error_rate'], 2) . "%\n\n";
    }
    
    private function printRecommendations(): void
    {
        echo "💡 RECOMENDAÇÕES\n";
        echo str_repeat("-", 40) . "\n";
        
        // Análise de autenticação
        if (isset($this->metrics['authentication'])) {
            $authTime = $this->metrics['authentication']['avg_time'];
            if ($authTime > 2.0) {
                echo "⚠️  Tempo de autenticação alto (" . number_format($authTime * 1000, 2) . "ms)\n";
                echo "   Considere implementar cache de tokens\n";
            } else {
                echo "✅ Tempo de autenticação aceitável\n";
            }
        }
        
        // Análise de operações
        if (isset($this->metrics['individual_operations'])) {
            $slowOperations = [];
            foreach ($this->metrics['individual_operations'] as $op => $data) {
                if ($data['avg_time'] > 1.0) {
                    $slowOperations[] = $op;
                }
            }
            
            if (!empty($slowOperations)) {
                echo "⚠️  Operações lentas detectadas: " . implode(', ', $slowOperations) . "\n";
                echo "   Considere otimizar consultas ou implementar cache\n";
            } else {
                echo "✅ Todas as operações com tempo aceitável\n";
            }
        }
        
        // Análise de memória
        if (isset($this->metrics['memory_usage'])) {
            $memoryIncrease = $this->metrics['memory_usage']['memory_increase'];
            if ($memoryIncrease > 10 * 1024 * 1024) { // 10MB
                echo "⚠️  Alto uso de memória detectado (" . $this->formatBytes($memoryIncrease) . ")\n";
                echo "   Considere implementar pooling de objetos\n";
            } else {
                echo "✅ Uso de memória aceitável\n";
            }
        }
        
        // Análise de carga
        if (isset($this->metrics['load_test'])) {
            $errorRate = $this->metrics['load_test']['error_rate'];
            if ($errorRate > 5.0) {
                echo "⚠️  Alta taxa de erro no teste de carga (" . number_format($errorRate, 2) . "%)\n";
                echo "   Verifique rate limiting e timeout\n";
            } else {
                echo "✅ Taxa de erro aceitável no teste de carga\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Formata bytes para leitura humana
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Limpeza de recursos
     */
    private function cleanup(): void
    {
        echo "🧹 Limpando recursos de teste...\n";
        
        $cleaned = 0;
        foreach ($this->createdCustomers as $customerId) {
            try {
                $this->customerResource->delete($customerId);
                $cleaned++;
            } catch (Exception $e) {
                // Ignora erros de limpeza
            }
        }
        
        echo "✅ {$cleaned} recursos limpos\n";
        echo "🏁 Teste de performance concluído!\n\n";
    }
}

// Executa os testes se o arquivo for executado diretamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $tester = new PerformanceTester();
        $tester->runAllTests();
         } catch (Exception $e) {
         echo "❌ Erro ao executar testes de performance: " . $e->getMessage() . "\n";
         exit(1);
     }
 } 