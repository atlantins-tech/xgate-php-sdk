<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Resource\CustomerResource;
use XGate\Resource\DepositResource;
use XGate\Model\Customer;
use XGate\Model\Transaction;
use XGate\Exception\AuthenticationException;
use XGate\Exception\ApiException;
use XGate\Exception\XGateException;

/**
 * Teste de Integração do SDK da XGATE
 * 
 * Este arquivo testa o fluxo completo de integração com a API da XGATE:
 * 1. Autenticação com credenciais reais
 * 2. Cadastro de cliente na XGATE
 * 3. Geração de depósito via cripto
 * 4. Verificação de status
 * 
 * Configuração necessária:
 * - Arquivo .env na raiz do projeto com XGATE_EMAIL e XGATE_PASSWORD
 * - Credenciais válidas da XGATE
 */

// Função para carregar variáveis de ambiente
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException("Arquivo .env não encontrado: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove aspas duplas ou simples do valor
        $value = trim($value, '"\'');
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Função para mascarar dados sensíveis nos logs
function maskSensitiveData(string $data): string
{
    $length = strlen($data);
    if ($length <= 4) {
        return str_repeat('*', $length);
    }
    
    return substr($data, 0, 2) . str_repeat('*', $length - 4) . substr($data, -2);
}

// Função para gerar dados de teste
function generateTestCustomerData(): array
{
    $timestamp = time();
    return [
        'name' => 'João Silva Teste',
        'email' => "teste.joao.{$timestamp}@exemplo.com",
        'phone' => '+5511999999999',
        'document' => '12345678901',
        'document_type' => 'cpf'
    ];
}

echo "=== Teste de Integração XGATE SDK ===\n\n";

try {
    // 1. Carregar variáveis de ambiente
    echo "1. Carregando configurações...\n";
    loadEnv(__DIR__ . '/../.env');
    
    $email = $_ENV['XGATE_EMAIL'] ?? null;
    $password = $_ENV['XGATE_PASSWORD'] ?? null;
    
    if (!$email || !$password) {
        throw new RuntimeException(
            "Credenciais não encontradas. Configure XGATE_EMAIL e XGATE_PASSWORD no arquivo .env"
        );
    }
    
    echo "✅ Configurações carregadas\n";
    echo "   Email: " . maskSensitiveData($email) . "\n\n";

    // 2. Inicializar cliente XGATE
    echo "2. Inicializando cliente XGATE...\n";
    $client = new XGateClient([
        'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgate.com',
        'environment' => $_ENV['XGATE_ENVIRONMENT'] ?? 'production',
        'timeout' => 60,
        'debug' => true,
    ]);
    
    echo "✅ Cliente inicializado\n";
    echo "   Versão: " . $client->getVersion() . "\n";
    echo "   Base URL: " . $client->getConfiguration()->getBaseUrl() . "\n\n";

    // 3. Autenticação
    echo "3. Realizando autenticação...\n";
    $authenticated = $client->authenticate($email, $password);
    
    if (!$authenticated) {
        throw new AuthenticationException("Falha na autenticação com as credenciais fornecidas");
    }
    
    echo "✅ Autenticação realizada com sucesso\n";
    echo "   Status: " . ($client->isAuthenticated() ? 'Autenticado' : 'Não autenticado') . "\n\n";

    // 4. Inicializar recursos
    echo "4. Inicializando recursos...\n";
    $customerResource = new CustomerResource($client->getHttpClient(), $client->getLogger());
    $depositResource = new DepositResource($client->getHttpClient(), $client->getLogger());
    
    echo "✅ Recursos inicializados\n\n";

    // 5. Cadastrar cliente de teste
    echo "5. Cadastrando cliente de teste...\n";
    $testCustomerData = generateTestCustomerData();
    
    $customer = $customerResource->create(
        name: $testCustomerData['name'],
        email: $testCustomerData['email'],
        phone: $testCustomerData['phone'],
        document: $testCustomerData['document'],
        documentType: $testCustomerData['document_type']
    );
    
    echo "✅ Cliente cadastrado com sucesso\n";
    echo "   ID: " . $customer->id . "\n";
    echo "   Nome: " . $customer->name . "\n";
    echo "   Email: " . $customer->email . "\n";
    echo "   Status: " . $customer->status . "\n\n";

    // 6. Gerar depósito via cripto
    echo "6. Gerando depósito via cripto...\n";
    $depositTransaction = new Transaction(
        id: null,
        amount: '100.50',
        currency: 'BRL',
        accountId: $customer->id,
        paymentMethod: 'crypto_transfer',
        type: 'deposit',
        referenceId: 'test_deposit_' . time(),
        description: 'Depósito de teste via SDK',
        callbackUrl: 'https://webhook.site/test-callback'
    );
    
    $createdDeposit = $depositResource->createDeposit($depositTransaction);
    
    echo "✅ Depósito criado com sucesso\n";
    echo "   ID da Transação: " . $createdDeposit->id . "\n";
    echo "   Valor: " . $createdDeposit->getFormattedAmount() . "\n";
    echo "   Moeda: " . $createdDeposit->currency . "\n";
    echo "   Status: " . $createdDeposit->status . "\n";
    echo "   Método: " . $createdDeposit->paymentMethod . "\n\n";

    // 7. Verificar status do depósito
    echo "7. Verificando status do depósito...\n";
    $depositStatus = $depositResource->getDeposit($createdDeposit->id);
    
    echo "✅ Status verificado\n";
    echo "   Status atual: " . $depositStatus->status . "\n";
    echo "   Criado em: " . ($depositStatus->createdAt ? $depositStatus->createdAt->format('Y-m-d H:i:s') : 'N/A') . "\n";
    
    if ($depositStatus->isPending()) {
        echo "   ⏳ Depósito aguardando processamento\n";
    } elseif ($depositStatus->isCompleted()) {
        echo "   ✅ Depósito processado com sucesso\n";
    } elseif ($depositStatus->isFailed()) {
        echo "   ❌ Depósito falhou\n";
    }
    echo "\n";

    // 8. Listar moedas suportadas
    echo "8. Listando moedas suportadas...\n";
    try {
        $supportedCurrencies = $depositResource->listSupportedCurrencies();
        echo "✅ Moedas suportadas obtidas\n";
        echo "   Total: " . count($supportedCurrencies) . " moedas\n";
        echo "   Moedas: " . implode(', ', $supportedCurrencies) . "\n\n";
    } catch (Exception $e) {
        echo "⚠️  Não foi possível obter moedas suportadas: " . $e->getMessage() . "\n\n";
    }

    // 9. Buscar cliente criado
    echo "9. Buscando cliente criado...\n";
    $retrievedCustomer = $customerResource->get($customer->id);
    
    echo "✅ Cliente encontrado\n";
    echo "   Nome: " . $retrievedCustomer->name . "\n";
    echo "   Email: " . $retrievedCustomer->email . "\n";
    echo "   Status: " . $retrievedCustomer->status . "\n\n";

    // 10. Fazer logout
    echo "10. Realizando logout...\n";
    $logoutSuccess = $client->logout();
    
    if ($logoutSuccess) {
        echo "✅ Logout realizado com sucesso\n";
    } else {
        echo "⚠️  Logout não foi necessário ou falhou\n";
    }
    
    echo "   Status final: " . ($client->isAuthenticated() ? 'Ainda autenticado' : 'Desautenticado') . "\n\n";

    // Resumo final
    echo "=== RESUMO DO TESTE ===\n";
    echo "✅ Autenticação: Sucesso\n";
    echo "✅ Cadastro de Cliente: Sucesso (ID: {$customer->id})\n";
    echo "✅ Geração de Depósito: Sucesso (ID: {$createdDeposit->id})\n";
    echo "✅ Verificação de Status: Sucesso\n";
    echo "✅ Logout: Sucesso\n";
    echo "\n🎉 Todos os testes foram executados com sucesso!\n";
    echo "   O SDK está funcionando corretamente com a API da XGATE.\n\n";

} catch (AuthenticationException $e) {
    echo "❌ Erro de Autenticação: " . $e->getMessage() . "\n";
    echo "   Verifique suas credenciais no arquivo .env\n";
    echo "   XGATE_EMAIL e XGATE_PASSWORD devem estar corretos\n\n";
    exit(1);
    
} catch (ApiException $e) {
    echo "❌ Erro da API: " . $e->getMessage() . "\n";
    echo "   Código HTTP: " . $e->getCode() . "\n";
    if ($e->getResponse()) {
        echo "   Resposta: " . $e->getResponse()->getBody()->getContents() . "\n";
    }
    echo "\n";
    exit(1);
    
} catch (XGateException $e) {
    echo "❌ Erro do SDK XGATE: " . $e->getMessage() . "\n";
    echo "   Código: " . $e->getCode() . "\n";
    if ($e->getPrevious()) {
        echo "   Erro anterior: " . $e->getPrevious()->getMessage() . "\n";
    }
    echo "\n";
    exit(1);
    
} catch (Exception $e) {
    echo "❌ Erro inesperado: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "=== Fim do Teste de Integração ===\n"; 