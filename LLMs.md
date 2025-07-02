# XGATE PHP SDK - Guia de Integração para Agentes de IA

Este documento fornece informações abrangentes para agentes de IA (LLMs) trabalhando com o XGATE PHP SDK. Inclui dados estruturados, padrões de uso e diretrizes de integração otimizadas para consumo por IA.

## 📋 Metadados do Documento

```xml
<document_info>
  <title>XGATE PHP SDK - Guia de Integração para Agentes de IA</title>
  <version>1.0.0</version>
  <last_updated>2024-12-19</last_updated>
  <target_audience>Agentes de IA, LLMs, Assistentes de Código</target_audience>
  <sdk_version>^1.0.0</sdk_version>
  <php_version>^8.1</php_version>
  <format>structured_markdown</format>
</document_info>
```

## 🏗️ Visão Geral da Arquitetura do SDK

```xml
<architecture>
  <core_components>
    <component name="XGateClient" type="main_client" path="src/XGateClient.php">
      <description>Ponto de entrada principal do SDK e facade do cliente</description>
      <responsibilities>
        <item>Gerenciamento de autenticação</item>
        <item>Manipulação de configuração</item>
        <item>Factory de recursos</item>
        <item>Coordenação do cliente HTTP</item>
      </responsibilities>
    </component>
    
    <component name="HttpClient" type="transport" path="src/Http/HttpClient.php">
      <description>Camada de comunicação HTTP com lógica de retry</description>
      <features>
        <item>Manipulação de request/response</item>
        <item>Injeção de autenticação</item>
        <item>Tratamento de erros e retries</item>
        <item>Conformidade com rate limiting</item>
      </features>
    </component>
    
    <component name="AuthenticationManager" type="security" path="src/Authentication/AuthenticationManager.php">
      <description>Gerencia autenticação da API e tokens</description>
      <methods>
        <item>authenticate()</item>
        <item>getAuthHeaders()</item>
        <item>refreshToken()</item>
        <item>isAuthenticated()</item>
      </methods>
    </component>
    
    <component name="ConfigurationManager" type="config" path="src/Configuration/ConfigurationManager.php">
      <description>Gerencia configuração do SDK e configurações de ambiente</description>
      <configuration_keys>
        <item>api_key</item>
        <item>api_secret</item>
        <item>environment</item>
        <item>timeout</item>
        <item>retries</item>
        <item>debug</item>
        <item>base_url</item>
      </configuration_keys>
    </component>
  </core_components>
  
  <resources>
    <resource name="CustomerResource" path="src/Resource/CustomerResource.php">
      <operations>create, get, list, update, delete</operations>
      <primary_model>Customer</primary_model>
    </resource>
    <resource name="PixResource" path="src/Resource/PixResource.php">
      <operations>createPayment, getPayment, listPayments, cancelPayment</operations>
      <primary_model>Transaction</primary_model>
    </resource>
    <resource name="DepositResource" path="src/Resource/DepositResource.php">
      <operations>create, get, list, process</operations>
      <primary_model>Transaction</primary_model>
    </resource>
    <resource name="WithdrawResource" path="src/Resource/WithdrawResource.php">
      <operations>create, get, list, process, cancel</operations>
      <primary_model>Transaction</primary_model>
    </resource>
  </resources>
  
  <models>
    <model name="Customer" path="src/Model/Customer.php">
      <properties>id, name, email, document, phone, status, created_at, updated_at</properties>
      <validation>formato de email, formato de documento, campos obrigatórios</validation>
    </model>
    <model name="Transaction" path="src/Model/Transaction.php">
      <properties>id, type, amount, status, description, customer_id, created_at, updated_at</properties>
      <types>pix_payment, deposit, withdraw</types>
    </model>
    <model name="PixKey" path="src/Model/PixKey.php">
      <properties>key, type, owner_name, owner_document</properties>
      <types>email, phone, document, random</types>
    </model>
  </models>
  
  <exceptions>
    <exception name="XGateException" type="base" path="src/Exception/XGateException.php">
      <description>Classe base de exceção para todas as exceções do SDK</description>
    </exception>
    <exception name="ApiException" type="api_error" path="src/Exception/ApiException.php">
      <description>Erros relacionados à API (respostas 4xx, 5xx)</description>
      <properties>status_code, error_code, error_message, response_data</properties>
    </exception>
    <exception name="AuthenticationException" type="auth_error" path="src/Exception/AuthenticationException.php">
      <description>Falhas de autenticação e autorização</description>
    </exception>
    <exception name="ValidationException" type="validation_error" path="src/Exception/ValidationException.php">
      <description>Falhas de validação de entrada</description>
      <properties>validation_errors, field_errors</properties>
    </exception>
    <exception name="NetworkException" type="network_error" path="src/Exception/NetworkException.php">
      <description>Problemas de conectividade de rede e timeout</description>
    </exception>
    <exception name="RateLimitException" type="rate_limit" path="src/Exception/RateLimitException.php">
      <description>Erros de rate limiting e quota excedida</description>
      <properties>retry_after, limit, remaining</properties>
    </exception>
  </exceptions>
</architecture>
```

## 🚀 Início Rápido para Agentes de IA

```xml
<quick_start>
  <installation>
    <command>composer require xgate/php-sdk</command>
    <requirements>
      <php_version>^8.1</php_version>
      <extensions>curl, json, mbstring</extensions>
    </requirements>
  </installation>
  
  <basic_setup>
    <code_example language="php">
      <![CDATA[
<?php
require_once 'vendor/autoload.php';

use XGate\Client\XGateClient;

// Configuração básica
$client = new XGateClient([
    'api_key' => 'sua-api-key',
    'api_secret' => 'seu-api-secret',
    'environment' => 'sandbox', // ou 'production'
    'timeout' => 30,
    'debug' => true
]);

// Testar conexão
try {
    $customers = $client->customers()->list();
    echo "Conexão bem-sucedida!\n";
} catch (Exception $e) {
    echo "Falha na conexão: " . $e->getMessage() . "\n";
}
      ]]>
    </code_example>
  </basic_setup>
</quick_start>
```

## 🔧 Padrões de Configuração

```xml
<configuration_patterns>
  <pattern name="environment_based" priority="recommended">
    <description>Carregar configuração de variáveis de ambiente</description>
    <code_example language="php">
      <![CDATA[
$client = new XGateClient([
    'api_key' => $_ENV['XGATE_API_KEY'],
    'api_secret' => $_ENV['XGATE_API_SECRET'],
    'environment' => $_ENV['XGATE_ENV'] ?? 'sandbox',
    'timeout' => (int)($_ENV['XGATE_TIMEOUT'] ?? 30),
    'debug' => filter_var($_ENV['XGATE_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)
]);
      ]]>
    </code_example>
    <environment_variables>
      <variable name="XGATE_API_KEY" required="true" description="Chave de autenticação da API"/>
      <variable name="XGATE_API_SECRET" required="true" description="Segredo de autenticação da API"/>
      <variable name="XGATE_ENV" required="false" default="sandbox" description="Ambiente (sandbox|production)"/>
      <variable name="XGATE_TIMEOUT" required="false" default="30" description="Timeout da requisição em segundos"/>
      <variable name="XGATE_DEBUG" required="false" default="false" description="Habilitar log de debug"/>
    </environment_variables>
  </pattern>
  
  <pattern name="config_file" priority="alternative">
    <description>Carregar configuração de arquivo JSON/PHP</description>
    <code_example language="php">
      <![CDATA[
// config/xgate.php
return [
    'api_key' => env('XGATE_API_KEY'),
    'api_secret' => env('XGATE_API_SECRET'),
    'environment' => env('XGATE_ENV', 'sandbox'),
    'timeout' => 30,
    'retries' => 3,
    'debug' => env('APP_DEBUG', false)
];

// Uso
$config = require 'config/xgate.php';
$client = new XGateClient($config);
      ]]>
    </code_example>
  </pattern>
  
  <pattern name="dependency_injection" priority="advanced">
    <description>Integração com containers DI (Laravel, Symfony)</description>
    <code_example language="php">
      <![CDATA[
// Exemplo de Service Provider do Laravel
public function register()
{
    $this->app->singleton(XGateClient::class, function ($app) {
        return new XGateClient([
            'api_key' => config('services.xgate.api_key'),
            'api_secret' => config('services.xgate.api_secret'),
            'environment' => config('services.xgate.environment'),
        ]);
    });
}
      ]]>
    </code_example>
  </pattern>
</configuration_patterns>
```

## 📊 Referência de Operações da API

```xml
<api_operations>
  <resource name="customers" class="CustomerResource">
    <operation name="create" method="POST" endpoint="/customers">
      <parameters>
        <parameter name="name" type="string" required="true" description="Nome completo do cliente"/>
        <parameter name="email" type="string" required="true" description="Endereço de email do cliente"/>
        <parameter name="document" type="string" required="false" description="Documento do cliente (CPF/CNPJ)"/>
        <parameter name="phone" type="string" required="false" description="Número de telefone do cliente"/>
      </parameters>
      <returns type="Customer" description="Objeto cliente criado"/>
      <exceptions>
        <exception type="ValidationException" condition="Dados de entrada inválidos"/>
        <exception type="ApiException" condition="Resposta de erro da API"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$customer = $client->customers()->create([
    'name' => 'João Silva',
    'email' => 'joao@exemplo.com',
    'document' => '12345678901',
    'phone' => '+5511999999999'
]);
        ]]>
      </code_example>
    </operation>
    
    <operation name="get" method="GET" endpoint="/customers/{id}">
      <parameters>
        <parameter name="id" type="string" required="true" description="ID do cliente"/>
      </parameters>
      <returns type="Customer" description="Objeto cliente"/>
      <exceptions>
        <exception type="ApiException" condition="Cliente não encontrado (404)"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$customer = $client->customers()->get('customer-123');
echo $customer->getName(); // "João Silva"
        ]]>
      </code_example>
    </operation>
    
    <operation name="list" method="GET" endpoint="/customers">
      <parameters>
        <parameter name="filters" type="array" required="false" description="Critérios de filtro"/>
        <parameter name="page" type="int" required="false" default="1" description="Número da página"/>
        <parameter name="limit" type="int" required="false" default="25" description="Itens por página"/>
      </parameters>
      <returns type="array" description="Lista paginada de clientes"/>
      <code_example language="php">
        <![CDATA[
$result = $client->customers()->list([
    'status' => 'active',
    'created_after' => '2024-01-01'
], 1, 50);

foreach ($result['data'] as $customer) {
    echo $customer->getName() . "\n";
}
        ]]>
      </code_example>
    </operation>
    
    <operation name="update" method="PUT" endpoint="/customers/{id}">
      <parameters>
        <parameter name="id" type="string" required="true" description="ID do cliente"/>
        <parameter name="data" type="array" required="true" description="Dados de atualização"/>
      </parameters>
      <returns type="Customer" description="Objeto cliente atualizado"/>
      <code_example language="php">
        <![CDATA[
$customer = $client->customers()->update('customer-123', [
    'name' => 'João Silva',
    'phone' => '+5511888888888'
]);
        ]]>
      </code_example>
    </operation>
    
    <operation name="delete" method="DELETE" endpoint="/customers/{id}">
      <parameters>
        <parameter name="id" type="string" required="true" description="ID do cliente"/>
      </parameters>
      <returns type="bool" description="Status de sucesso"/>
      <code_example language="php">
        <![CDATA[
$success = $client->customers()->delete('customer-123');
        ]]>
      </code_example>
    </operation>
  </resource>
  
  <resource name="pix" class="PixResource">
    <operation name="register" method="POST" endpoint="/pix/keys">
      <parameters>
        <parameter name="type" type="string" required="true" description="Tipo da chave PIX (cpf, cnpj, email, phone, random)"/>
        <parameter name="key" type="string" required="true" description="Valor da chave PIX"/>
        <parameter name="accountHolderName" type="string" required="false" description="Nome do portador da conta"/>
        <parameter name="accountHolderDocument" type="string" required="false" description="Documento do portador da conta"/>
        <parameter name="bankCode" type="string" required="false" description="Código do banco (ISPB)"/>
        <parameter name="accountNumber" type="string" required="false" description="Número da conta"/>
        <parameter name="accountType" type="string" required="false" description="Tipo da conta (checking, savings)"/>
        <parameter name="metadata" type="array" required="false" description="Metadados adicionais da chave PIX"/>
      </parameters>
      <returns type="PixKey" description="Chave PIX registrada"/>
      <exceptions>
        <exception type="ValidationException" condition="Dados de entrada inválidos"/>
        <exception type="ApiException" condition="Erro ao registrar chave PIX"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$pixKey = $client->pix()->register(
    type: 'email',
    key: 'user@example.com',
    accountHolderName: 'João Silva',
    accountHolderDocument: '12345678901',
    bankCode: '001'
);
        ]]>
      </code_example>
    </operation>
    
    <operation name="get" method="GET" endpoint="/pix/keys/{id}">
      <parameters>
        <parameter name="pixKeyId" type="string" required="true" description="ID da chave PIX"/>
      </parameters>
      <returns type="PixKey" description="Chave PIX"/>
      <exceptions>
        <exception type="ApiException" condition="Chave PIX não encontrada (404)"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$pixKey = $client->pix()->get('pix-key-123');
echo $pixKey->type; // "email"
        ]]>
      </code_example>
    </operation>
    
    <operation name="update" method="PUT" endpoint="/pix/keys/{id}">
      <parameters>
        <parameter name="pixKeyId" type="string" required="true" description="ID da chave PIX"/>
        <parameter name="updateData" type="array" required="true" description="Dados de atualização"/>
      </parameters>
      <returns type="PixKey" description="Chave PIX atualizada"/>
      <code_example language="php">
        <![CDATA[
$pixKey = $client->pix()->update('pix-key-123', [
    'account_holder_name' => 'João da Silva',
    'metadata' => ['updated_reason' => 'name_change']
]);
        ]]>
      </code_example>
    </operation>
    
    <operation name="delete" method="DELETE" endpoint="/pix/keys/{id}">
      <parameters>
        <parameter name="pixKeyId" type="string" required="true" description="ID da chave PIX"/>
      </parameters>
      <returns type="bool" description="Status de sucesso"/>
      <code_example language="php">
        <![CDATA[
$success = $client->pix()->delete('pix-key-123');
        ]]>
      </code_example>
    </operation>
    
    <operation name="list" method="GET" endpoint="/pix/keys">
      <parameters>
        <parameter name="page" type="int" required="false" default="1" description="Número da página"/>
        <parameter name="limit" type="int" required="false" default="20" description="Itens por página"/>
        <parameter name="filters" type="array" required="false" description="Filtros opcionais (type, status, etc.)"/>
      </parameters>
      <returns type="array" description="Lista de chaves PIX"/>
      <code_example language="php">
        <![CDATA[
$pixKeys = $client->pix()->list(1, 10, [
    'type' => 'email',
    'status' => 'active'
]);
        ]]>
      </code_example>
    </operation>
    
    <operation name="search" method="GET" endpoint="/pix/keys/search">
      <parameters>
        <parameter name="query" type="string" required="true" description="Consulta de busca"/>
        <parameter name="limit" type="int" required="false" default="10" description="Máximo de resultados"/>
      </parameters>
      <returns type="array" description="Lista de chaves PIX correspondentes"/>
      <code_example language="php">
        <![CDATA[
$pixKeys = $client->pix()->search('example.com', 5);
        ]]>
      </code_example>
    </operation>
    
    <operation name="findByKey" method="GET" endpoint="/pix/keys/find">
      <parameters>
        <parameter name="type" type="string" required="true" description="Tipo da chave PIX"/>
        <parameter name="key" type="string" required="true" description="Valor da chave PIX"/>
      </parameters>
      <returns type="PixKey|null" description="Chave PIX se encontrada, null caso contrário"/>
      <code_example language="php">
        <![CDATA[
$pixKey = $client->pix()->findByKey('email', 'user@example.com');
if ($pixKey !== null) {
    echo "PIX key found: " . $pixKey->id;
}
        ]]>
      </code_example>
    </operation>
  </resource>
  
  <resource name="deposits" class="DepositResource">
    <operation name="listSupportedCurrencies" method="GET" endpoint="/deposits/currencies">
      <parameters>
        <!-- No parameters required -->
      </parameters>
      <returns type="array" description="Array of supported currency codes (ISO 4217)"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$currencies = $client->deposits()->listSupportedCurrencies();
// Returns: ['BRL', 'USD', 'EUR', 'GBP']
foreach ($currencies as $currency) {
    echo "Supported currency: {$currency}\n";
}
        ]]>
      </code_example>
    </operation>
    
    <operation name="createDeposit" method="POST" endpoint="/deposits">
      <parameters>
        <parameter name="transaction" type="Transaction" required="true" description="Transaction data for the deposit"/>
      </parameters>
      <returns type="Transaction" description="The created transaction with server-assigned ID and timestamps"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response (validation, etc.)"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$transaction = new Transaction(
    id: null,
    amount: '250.00',
    currency: 'BRL',
    accountId: 'acc_456',
    paymentMethod: 'bank_transfer',
    type: 'deposit',
    referenceId: 'invoice_789',
    description: 'Payment for services'
);
$result = $client->deposits()->createDeposit($transaction);
        ]]>
      </code_example>
    </operation>
    
    <operation name="getDeposit" method="GET" endpoint="/deposits/{id}">
      <parameters>
        <parameter name="depositId" type="string" required="true" description="Unique identifier of the deposit transaction"/>
      </parameters>
      <returns type="Transaction" description="The deposit transaction with current status"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response or deposit not found"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$deposit = $client->deposits()->getDeposit('txn_12345');
if ($deposit->isPending()) {
    echo "Deposit is still being processed";
} elseif ($deposit->isCompleted()) {
    echo "Deposit completed successfully!";
}
        ]]>
      </code_example>
    </operation>
    
    <operation name="listDeposits" method="GET" endpoint="/deposits">
      <parameters>
        <parameter name="page" type="int" required="false" default="1" description="Page number (1-based)"/>
        <parameter name="limit" type="int" required="false" default="20" description="Number of items per page (1-100)"/>
        <parameter name="filters" type="array" required="false" description="Optional filters (status, currency, from_date, to_date, account_id)"/>
      </parameters>
      <returns type="array" description="Paginated list of deposit transactions with pagination info"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$filters = [
    'status' => 'completed',
    'currency' => 'BRL',
    'from_date' => '2023-06-01T00:00:00Z'
];
$result = $client->deposits()->listDeposits(1, 20, $filters);
echo "Found {$result['pagination']['total']} deposits\n";
        ]]>
      </code_example>
    </operation>
    
    <operation name="searchDeposits" method="GET" endpoint="/deposits/search">
      <parameters>
        <parameter name="query" type="string" required="true" description="Search query (reference ID, description keywords, etc.)"/>
        <parameter name="limit" type="int" required="false" default="20" description="Maximum number of results to return (1-50)"/>
      </parameters>
      <returns type="array" description="Array of matching deposit transactions"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$deposits = $client->deposits()->searchDeposits('invoice_123', 10);
foreach ($deposits as $deposit) {
    echo "Deposit: {$deposit->getDisplayName()}\n";
}
        ]]>
      </code_example>
    </operation>
  </resource>
  
  <resource name="withdraws" class="WithdrawResource">
    <operation name="listSupportedCurrencies" method="GET" endpoint="/withdrawals/currencies">
      <parameters>
        <!-- No parameters required -->
      </parameters>
      <returns type="array" description="List of supported currency codes (ISO 4217)"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$currencies = $client->withdraws()->listSupportedCurrencies();
// Returns: ['USD', 'EUR', 'BRL', 'GBP', 'JPY']
foreach ($currencies as $currency) {
    echo "Supported currency: {$currency}\n";
}
        ]]>
      </code_example>
    </operation>
    
    <operation name="createWithdrawal" method="POST" endpoint="/withdrawals">
      <parameters>
        <parameter name="transaction" type="Transaction" required="true" description="Transaction data for the withdrawal request"/>
      </parameters>
      <returns type="Transaction" description="The created withdrawal transaction with assigned ID and status"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error (e.g., insufficient funds, invalid data)"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$transaction = new Transaction(
    id: null,
    amount: '1000.00',
    currency: 'USD',
    accountId: 'acc_789',
    paymentMethod: 'wire_transfer',
    type: 'withdrawal',
    referenceId: 'monthly_payout_001',
    description: 'Monthly profit distribution'
);
$result = $client->withdraws()->createWithdrawal($transaction);
        ]]>
      </code_example>
    </operation>
    
    <operation name="getWithdrawal" method="GET" endpoint="/withdrawals/{id}">
      <parameters>
        <parameter name="withdrawalId" type="string" required="true" description="Unique identifier of the withdrawal transaction"/>
      </parameters>
      <returns type="Transaction" description="The withdrawal transaction details"/>
      <exceptions>
        <exception type="ApiException" condition="Withdrawal not found or API error occurs"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$withdrawal = $client->withdraws()->getWithdrawal('txn_abc123');
if ($withdrawal->isCompleted()) {
    echo "Withdrawal completed successfully!\n";
} elseif ($withdrawal->isPending()) {
    echo "Withdrawal is still being processed...\n";
}
        ]]>
      </code_example>
    </operation>
    
    <operation name="listWithdrawals" method="GET" endpoint="/withdrawals">
      <parameters>
        <parameter name="page" type="int" required="false" default="1" description="Page number (1-based)"/>
        <parameter name="limit" type="int" required="false" default="20" description="Number of items per page (1-100)"/>
        <parameter name="filters" type="array" required="false" description="Optional filters (status, currency, account_id, from_date, to_date, payment_method, min_amount, max_amount)"/>
      </parameters>
      <returns type="array" description="Paginated list of withdrawal transactions with pagination info"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$filters = [
    'status' => 'completed',
    'currency' => 'USD',
    'from_date' => '2023-05-01T00:00:00Z',
    'to_date' => '2023-05-31T23:59:59Z'
];
$result = $client->withdraws()->listWithdrawals(1, 50, $filters);
        ]]>
      </code_example>
    </operation>
    
    <operation name="searchWithdrawals" method="GET" endpoint="/withdrawals/search">
      <parameters>
        <parameter name="query" type="string" required="true" description="Search query (reference ID, description keywords, etc.)"/>
        <parameter name="limit" type="int" required="false" default="20" description="Maximum number of results to return (1-50)"/>
      </parameters>
      <returns type="array" description="Array of matching withdrawal transactions"/>
      <exceptions>
        <exception type="ApiException" condition="API returns an error response"/>
        <exception type="NetworkException" condition="Network connectivity issue"/>
      </exceptions>
      <code_example language="php">
        <![CDATA[
$withdrawals = $client->withdraws()->searchWithdrawals('monthly_payout');
foreach ($withdrawals as $withdrawal) {
    echo "Found: {$withdrawal->id} - {$withdrawal->description}\n";
    echo "Reference: {$withdrawal->referenceId}\n";
}
        ]]>
      </code_example>
    </operation>
  </resource>
</api_operations>
```

## ⚠️ Padrões de Tratamento de Erros

```xml
<error_handling>
  <exception_hierarchy>
    <base_exception name="XGateException">
      <child name="ApiException">
        <properties>
          <property name="statusCode" type="int" description="Código de status HTTP"/>
          <property name="errorCode" type="string" description="Código de erro da API"/>
          <property name="errorMessage" type="string" description="Mensagem de erro legível"/>
          <property name="responseData" type="array" description="Dados completos da resposta da API"/>
        </properties>
      </child>
      <child name="AuthenticationException">
        <scenarios>
          <scenario>Credenciais da API inválidas</scenario>
          <scenario>Token expirado</scenario>
          <scenario>Permissões insuficientes</scenario>
        </scenarios>
      </child>
      <child name="ValidationException">
        <properties>
          <property name="errors" type="array" description="Detalhes dos erros de validação"/>
          <property name="fieldErrors" type="array" description="Erros específicos de campos"/>
        </properties>
      </child>
      <child name="NetworkException">
        <scenarios>
          <scenario>Timeout de conexão</scenario>
          <scenario>Falha na resolução DNS</scenario>
          <scenario>Erros SSL/TLS</scenario>
        </scenarios>
      </child>
      <child name="RateLimitException">
        <properties>
          <property name="retryAfter" type="int" description="Segundos para aguardar antes de tentar novamente"/>
          <property name="limit" type="int" description="Limite de rate limit"/>
          <property name="remaining" type="int" description="Requisições restantes"/>
        </properties>
      </child>
    </base_exception>
  </exception_hierarchy>
  
  <handling_patterns>
    <pattern name="comprehensive_handling" priority="recommended">
      <code_example language="php">
        <![CDATA[
try {
    $customer = $client->customers()->create($customerData);
    echo "Cliente criado: " . $customer->getId();
    
} catch (ValidationException $e) {
    // Tratar erros de validação
    foreach ($e->getErrors() as $field => $errors) {
        echo "Erro de validação em $field: " . implode(', ', $errors) . "\n";
    }
    
} catch (RateLimitException $e) {
    // Tratar rate limiting
    $retryAfter = $e->getRetryAfter();
    echo "Rate limit atingido. Tente novamente em $retryAfter segundos.\n";
    sleep($retryAfter);
    // Implementar lógica de retry
    
} catch (AuthenticationException $e) {
    // Tratar erros de autenticação
    echo "Falha na autenticação: " . $e->getMessage() . "\n";
    // Atualizar credenciais ou re-autenticar
    
} catch (NetworkException $e) {
    // Tratar erros de rede
    echo "Erro de rede: " . $e->getMessage() . "\n";
    // Implementar retry com backoff exponencial
    
} catch (ApiException $e) {
    // Tratar erros da API
    $statusCode = $e->getStatusCode();
    $errorCode = $e->getErrorCode();
    echo "Erro da API ($statusCode): $errorCode - " . $e->getMessage() . "\n";
    
} catch (XGateException $e) {
    // Tratar outros erros do SDK
    echo "Erro do SDK: " . $e->getMessage() . "\n";
    
} catch (Exception $e) {
    // Tratar erros inesperados
    echo "Erro inesperado: " . $e->getMessage() . "\n";
}
        ]]>
      </code_example>
    </pattern>
  </handling_patterns>
</error_handling>
```

## 🔄 Padrões de Retry e Resiliência

```xml
<resilience_patterns>
  <pattern name="exponential_backoff" priority="recommended">
    <description>Implementar retry com backoff exponencial para operações que falharam</description>
    <code_example language="php">
      <![CDATA[
/**
 * Executa uma operação com retry automático e backoff exponencial
 * 
 * @param callable $operation Operação a ser executada
 * @param int $maxRetries Número máximo de tentativas
 * @return mixed Resultado da operação
 */
function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
{
    $attempt = 0;
    $baseDelay = 1; // segundos
    
    while ($attempt < $maxRetries) {
        try {
            return $operation();
            
        } catch (RateLimitException $e) {
            $delay = $e->getRetryAfter() ?: ($baseDelay * pow(2, $attempt));
            sleep($delay);
            
        } catch (NetworkException $e) {
            if ($attempt === $maxRetries - 1) {
                throw $e;
            }
            $delay = $baseDelay * pow(2, $attempt);
            sleep($delay);
            
        } catch (ApiException $e) {
            // Apenas tentar novamente em erros 5xx do servidor
            if ($e->getStatusCode() < 500 || $attempt === $maxRetries - 1) {
                throw $e;
            }
            $delay = $baseDelay * pow(2, $attempt);
            sleep($delay);
        }
        
        $attempt++;
    }
    
    throw new Exception("Máximo de tentativas excedido");
}

// Uso
$customer = executeWithRetry(function() use ($client, $customerData) {
    return $client->customers()->create($customerData);
});
      ]]>
    </code_example>
  </pattern>
  
  <pattern name="circuit_breaker" priority="advanced">
    <description>Implementar padrão circuit breaker para falhas persistentes</description>
    <code_example language="php">
      <![CDATA[
class CircuitBreaker
{
    private int $failureCount = 0;
    private int $threshold = 5;
    private int $timeout = 60; // segundos
    private ?int $lastFailureTime = null;
    private string $state = 'CLOSED'; // CLOSED, OPEN, HALF_OPEN
    
    public function call(callable $operation): mixed
    {
        if ($this->state === 'OPEN') {
            if (time() - $this->lastFailureTime >= $this->timeout) {
                $this->state = 'HALF_OPEN';
            } else {
                throw new Exception('Circuit breaker está ABERTO');
            }
        }
        
        try {
            $result = $operation();
            $this->onSuccess();
            return $result;
            
        } catch (Exception $e) {
            $this->onFailure();
            throw $e;
        }
    }
    
    private function onSuccess(): void
    {
        $this->failureCount = 0;
        $this->state = 'CLOSED';
    }
    
    private function onFailure(): void
    {
        $this->failureCount++;
        $this->lastFailureTime = time();
        
        if ($this->failureCount >= $this->threshold) {
            $this->state = 'OPEN';
        }
    }
}
      ]]>
    </code_example>
  </pattern>
</resilience_patterns>
```

## 🧪 Padrões de Teste

```xml
<testing_patterns>
  <pattern name="unit_testing" priority="essential">
    <description>Testes unitários com mocking de dependências</description>
    <code_example language="php">
      <![CDATA[
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CustomerServiceTest extends TestCase
{
    private XGateClient|MockObject $mockClient;
    
    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(XGateClient::class);
    }
    
    public function testCreateCustomerSuccess(): void
    {
        $customerData = [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com'
        ];
        
        $expectedCustomer = new Customer();
        $expectedCustomer->setId('customer-123');
        $expectedCustomer->setName('João Silva');
        
        $mockCustomerResource = $this->createMock(CustomerResource::class);
        $mockCustomerResource
            ->expects($this->once())
            ->method('create')
            ->with($customerData)
            ->willReturn($expectedCustomer);
            
        $this->mockClient
            ->expects($this->once())
            ->method('customers')
            ->willReturn($mockCustomerResource);
            
        $result = $this->mockClient->customers()->create($customerData);
        
        $this->assertInstanceOf(Customer::class, $result);
        $this->assertEquals('customer-123', $result->getId());
        $this->assertEquals('João Silva', $result->getName());
    }
    
    public function testCreateCustomerValidationError(): void
    {
        $this->expectException(ValidationException::class);
        
        $invalidData = ['email' => 'email-invalido'];
        
        $mockCustomerResource = $this->createMock(CustomerResource::class);
        $mockCustomerResource
            ->expects($this->once())
            ->method('create')
            ->with($invalidData)
            ->willThrowException(new ValidationException('Email inválido'));
            
        $this->mockClient
            ->expects($this->once())
            ->method('customers')
            ->willReturn($mockCustomerResource);
            
        $this->mockClient->customers()->create($invalidData);
    }
}
      ]]>
    </code_example>
  </pattern>
  
  <pattern name="integration_testing" priority="important">
    <description>Testes de integração com ambiente sandbox</description>
    <code_example language="php">
      <![CDATA[
class XGateIntegrationTest extends TestCase
{
    private XGateClient $client;
    
    protected function setUp(): void
    {
        $this->client = new XGateClient([
            'api_key' => $_ENV['XGATE_TEST_API_KEY'],
            'api_secret' => $_ENV['XGATE_TEST_API_SECRET'],
            'environment' => 'sandbox',
            'timeout' => 30
        ]);
    }
    
    public function testCustomerLifecycle(): void
    {
        // Criar cliente
        $customer = $this->client->customers()->create([
            'name' => 'Cliente Teste',
            'email' => 'teste@exemplo.com',
            'document' => '12345678901'
        ]);
        
        $this->assertNotNull($customer->getId());
        $this->assertEquals('Cliente Teste', $customer->getName());
        
        // Buscar cliente
        $retrievedCustomer = $this->client->customers()->get($customer->getId());
        $this->assertEquals($customer->getId(), $retrievedCustomer->getId());
        
        // Atualizar cliente
        $updatedCustomer = $this->client->customers()->update($customer->getId(), [
            'name' => 'Cliente Teste Atualizado'
        ]);
        $this->assertEquals('Cliente Teste Atualizado', $updatedCustomer->getName());
        
        // Deletar cliente
        $result = $this->client->customers()->delete($customer->getId());
        $this->assertTrue($result);
    }
}
      ]]>
    </code_example>
  </pattern>
</testing_patterns>
```

## 📈 Monitoramento e Observabilidade

```xml
<monitoring>
  <logging_patterns>
    <pattern name="structured_logging" priority="recommended">
      <description>Log estruturado com contexto para debugging</description>
      <code_example language="php">
        <![CDATA[
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

// Configurar logger
$logger = new Logger('xgate-sdk');
$handler = new StreamHandler('php://stdout', Logger::INFO);
$handler->setFormatter(new JsonFormatter());
$logger->pushHandler($handler);

// Configurar cliente com logging
$client = new XGateClient([
    'api_key' => $_ENV['XGATE_API_KEY'],
    'api_secret' => $_ENV['XGATE_API_SECRET'],
    'environment' => 'production',
    'timeout' => 30,
    'verify_ssl' => true, // Sempre validar SSL
    'debug' => false, // Desabilitar debug em produção
    'log_sensitive_data' => false // Não logar dados sensíveis
]);

// O SDK automaticamente logará:
// - Requisições HTTP (método, URL, headers)
// - Respostas HTTP (status, tempo de resposta)
// - Erros e exceções com contexto
// - Tentativas de retry e rate limiting
        ]]>
      </code_example>
    </pattern>
    
    <pattern name="custom_middleware" priority="advanced">
      <description>Middleware customizado para métricas e tracing</description>
      <code_example language="php">
        <![CDATA[
class MetricsMiddleware implements MiddlewareInterface
{
    private $metricsCollector;
    
    public function __construct(MetricsCollectorInterface $collector)
    {
        $this->metricsCollector = $collector;
    }
    
    public function process(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $startTime = microtime(true);
        
        try {
            $response = $this->next->process($request, $response);
            
            // Coletar métricas de sucesso
            $this->metricsCollector->increment('xgate.api.requests.success', [
                'method' => $request->getMethod(),
                'endpoint' => $this->extractEndpoint($request->getUri())
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            // Coletar métricas de erro
            $this->metricsCollector->increment('xgate.api.requests.error', [
                'method' => $request->getMethod(),
                'error_type' => get_class($e)
            ]);
            
            throw $e;
            
        } finally {
            // Coletar métricas de tempo de resposta
            $duration = microtime(true) - $startTime;
            $this->metricsCollector->timing('xgate.api.response_time', $duration);
        }
    }
}
        ]]>
      </code_example>
    </pattern>
  </logging_patterns>
</monitoring>
```

## 📋 Exemplos Completos com Padrões de Input/Output

```xml
<comprehensive_examples>
  <example name="complete_customer_workflow" type="end_to_end">
    <description>Fluxo completo de gerenciamento de cliente: criação, consulta, atualização e exclusão</description>
    
    <step name="create_customer" order="1">
      <input_data>
        <parameter name="name" value="João Silva Santos"/>
        <parameter name="email" value="joao.silva@email.com"/>
        <parameter name="phone" value="+5511987654321"/>
        <parameter name="document" value="12345678901"/>
        <parameter name="documentType" value="cpf"/>
        <parameter name="metadata" type="array">
          <item key="source" value="website"/>
          <item key="campaign" value="summer_2024"/>
          <item key="preferred_contact" value="email"/>
        </parameter>
      </input_data>
      
      <code_implementation language="php">
        <![CDATA[
try {
    $customer = $client->customers()->create(
        name: 'João Silva Santos',
        email: 'joao.silva@email.com',
        phone: '+5511987654321',
        document: '12345678901',
        documentType: 'cpf',
        metadata: [
            'source' => 'website',
            'campaign' => 'summer_2024',
            'preferred_contact' => 'email'
        ]
    );
    
    $customerId = $customer->id;
    echo "Cliente criado com sucesso: {$customerId}\n";
    
} catch (ValidationException $e) {
    echo "Erro de validação: " . $e->getMessage() . "\n";
    foreach ($e->getFieldErrors() as $field => $errors) {
        echo "Campo {$field}: " . implode(', ', $errors) . "\n";
    }
} catch (ApiException $e) {
    echo "Erro da API: {$e->getMessage()}\n";
    echo "Código: {$e->getErrorCode()}\n";
}
        ]]>
      </code_implementation>
      
      <expected_output>
        <success_response>
          <customer_object>
            <property name="id" value="cust_abc123def456"/>
            <property name="name" value="João Silva Santos"/>
            <property name="email" value="joao.silva@email.com"/>
            <property name="phone" value="+5511987654321"/>
            <property name="document" value="12345678901"/>
            <property name="documentType" value="cpf"/>
            <property name="status" value="active"/>
            <property name="createdAt" value="2024-01-15T10:30:00Z"/>
            <property name="metadata" type="array">
              <item key="source" value="website"/>
              <item key="campaign" value="summer_2024"/>
              <item key="preferred_contact" value="email"/>
            </property>
          </customer_object>
        </success_response>
        <error_responses>
          <validation_error>
            <message>Dados de entrada inválidos</message>
            <field_errors>
              <field name="email">E-mail deve ter um formato válido</field>
              <field name="document">CPF deve ter 11 dígitos</field>
            </field_errors>
          </validation_error>
          <api_error>
            <message>Cliente já existe com este e-mail</message>
            <code>CUSTOMER_DUPLICATE_EMAIL</code>
            <status_code>409</status_code>
          </api_error>
        </error_responses>
      </expected_output>
    </step>
    
    <step name="get_customer" order="2">
      <input_data>
        <parameter name="customerId" value="cust_abc123def456"/>
      </input_data>
      
      <code_implementation language="php">
        <![CDATA[
try {
    $customer = $client->customers()->get('cust_abc123def456');
    
    echo "Cliente encontrado: {$customer->name}\n";
    echo "E-mail: {$customer->email}\n";
    echo "Status: {$customer->status}\n";
    echo "Criado em: {$customer->createdAt->format('d/m/Y H:i')}\n";
    
    // Acessar metadados
    if ($customer->hasMetadata('source')) {
        echo "Origem: {$customer->getMetadata('source')}\n";
    }
    
} catch (ApiException $e) {
    if ($e->getStatusCode() === 404) {
        echo "Cliente não encontrado\n";
    } else {
        echo "Erro ao buscar cliente: {$e->getMessage()}\n";
    }
}
        ]]>
      </code_implementation>
      
      <expected_output>
        <success_response>
          <console_output>
            <line>Cliente encontrado: João Silva Santos</line>
            <line>E-mail: joao.silva@email.com</line>
            <line>Status: active</line>
            <line>Criado em: 15/01/2024 10:30</line>
            <line>Origem: website</line>
          </console_output>
        </success_response>
      </expected_output>
    </step>
    
    <step name="update_customer" order="3">
      <input_data>
        <parameter name="customerId" value="cust_abc123def456"/>
        <parameter name="updateData" type="array">
          <item key="phone" value="+5511999888777"/>
          <item key="metadata" type="array">
            <item key="source" value="website"/>
            <item key="campaign" value="summer_2024"/>
            <item key="preferred_contact" value="phone"/>
            <item key="last_update" value="2024-01-15"/>
          </item>
        </parameter>
      </input_data>
      
      <code_implementation language="php">
        <![CDATA[
try {
    $updatedCustomer = $client->customers()->update('cust_abc123def456', [
        'phone' => '+5511999888777',
        'metadata' => [
            'source' => 'website',
            'campaign' => 'summer_2024',
            'preferred_contact' => 'phone',
            'last_update' => date('Y-m-d')
        ]
    ]);
    
    echo "Cliente atualizado com sucesso\n";
    echo "Novo telefone: {$updatedCustomer->phone}\n";
    echo "Contato preferido: {$updatedCustomer->getMetadata('preferred_contact')}\n";
    
} catch (ValidationException $e) {
    echo "Dados inválidos para atualização: {$e->getMessage()}\n";
} catch (ApiException $e) {
    echo "Erro ao atualizar cliente: {$e->getMessage()}\n";
}
        ]]>
      </code_implementation>
    </step>
  </example>
  
  <example name="pix_key_management" type="workflow">
    <description>Gerenciamento completo de chaves PIX: registro, consulta, busca e exclusão</description>
    
    <step name="register_pix_key" order="1">
      <input_data>
        <parameter name="type" value="email"/>
        <parameter name="key" value="joao.silva@email.com"/>
        <parameter name="accountHolderName" value="João Silva Santos"/>
        <parameter name="accountHolderDocument" value="12345678901"/>
        <parameter name="bankCode" value="001"/>
        <parameter name="accountNumber" value="12345-6"/>
        <parameter name="accountType" value="checking"/>
        <parameter name="metadata" type="array">
          <item key="purpose" value="business"/>
          <item key="priority" value="high"/>
        </parameter>
      </input_data>
      
      <code_implementation language="php">
        <![CDATA[
try {
    $pixKey = $client->pix()->register(
        type: 'email',
        key: 'joao.silva@email.com',
        accountHolderName: 'João Silva Santos',
        accountHolderDocument: '12345678901',
        bankCode: '001',
        accountNumber: '12345-6',
        accountType: 'checking',
        metadata: [
            'purpose' => 'business',
            'priority' => 'high'
        ]
    );
    
    echo "Chave PIX registrada: {$pixKey->id}\n";
    echo "Tipo: {$pixKey->type}\n";
    echo "Chave: {$pixKey->key}\n";
    echo "Status: {$pixKey->status}\n";
    
} catch (ApiException $e) {
    echo "Erro ao registrar chave PIX: {$e->getMessage()}\n";
    if ($e->getErrorCode() === 'PIX_KEY_ALREADY_EXISTS') {
        echo "Esta chave PIX já está registrada\n";
    }
}
        ]]>
      </code_implementation>
      
      <expected_output>
        <success_response>
          <pix_key_object>
            <property name="id" value="pix_key_xyz789"/>
            <property name="type" value="email"/>
            <property name="key" value="joao.silva@email.com"/>
            <property name="accountHolderName" value="João Silva Santos"/>
            <property name="accountHolderDocument" value="12345678901"/>
            <property name="bankCode" value="001"/>
            <property name="status" value="active"/>
            <property name="createdAt" value="2024-01-15T11:00:00Z"/>
          </pix_key_object>
        </success_response>
      </expected_output>
    </step>
    
    <step name="search_pix_keys" order="2">
      <input_data>
        <parameter name="query" value="joao.silva"/>
        <parameter name="limit" value="10"/>
      </input_data>
      
      <code_implementation language="php">
        <![CDATA[
try {
    $pixKeys = $client->pix()->search('joao.silva', 10);
    
    echo "Encontradas " . count($pixKeys) . " chaves PIX\n";
    
    foreach ($pixKeys as $pixKey) {
        echo "ID: {$pixKey->id}\n";
        echo "Tipo: {$pixKey->type}\n";
        echo "Chave: {$pixKey->key}\n";
        echo "Portador: {$pixKey->accountHolderName}\n";
        echo "Status: {$pixKey->status}\n";
        echo "---\n";
    }
    
} catch (ApiException $e) {
    echo "Erro na busca: {$e->getMessage()}\n";
}
        ]]>
      </code_implementation>
    </step>
  </example>
  
  <example name="transaction_processing" type="financial_workflow">
    <description>Processamento de transações financeiras com diferentes tipos de operação</description>
    
    <step name="create_deposit" order="1">
      <input_data>
        <parameter name="transaction" type="Transaction">
          <property name="amount" value="500.00"/>
          <property name="currency" value="BRL"/>
          <property name="accountId" value="acc_customer_001"/>
          <property name="paymentMethod" value="bank_transfer"/>
          <property name="type" value="deposit"/>
          <property name="referenceId" value="invoice_2024_001"/>
          <property name="description" value="Depósito via transferência bancária"/>
        </parameter>
      </input_data>
      
      <code_implementation language="php">
        <![CDATA[
use XGate\DTO\Transaction;

try {
    $transaction = new Transaction(
        id: null,
        amount: '500.00',
        currency: 'BRL',
        accountId: 'acc_customer_001',
        paymentMethod: 'bank_transfer',
        type: 'deposit',
        referenceId: 'invoice_2024_001',
        description: 'Depósito via transferência bancária'
    );
    
    $result = $client->deposits()->createDeposit($transaction);
    
    echo "Depósito criado: {$result->id}\n";
    echo "Status: {$result->status}\n";
    echo "Valor: {$result->getFormattedAmount()}\n";
    echo "Referência: {$result->referenceId}\n";
    
    // Monitorar status
    while ($result->isPending()) {
        sleep(5); // Aguardar 5 segundos
        $result = $client->deposits()->getDeposit($result->id);
        echo "Status atual: {$result->status}\n";
        
        if ($result->isCompleted()) {
            echo "Depósito confirmado!\n";
            break;
        } elseif ($result->isFailed()) {
            echo "Depósito falhou: {$result->failureReason}\n";
            break;
        }
    }
    
} catch (ApiException $e) {
    echo "Erro ao processar depósito: {$e->getMessage()}\n";
    
    if ($e->getErrorCode() === 'INSUFFICIENT_BALANCE') {
        echo "Saldo insuficiente na conta de origem\n";
    } elseif ($e->getErrorCode() === 'INVALID_ACCOUNT') {
        echo "Conta de destino inválida\n";
    }
}
        ]]>
      </code_implementation>
      
      <expected_output>
        <success_response>
          <transaction_object>
            <property name="id" value="txn_dep_001"/>
            <property name="amount" value="500.00"/>
            <property name="currency" value="BRL"/>
            <property name="status" value="pending"/>
            <property name="type" value="deposit"/>
            <property name="createdAt" value="2024-01-15T12:00:00Z"/>
            <property name="referenceId" value="invoice_2024_001"/>
          </transaction_object>
          <status_updates>
            <update timestamp="2024-01-15T12:00:05Z" status="processing"/>
            <update timestamp="2024-01-15T12:02:30Z" status="completed"/>
          </status_updates>
        </success_response>
      </expected_output>
    </step>
  </example>
  
  <example name="error_handling_patterns" type="best_practices">
    <description>Padrões recomendados para tratamento de erros e recuperação</description>
    
    <pattern name="comprehensive_error_handling">
      <code_implementation language="php">
        <![CDATA[
use XGate\Exceptions\{
    ApiException,
    AuthenticationException,
    ValidationException,
    NetworkException,
    RateLimitException
};

function processCustomerWithRetry(array $customerData, int $maxRetries = 3): ?Customer
{
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            $attempt++;
            
            // Tentar criar cliente
            $customer = $client->customers()->create(
                name: $customerData['name'],
                email: $customerData['email'],
                phone: $customerData['phone'] ?? null,
                document: $customerData['document'] ?? null,
                documentType: $customerData['document_type'] ?? 'cpf'
            );
            
            echo "Cliente criado com sucesso na tentativa {$attempt}\n";
            return $customer;
            
        } catch (ValidationException $e) {
            // Erro de validação - não retry
            echo "Erro de validação (sem retry): {$e->getMessage()}\n";
            foreach ($e->getFieldErrors() as $field => $errors) {
                echo "- {$field}: " . implode(', ', $errors) . "\n";
            }
            return null;
            
        } catch (AuthenticationException $e) {
            // Erro de autenticação - não retry
            echo "Erro de autenticação: {$e->getMessage()}\n";
            return null;
            
        } catch (RateLimitException $e) {
            // Rate limit - aguardar e tentar novamente
            $waitTime = $e->getRetryAfter() ?? (2 ** $attempt); // Exponential backoff
            echo "Rate limit atingido. Aguardando {$waitTime} segundos...\n";
            sleep($waitTime);
            continue;
            
        } catch (NetworkException $e) {
            // Erro de rede - retry com backoff
            if ($attempt >= $maxRetries) {
                echo "Erro de rede após {$maxRetries} tentativas: {$e->getMessage()}\n";
                return null;
            }
            
            $waitTime = 2 ** $attempt; // Exponential backoff: 2, 4, 8 segundos
            echo "Erro de rede (tentativa {$attempt}). Tentando novamente em {$waitTime}s...\n";
            sleep($waitTime);
            continue;
            
        } catch (ApiException $e) {
            // Outros erros da API
            if ($e->getStatusCode() >= 500) {
                // Erro do servidor - retry
                if ($attempt >= $maxRetries) {
                    echo "Erro do servidor após {$maxRetries} tentativas: {$e->getMessage()}\n";
                    return null;
                }
                
                $waitTime = 2 ** $attempt;
                echo "Erro do servidor (tentativa {$attempt}). Tentando novamente em {$waitTime}s...\n";
                sleep($waitTime);
                continue;
            } else {
                // Erro do cliente - não retry
                echo "Erro da API: {$e->getMessage()}\n";
                echo "Código: {$e->getErrorCode()}\n";
                return null;
            }
            
        } catch (Exception $e) {
            // Erro inesperado
            echo "Erro inesperado: {$e->getMessage()}\n";
            return null;
        }
    }
    
    echo "Falha após {$maxRetries} tentativas\n";
    return null;
}

// Exemplo de uso
$customerData = [
    'name' => 'Maria Silva',
    'email' => 'maria.silva@email.com',
    'phone' => '+5511987654321',
    'document' => '98765432109',
    'document_type' => 'cpf'
];

$customer = processCustomerWithRetry($customerData);

if ($customer !== null) {
    echo "Processamento bem-sucedido: {$customer->id}\n";
} else {
    echo "Falha no processamento do cliente\n";
}
        ]]>
      </code_implementation>
      
      <expected_behavior>
        <scenario name="validation_error">
          <input>Invalid email format</input>
          <output>Immediate failure with field-specific error messages</output>
          <retry>No retry attempted</retry>
        </scenario>
        <scenario name="rate_limit">
          <input>Too many requests</input>
          <output>Wait for specified time, then retry</output>
          <retry>Yes, respects rate limit headers</retry>
        </scenario>
        <scenario name="network_error">
          <input>Connection timeout</input>
          <output>Exponential backoff retry (2, 4, 8 seconds)</output>
          <retry>Yes, up to maxRetries</retry>
        </scenario>
        <scenario name="server_error">
          <input>HTTP 500 Internal Server Error</input>
          <output>Exponential backoff retry</output>
          <retry>Yes, server errors are typically transient</retry>
        </scenario>
      </expected_behavior>
    </pattern>
  </example>
  
  <example name="batch_operations" type="bulk_processing">
    <description>Processamento em lote para operações de alto volume</description>
    
    <step name="batch_customer_creation" order="1">
      <input_data>
        <parameter name="customers" type="array">
          <item index="0">
            <property name="name" value="Cliente 1"/>
            <property name="email" value="cliente1@email.com"/>
            <property name="document" value="11111111111"/>
          </item>
          <item index="1">
            <property name="name" value="Cliente 2"/>
            <property name="email" value="cliente2@email.com"/>
            <property name="document" value="22222222222"/>
          </item>
          <item index="2">
            <property name="name" value="Cliente 3"/>
            <property name="email" value="cliente3@email.com"/>
            <property name="document" value="33333333333"/>
          </item>
        </parameter>
      </input_data>
      
      <code_implementation language="php">
        <![CDATA[
function processBatchCustomers(array $customersData, int $batchSize = 5): array
{
    $results = [
        'success' => [],
        'failed' => [],
        'total' => count($customersData)
    ];
    
    $batches = array_chunk($customersData, $batchSize);
    
    foreach ($batches as $batchIndex => $batch) {
        echo "Processando lote " . ($batchIndex + 1) . " de " . count($batches) . "\n";
        
        foreach ($batch as $customerData) {
            try {
                $customer = $client->customers()->create(
                    name: $customerData['name'],
                    email: $customerData['email'],
                    document: $customerData['document'] ?? null,
                    documentType: 'cpf'
                );
                
                $results['success'][] = [
                    'input' => $customerData,
                    'customer' => $customer,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                echo "✅ {$customerData['name']} - {$customer->id}\n";
                
            } catch (Exception $e) {
                $results['failed'][] = [
                    'input' => $customerData,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'failed_at' => date('Y-m-d H:i:s')
                ];
                
                echo "❌ {$customerData['name']} - {$e->getMessage()}\n";
            }
            
            // Pequena pausa para evitar rate limiting
            usleep(100000); // 100ms
        }
        
        // Pausa maior entre lotes
        if ($batchIndex < count($batches) - 1) {
            echo "Aguardando 2 segundos antes do próximo lote...\n";
            sleep(2);
        }
    }
    
    return $results;
}

// Dados de exemplo
$customersData = [
    ['name' => 'João Silva', 'email' => 'joao@email.com', 'document' => '11111111111'],
    ['name' => 'Maria Santos', 'email' => 'maria@email.com', 'document' => '22222222222'],
    ['name' => 'Pedro Costa', 'email' => 'pedro@email.com', 'document' => '33333333333'],
    ['name' => 'Ana Oliveira', 'email' => 'ana@email.com', 'document' => '44444444444'],
    ['name' => 'Carlos Lima', 'email' => 'carlos@email.com', 'document' => '55555555555'],
];

$results = processBatchCustomers($customersData);

echo "\n=== RESUMO DO PROCESSAMENTO ===\n";
echo "Total: {$results['total']}\n";
echo "Sucesso: " . count($results['success']) . "\n";
echo "Falhas: " . count($results['failed']) . "\n";

if (!empty($results['failed'])) {
    echo "\n=== FALHAS DETALHADAS ===\n";
    foreach ($results['failed'] as $failure) {
        echo "- {$failure['input']['name']}: {$failure['error']}\n";
    }
}
        ]]>
      </code_implementation>
      
      <expected_output>
        <batch_processing_log>
          <line>Processando lote 1 de 1</line>
          <line>✅ João Silva - cust_abc123</line>
          <line>✅ Maria Santos - cust_def456</line>
          <line>❌ Pedro Costa - E-mail já cadastrado</line>
          <line>✅ Ana Oliveira - cust_ghi789</line>
          <line>✅ Carlos Lima - cust_jkl012</line>
          <line></line>
          <line>=== RESUMO DO PROCESSAMENTO ===</line>
          <line>Total: 5</line>
          <line>Sucesso: 4</line>
          <line>Falhas: 1</line>
          <line></line>
          <line>=== FALHAS DETALHADAS ===</line>
          <line>- Pedro Costa: E-mail já cadastrado</line>
        </batch_processing_log>
      </expected_output>
    </step>
  </example>
</comprehensive_examples>
```

## 🔒 Segurança e Boas Práticas

```xml
<security_best_practices>
  <practice name="credential_management" priority="critical">
    <description>Gerenciamento seguro de credenciais da API</description>
    <guidelines>
      <guideline>Nunca hardcodar credenciais no código fonte</guideline>
      <guideline>Usar variáveis de ambiente para credenciais</guideline>
      <guideline>Rotacionar credenciais regularmente</guideline>
      <guideline>Usar diferentes credenciais para diferentes ambientes</guideline>
      <guideline>Monitorar uso de credenciais para detectar vazamentos</guideline>
    </guidelines>
    <code_example language="php">
      <![CDATA[
// ❌ NUNCA fazer isso
$client = new XGateClient([
    'api_key' => 'xgate_live_123456789',
    'api_secret' => 'secret_abc123def456'
]);

// ✅ Usar variáveis de ambiente
$client = new XGateClient([
    'api_key' => $_ENV['XGATE_API_KEY'] ?? throw new InvalidArgumentException('XGATE_API_KEY não definida'),
    'api_secret' => $_ENV['XGATE_API_SECRET'] ?? throw new InvalidArgumentException('XGATE_API_SECRET não definida'),
    'environment' => $_ENV['XGATE_ENV'] ?? 'sandbox'
]);

// ✅ Validar ambiente
if ($_ENV['XGATE_ENV'] === 'production' && !$_ENV['XGATE_API_KEY']) {
    throw new InvalidArgumentException('Credenciais de produção são obrigatórias');
}
      ]]>
    </code_example>
  </practice>
  
  <practice name="input_validation" priority="high">
    <description>Validação rigorosa de entrada para prevenir ataques</description>
    <code_example language="php">
      <![CDATA[
/**
 * Valida dados de cliente antes de enviar para a API
 */
function validateCustomerData(array $data): array
{
    $validator = new Validator();
    
    $rules = [
        'name' => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
        'email' => 'required|email|max:255',
        'document' => 'nullable|string|regex:/^\d{11}$|cpf',
        'phone' => 'nullable|string|regex:/^\+55\d{10,11}$/'
    ];
    
    $validated = $validator->validate($data, $rules);
    
    if ($validator->fails()) {
        throw new ValidationException('Dados inválidos', $validator->errors());
    }
    
    // Sanitizar dados
    return [
        'name' => trim(strip_tags($validated['name'])),
        'email' => filter_var($validated['email'], FILTER_SANITIZE_EMAIL),
        'document' => preg_replace('/\D/', '', $validated['document'] ?? ''),
        'phone' => preg_replace('/\D/', '', $validated['phone'] ?? '')
    ];
}
      ]]>
    </code_example>
  </practice>
  
  <practice name="secure_communication" priority="high">
    <description>Comunicação segura com a API</description>
    <guidelines>
      <guideline>Sempre usar HTTPS para comunicação com a API</guideline>
      <guideline>Validar certificados SSL/TLS</guideline>
      <guideline>Implementar timeout adequado para requisições</guideline>
      <guideline>Não logar informações sensíveis</guideline>
    </guidelines>
    <code_example language="php">
      <![CDATA[
$client = new XGateClient([
    'api_key' => $_ENV['XGATE_API_KEY'],
    'api_secret' => $_ENV['XGATE_API_SECRET'],
    'environment' => 'production',
    'timeout' => 30,
    'verify_ssl' => true, // Sempre validar SSL
    'debug' => false, // Desabilitar debug em produção
    'log_sensitive_data' => false // Não logar dados sensíveis
]);
      ]]>
    </code_example>
  </practice>
</security_best_practices>
```

## 📚 Recursos Adicionais

```xml
<additional_resources>
  <documentation>
    <resource name="README.md" type="getting_started" description="Guia de início rápido e configuração básica"/>
    <resource name="QUICKSTART.md" type="tutorial" description="Tutorial passo-a-passo para primeiros passos"/>
    <resource name="examples/" type="code_samples" description="Exemplos práticos de uso do SDK"/>
    <resource name="tests/" type="test_examples" description="Exemplos de testes unitários e de integração"/>
  </documentation>
  
  <development_tools>
    <tool name="PHPStan" type="static_analysis" description="Análise estática com validação de docblocks"/>
    <tool name="PHP CS Fixer" type="code_formatting" description="Formatação automática de código e documentação"/>
    <tool name="PHPUnit" type="testing" description="Framework de testes com cobertura"/>
    <tool name="Composer" type="dependency_management" description="Gerenciamento de dependências"/>
  </development_tools>
  
  <ide_support>
    <ide name="PhpStorm" configuration=".phpstorm.meta.php" description="Autocompletar e type hints avançados"/>
    <ide name="VS Code" configuration=".vscode/" description="Configuração completa para desenvolvimento"/>
  </ide_support>
  
  <quality_commands>
    <command name="composer test" description="Executar suite completa de testes"/>
    <command name="composer phpstan" description="Análise estática com PHPStan"/>
    <command name="composer cs-fix" description="Corrigir formatação de código"/>
    <command name="composer docs-fix" description="Corrigir formatação de documentação"/>
    <command name="composer quality" description="Executar todas as verificações de qualidade"/>
  </quality_commands>
</additional_resources>
```

---

## 🤖 Diretrizes para Agentes de IA

### Padrões de Uso Recomendados

1. **Sempre validar entrada** antes de fazer chamadas da API
2. **Implementar tratamento de erro abrangente** com tipos específicos de exceção
3. **Usar configuração baseada em ambiente** para diferentes ambientes
4. **Implementar retry com backoff exponencial** para operações que falharam
5. **Logar operações importantes** para debugging e monitoramento
6. **Seguir padrões de segurança** para credenciais e comunicação

### Casos de Uso Comuns

- **Integração de e-commerce**: Gerenciar clientes e processar pagamentos PIX
- **Sistemas financeiros**: Processar depósitos, saques e transferências
- **Plataformas de marketplace**: Gerenciar múltiplos clientes e transações
- **Aplicações móveis**: Integração via API REST para funcionalidades financeiras

### Suporte e Comunidade

- **Documentação**: Consulte README.md e QUICKSTART.md para informações detalhadas
- **Exemplos**: Veja a pasta `examples/` para casos de uso práticos
- **Testes**: Execute `composer test` para validar funcionalidade
- **Qualidade**: Use `composer quality` para verificar padrões de código

---

*Este documento é otimizado para consumo por agentes de IA e assistentes de código. Para documentação voltada ao usuário final, consulte o README.md principal.* 