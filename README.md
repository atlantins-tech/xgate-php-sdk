# XGATE PHP SDK

Um SDK PHP moderno e robusto para integração com a API da XGATE Global, uma plataforma de pagamentos que oferece soluções para depósitos, saques e conversões entre moedas fiduciárias e criptomoedas.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)]()

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Guia de Início Rápido](#-guia-de-início-rápido)
- [Autenticação](#-autenticação)
- [Funcionalidades da API](#-funcionalidades-da-api)
- [Exemplos de Uso](#-exemplos-de-uso)
- [Tratamento de Erros](#-tratamento-de-erros)
- [Logging e Debug](#-logging-e-debug)
- [Testes](#-testes)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)
- [Suporte](#-suporte)

## ✨ Características

- ✅ **Autenticação JWT automática** com renovação de tokens
- ✅ **Validação rigorosa** de dados de entrada com exceções específicas
- ✅ **Tratamento robusto de erros** com hierarquia de exceções customizadas
- ✅ **Suporte completo a PHPDoc** para melhor experiência de desenvolvimento
- ✅ **Compatível com PHP 8.1+** usando recursos modernos da linguagem
- ✅ **Seguindo padrões PSR** (PSR-4, PSR-12, PSR-3, PSR-16)
- ✅ **Cache inteligente** para otimização de performance
- ✅ **Logging estruturado** com níveis configuráveis
- ✅ **Rate limiting** com retry automático
- ✅ **Testes abrangentes** com cobertura completa

## 📦 Instalação

### Requisitos

- PHP 8.1 ou superior
- Extensões PHP: `curl`, `json`, `mbstring`
- Composer

### Via Composer

```bash
composer require xgate/php-sdk
```

### Instalação Manual

```bash
git clone https://github.com/xgate/php-sdk.git
cd php-sdk
composer install
```

## ⚙️ Configuração

### Configuração Básica

```php
<?php

require_once 'vendor/autoload.php';

use XGate\XGateClient;

// Configuração básica
$client = new XGateClient([
    'api_key' => 'your-api-key',
    'base_url' => 'https://api.xgate.com',
    'environment' => 'production', // ou 'sandbox'
    'timeout' => 30,
    'retry_attempts' => 3,
]);
```

### Configuração Avançada

```php
<?php

use XGate\XGateClient;
use XGate\Configuration\ConfigurationManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

// Logger personalizado
$logger = new Logger('xgate-sdk');
$logger->pushHandler(new StreamHandler('logs/xgate.log', Logger::INFO));

// Cache personalizado (Redis)
$redis = new RedisAdapter(RedisAdapter::createConnection('redis://localhost'));
$cache = new Psr16Cache($redis);

// Configuração detalhada
$config = new ConfigurationManager([
    'api_key' => getenv('XGATE_API_KEY'),
    'base_url' => getenv('XGATE_BASE_URL') ?: 'https://api.xgate.com',
    'environment' => getenv('XGATE_ENV') ?: 'production',
    'timeout' => 30,
    'retry_attempts' => 3,
    'retry_delay' => 1000, // milliseconds
    'rate_limit_requests' => 100,
    'rate_limit_window' => 60, // seconds
]);

$client = new XGateClient($config, $logger, $cache);
```

### Variáveis de Ambiente

Crie um arquivo `.env` na raiz do seu projeto:

```env
XGATE_API_KEY=your-api-key-here
XGATE_BASE_URL=https://api.xgate.com
XGATE_ENV=production
XGATE_TIMEOUT=30
XGATE_LOG_LEVEL=info
```

## 🚀 Guia de Início Rápido

### Exemplo Básico

```php
<?php

require_once 'vendor/autoload.php';

use XGate\XGateClient;
use XGate\Exception\AuthenticationException;
use XGate\Exception\ApiException;

try {
    // 1. Inicializar o cliente
    $client = new XGateClient([
        'api_key' => 'your-api-key',
        'base_url' => 'https://api.xgate.com',
    ]);

    // 2. Autenticar
    $client->authenticate('user@example.com', 'password123');

    // 3. Verificar autenticação
    if ($client->isAuthenticated()) {
        echo "✅ Autenticado com sucesso!\n";
        
        // 4. Fazer uma requisição
        $userData = $client->get('/user/profile');
        echo "Usuário: " . $userData['name'] . "\n";
    }

} catch (AuthenticationException $e) {
    echo "❌ Erro de autenticação: " . $e->getMessage() . "\n";
} catch (ApiException $e) {
    echo "❌ Erro da API: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}
```

## 🔐 Autenticação

### Login com Email e Senha

```php
<?php

use XGate\Exception\AuthenticationException;

try {
    $success = $client->authenticate('user@example.com', 'password123');
    
    if ($success) {
        echo "Login realizado com sucesso!";
        
        // Token é gerenciado automaticamente
        // Todas as próximas requisições usarão o token automaticamente
    }
    
} catch (AuthenticationException $e) {
    switch ($e->getCode()) {
        case 401:
            echo "Credenciais inválidas";
            break;
        case 429:
            echo "Muitas tentativas de login. Tente novamente em alguns minutos.";
            break;
        default:
            echo "Erro de autenticação: " . $e->getMessage();
    }
}
```

### Verificação de Autenticação

```php
<?php

// Verificar se está autenticado
if ($client->isAuthenticated()) {
    // Fazer operações que requerem autenticação
    $data = $client->get('/protected-endpoint');
} else {
    // Redirecionar para login ou autenticar
    $client->authenticate($email, $password);
}
```

### Logout

```php
<?php

// Fazer logout (limpa token e cache)
$client->logout();
echo "Logout realizado com sucesso!";
```

## 🔧 Funcionalidades da API

### Status das Funcionalidades

| Módulo | Status | Descrição |
|--------|--------|-----------|
| ✅ **Autenticação** | Completo | Login JWT, renovação automática de tokens |
| ✅ **Clientes** | Completo | CRUD completo de clientes |
| ✅ **PIX** | Completo | Criação e gestão de chaves PIX |
| ✅ **Depósitos FIAT** | Completo | Criação e consulta de depósitos |
| ✅ **Saques FIAT** | Completo | Processamento de saques via PIX |
| 🔄 **Operações Cripto** | Em desenvolvimento | Carteiras e saques cripto |
| 🔄 **Conversões** | Em desenvolvimento | Conversões entre moedas |

### Legenda
- ✅ **Completo** - Funcionalidade implementada e testada
- 🔄 **Em desenvolvimento** - Funcionalidade em progresso
- ⏳ **Planejado** - Funcionalidade planejada

## 📖 Exemplos de Uso

### Gestão de Clientes

```php
<?php

use XGate\Resource\CustomerResource;
use XGate\Exception\ValidationException;

// Obter resource de clientes
$customerResource = new CustomerResource($client->getHttpClient(), $client->getLogger());

try {
    // Criar novo cliente
    $customer = $customerResource->create(
        name: 'João Silva',
        email: 'joao@example.com',
        phone: '+5511999999999',
        document: '12345678901',
        documentType: 'cpf',
        metadata: [
            'source' => 'website',
            'campaign' => 'summer2024'
        ]
    );
    
    echo "Cliente criado: {$customer->name} (ID: {$customer->id})\n";
    
    // Buscar cliente por ID
    $foundCustomer = $customerResource->get($customer->id);
    echo "Cliente encontrado: {$foundCustomer->email}\n";
    
    // Atualizar cliente
    $updatedCustomer = $customerResource->update($customer->id, [
        'phone' => '+5511888888888',
        'metadata' => ['updated' => true]
    ]);
    
    // Listar clientes com paginação
    $customers = $customerResource->list(page: 1, limit: 10, filters: [
        'document_type' => 'cpf'
    ]);
    
    echo "Total de clientes: " . count($customers) . "\n";
    
} catch (ValidationException $e) {
    echo "Erro de validação:\n";
    foreach ($e->getValidationErrors() as $field => $errors) {
        echo "- {$field}: " . implode(', ', $errors) . "\n";
    }
}
```

### Sistema PIX

```php
<?php

use XGate\Resource\PixResource;

$pixResource = new PixResource($client->getHttpClient(), $client->getLogger());

try {
    // Criar chave PIX
    $pixKey = $pixResource->create(
        customerId: $customer->id,
        keyType: 'cpf',
        keyValue: '12345678901'
    );
    
    echo "Chave PIX criada: {$pixKey->keyValue}\n";
    
    // Listar chaves PIX do cliente
    $pixKeys = $pixResource->listByCustomer($customer->id);
    
    foreach ($pixKeys as $key) {
        echo "- {$key->keyType}: {$key->keyValue} (Status: {$key->status})\n";
    }
    
    // Validar chave PIX
    $isValid = $pixResource->validate('user@example.com', 'email');
    echo $isValid ? "Chave válida" : "Chave inválida";
    
} catch (ApiException $e) {
    echo "Erro ao gerenciar PIX: " . $e->getMessage();
}
```

### Depósitos FIAT

```php
<?php

use XGate\Resource\DepositResource;

$depositResource = new DepositResource($client->getHttpClient(), $client->getLogger());

try {
    // Listar moedas disponíveis para depósito
    $currencies = $depositResource->getAvailableCurrencies();
    
    echo "Moedas disponíveis para depósito:\n";
    foreach ($currencies as $currency) {
        echo "- {$currency['code']}: {$currency['name']} (Min: {$currency['min_amount']})\n";
    }
    
    // Criar depósito
    $deposit = $depositResource->create(
        customerId: $customer->id,
        amount: 100.50,
        currency: 'BRL',
        paymentMethod: 'pix',
        metadata: [
            'reference' => 'ORDER-12345'
        ]
    );
    
    echo "Depósito criado:\n";
    echo "- ID: {$deposit->id}\n";
    echo "- Valor: {$deposit->amount} {$deposit->currency}\n";
    echo "- Status: {$deposit->status}\n";
    echo "- PIX: {$deposit->pixCode}\n";
    
    // Consultar status do depósito
    $depositStatus = $depositResource->getStatus($deposit->id);
    echo "Status atual: {$depositStatus->status}\n";
    
} catch (ApiException $e) {
    echo "Erro no depósito: " . $e->getMessage();
}
```

### Saques FIAT

```php
<?php

use XGate\Resource\WithdrawResource;

$withdrawResource = new WithdrawResource($client->getHttpClient(), $client->getLogger());

try {
    // Listar moedas disponíveis para saque
    $currencies = $withdrawResource->getAvailableCurrencies();
    
    // Criar saque via PIX
    $withdrawal = $withdrawResource->create(
        customerId: $customer->id,
        amount: 50.00,
        currency: 'BRL',
        pixKey: 'joao@example.com',
        pixKeyType: 'email'
    );
    
    echo "Saque criado:\n";
    echo "- ID: {$withdrawal->id}\n";
    echo "- Valor: {$withdrawal->amount} {$withdrawal->currency}\n";
    echo "- PIX: {$withdrawal->pixKey}\n";
    echo "- Status: {$withdrawal->status}\n";
    
    // Consultar histórico de saques
    $withdrawals = $withdrawResource->listByCustomer($customer->id);
    
    foreach ($withdrawals as $w) {
        echo "Saque {$w->id}: {$w->amount} {$w->currency} - {$w->status}\n";
    }
    
} catch (ApiException $e) {
    echo "Erro no saque: " . $e->getMessage();
}
```

## ⚠️ Tratamento de Erros

O SDK fornece uma hierarquia robusta de exceções para diferentes tipos de erros:

### Hierarquia de Exceções

```
XGateException (base)
├── AuthenticationException (problemas de autenticação)
├── ApiException (erros da API)
│   ├── ValidationException (dados inválidos)
│   └── RateLimitException (limite de requisições)
└── NetworkException (problemas de rede)
```

### Tratamento Específico

```php
<?php

use XGate\Exception\{
    XGateException,
    AuthenticationException,
    ValidationException,
    RateLimitException,
    NetworkException,
    ApiException
};

try {
    $result = $client->post('/customers', $customerData);
    
} catch (ValidationException $e) {
    // Erro de validação - dados inválidos
    echo "Dados inválidos:\n";
    foreach ($e->getValidationErrors() as $field => $errors) {
        echo "- {$field}: " . implode(', ', $errors) . "\n";
    }
    
} catch (RateLimitException $e) {
    // Rate limit excedido
    $retryAfter = $e->getRetryAfter();
    echo "Rate limit excedido. Tente novamente em {$retryAfter} segundos.\n";
    
    // O SDK pode fazer retry automático se configurado
    if ($e->hasRetryAfter()) {
        sleep($retryAfter);
        // Tentar novamente...
    }
    
} catch (AuthenticationException $e) {
    // Problema de autenticação
    echo "Erro de autenticação: " . $e->getMessage() . "\n";
    
    // Tentar reautenticar
    $client->authenticate($email, $password);
    
} catch (NetworkException $e) {
    // Problema de rede
    echo "Erro de rede: " . $e->getMessage() . "\n";
    echo "Sugestão: " . $e->getSuggestion() . "\n";
    
    if ($e->isRetryable()) {
        $delay = $e->getRecommendedRetryDelay();
        echo "Tentando novamente em {$delay} segundos...\n";
        sleep($delay);
        // Retry logic...
    }
    
} catch (ApiException $e) {
    // Erro genérico da API
    echo "Erro da API: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getStatusCode() . "\n";
    echo "Error Code: " . $e->getApiErrorCode() . "\n";
    
} catch (XGateException $e) {
    // Erro genérico do SDK
    echo "Erro do SDK: " . $e->getMessage() . "\n";
    
    // Log context adicional
    $context = $e->getContext();
    if (!empty($context)) {
        echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
}
```

### Informações Detalhadas dos Erros

```php
<?php

try {
    $client->post('/invalid-endpoint', $data);
    
} catch (ApiException $e) {
    // Informações detalhadas do erro
    echo "Status HTTP: " . $e->getStatusCode() . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getApiErrorCode() . "\n";
    echo "Detalhes: " . json_encode($e->getErrorDetails()) . "\n";
    echo "Response body: " . $e->getResponseBody() . "\n";
    
    // Verificar tipo específico de erro
    if ($e->isValidationError()) {
        echo "Erro de validação detectado\n";
    } elseif ($e->isRateLimitError()) {
        echo "Rate limit detectado\n";
    } elseif ($e->isAuthenticationError()) {
        echo "Erro de autenticação detectado\n";
    }
}
```

## 📊 Logging e Debug

### Configuração de Logging

```php
<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;

// Logger com múltiplos handlers
$logger = new Logger('xgate-sdk');

// Log para arquivo rotativo
$logger->pushHandler(new RotatingFileHandler(
    'logs/xgate.log', 
    0, 
    Logger::INFO
));

// Log para console em desenvolvimento
if (getenv('APP_ENV') === 'development') {
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
}

// Adicionar informações de contexto
$logger->pushProcessor(new IntrospectionProcessor());

$client = new XGateClient($config, $logger);
```

### Níveis de Log Disponíveis

```php
<?php

// Configurar nível de log
$config = [
    'log_level' => 'debug', // emergency, alert, critical, error, warning, notice, info, debug
    // ... outras configurações
];
```

### Debug de Requisições

```php
<?php

// Habilitar debug detalhado
$client = new XGateClient([
    'debug' => true,
    'log_level' => 'debug',
    // ... outras configurações
]);

// Logs detalhados incluirão:
// - Headers de requisição e resposta
// - Body das requisições
// - Tempo de resposta
// - Informações de retry
// - Cache hits/misses
```

## 🧪 Testes

### Executar Testes

```bash
# Todos os testes
composer test

# Testes com cobertura
composer test-coverage

# Testes específicos
./vendor/bin/phpunit tests/Unit/CustomerResourceTest.php

# Testes de integração
./vendor/bin/phpunit tests/Integration/
```

### Análise de Código

```bash
# PHPStan (análise estática)
composer phpstan

# PHP CS Fixer (correção de estilo)
composer cs-fix

# Verificar estilo sem corrigir
composer cs-check

# Executar todas as verificações de qualidade
composer quality
```

### Exemplo de Teste

```php
<?php

use PHPUnit\Framework\TestCase;
use XGate\XGateClient;
use XGate\Exception\AuthenticationException;

class XGateClientTest extends TestCase
{
    private XGateClient $client;

    protected function setUp(): void
    {
        $this->client = new XGateClient([
            'api_key' => 'test-key',
            'base_url' => 'https://api-test.xgate.com',
            'environment' => 'test'
        ]);
    }

    public function testAuthenticationSuccess(): void
    {
        $result = $this->client->authenticate('test@example.com', 'password');
        $this->assertTrue($result);
        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testAuthenticationFailure(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->client->authenticate('invalid@example.com', 'wrong-password');
    }
}
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Este projeto segue as melhores práticas de desenvolvimento PHP.

### Processo de Contribuição

1. **Fork** o projeto
2. **Clone** o fork: `git clone https://github.com/seu-usuario/xgate-php-sdk.git`
3. **Instale** as dependências: `composer install`
4. **Crie uma branch**: `git checkout -b feature/nova-funcionalidade`
5. **Desenvolva** seguindo os padrões do projeto
6. **Execute os testes**: `composer quality`
7. **Commit** suas mudanças: `git commit -am 'feat: adiciona nova funcionalidade'`
8. **Push** para a branch: `git push origin feature/nova-funcionalidade`
9. **Abra um Pull Request**

### Padrões de Desenvolvimento

- **PHP 8.1+** com strict types
- **PSR-4** para autoloading
- **PSR-12** para estilo de código
- **PHPDoc** completo em todas as classes e métodos
- **Testes unitários** para toda funcionalidade nova
- **Conventional Commits** para mensagens de commit

### Gerenciamento de Tarefas

Este projeto utiliza o [Task Master](https://github.com/Starlord-Technologies/task-master-ai) para gerenciar o desenvolvimento:

```bash
# Ver todas as tarefas
task-master list

# Ver próxima tarefa
task-master next

# Ver detalhes de uma tarefa
task-master show <id>
```

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🆘 Suporte

### Documentação

- **README.md** - Este arquivo (visão geral e guia de início)
- **[LLMs.md](LLMs.md)** - Documentação específica para integração com IA/LLMs
- **[Wiki](https://github.com/xgate/php-sdk/wiki)** - Documentação detalhada e tutoriais
- **[PHPDoc](https://xgate.github.io/php-sdk/)** - Referência completa da API

### Canais de Suporte

- 🐛 **Issues**: [GitHub Issues](https://github.com/xgate/php-sdk/issues)
- 💬 **Discussões**: [GitHub Discussions](https://github.com/xgate/php-sdk/discussions)
- 📧 **Email**: dev@xgate.com.br
- 📚 **Wiki**: [GitHub Wiki](https://github.com/xgate/php-sdk/wiki)

### FAQ

**P: O SDK funciona em ambiente de produção da XGATE?**
R: Sim, mas lembre-se que a XGATE API opera apenas em ambiente de produção. Todas as transações são reais.

**P: Como habilitar logs detalhados?**
R: Configure `debug: true` e `log_level: 'debug'` na inicialização do cliente.

**P: O SDK suporta cache?**
R: Sim, suporte completo ao PSR-16 com cache automático de tokens e dados frequentes.

**P: Como contribuir com o projeto?**
R: Siga o [processo de contribuição](#-contribuindo) e use o Task Master para ver tarefas disponíveis.

---

**Status do Projeto**: 🚀 **Produção** - Pronto para uso

Desenvolvido com ❤️ para a comunidade PHP brasileira.

**Versão**: 1.0.0-dev | **Última atualização**: 2024 