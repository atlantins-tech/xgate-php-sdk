# Testes Avançados - SDK XGATE PHP

Este diretório contém uma suíte completa de testes avançados para o SDK XGATE PHP, incluindo testes de integração, performance e utilitários para desenvolvimento.

## 📋 Visão Geral

Os testes avançados foram criados para complementar os testes unitários existentes, fornecendo:

- **Testes de Integração Avançados**: Validação completa das operações em ambiente real
- **Testes de Performance**: Análise de desempenho, throughput e uso de recursos
- **Utilitários de Teste**: Helpers para criação de dados mock e validação

## 🗂️ Estrutura dos Arquivos

### 1. `advanced_integration_test.php`
**Teste de integração avançado com validação completa**

```php
php examples/advanced_integration_test.php
```

**Funcionalidades:**
- ✅ Autenticação e configuração automática
- ✅ Testes de operações de cliente (CRUD completo)
- ✅ Testes de operações PIX (chaves e pagamentos)
- ✅ Testes de depósitos e saques
- ✅ Validação de estruturas de dados
- ✅ Tratamento de erros e exceções
- ✅ Métricas de performance básicas
- ✅ Relatórios detalhados com estatísticas

**Exemplo de saída:**
```
=== TESTE DE INTEGRAÇÃO AVANÇADO - SDK XGATE ===

🔐 Testando Autenticação
   ✅ Autenticação bem-sucedida

👤 Testando Operações de Cliente
   ✅ Criação de cliente
   ✅ Busca de cliente
   ✅ Listagem de clientes
   ✅ Atualização de cliente

📊 RELATÓRIO DE TESTES
   Total de testes: 15
   Sucessos: 14
   Falhas: 1
   Taxa de sucesso: 93.3%
```

### 2. `unit_test_helper.php`
**Classe utilitária para testes unitários e mocks**

```php
require_once 'examples/unit_test_helper.php';
$helper = new UnitTestHelper();
```

**Funcionalidades:**
- 🔧 Geração de dados de teste únicos
- 🔧 Criação de objetos mock (Customer, Transaction, PixKey)
- 🔧 Respostas HTTP simuladas para diferentes cenários
- 🔧 Validadores (email, CPF, CNPJ, telefone)
- 🔧 Formatadores de dados
- 🔧 Logger mock para testes
- 🔧 Utilitários de limpeza

**Métodos principais:**
```php
// Gerar dados de cliente
$customerData = UnitTestHelper::generateCustomerData();

// Criar cliente mock
$customer = UnitTestHelper::createMockCustomer();

// Criar transação mock
$transaction = UnitTestHelper::createMockTransaction('deposit');

// Validar CPF
$isValid = UnitTestHelper::validateCPF('123.456.789-10');

// Criar resposta HTTP mock
$response = UnitTestHelper::createMockHttpResponse(200, ['status' => 'success']);
```

### 3. `performance_test.php`
**Teste especializado de performance e carga**

```php
php examples/performance_test.php
```

**Funcionalidades:**
- ⚡ Análise de tempo de autenticação
- ⚡ Performance de operações individuais
- ⚡ Testes de operações em lote
- ⚡ Simulação de operações concorrentes
- ⚡ Monitoramento de uso de memória
- ⚡ Teste de carga com duração configurável
- ⚡ Geração de recomendações baseadas nos resultados

**Exemplo de saída:**
```
=== TESTE DE PERFORMANCE - SDK XGATE ===

⚡ Executando: Performance de Autenticação
   📊 Tempo médio: 1,234.56ms
   ⚡ Tempo mínimo: 987.65ms
   🐌 Tempo máximo: 1,567.89ms

📊 RELATÓRIO DE PERFORMANCE
🔐 AUTENTICAÇÃO
   Tempo médio: 1,234.56ms
   Iterações: 5

💡 RECOMENDAÇÕES
✅ Tempo de autenticação aceitável
✅ Todas as operações com tempo aceitável
```

## 🚀 Como Executar

### Pré-requisitos

1. **Configurar credenciais** no arquivo `.env`:
```env
XGATE_EMAIL=seu-email@exemplo.com
XGATE_PASSWORD=sua-senha
XGATE_BASE_URL=https://api.xgate.com
XGATE_ENVIRONMENT=development
```

2. **Instalar dependências**:
```bash
composer install
```

### Executar Testes Individuais

```bash
# Teste de integração avançado
php examples/advanced_integration_test.php

# Teste de performance
php examples/performance_test.php

# Teste de integração simples (existente)
php examples/integration_test.php
```

### Executar Todos os Testes

```bash
# Executar em sequência
php examples/advanced_integration_test.php && php examples/performance_test.php
```

## 📊 Métricas e Relatórios

### Métricas de Integração
- **Taxa de Sucesso**: Percentual de testes bem-sucedidos
- **Tempo de Execução**: Duração total dos testes
- **Cobertura de Operações**: Operações testadas vs. disponíveis
- **Validação de Dados**: Estruturas de resposta validadas

### Métricas de Performance
- **Tempo de Resposta**: Latência média das operações
- **Throughput**: Operações por segundo
- **Uso de Memória**: Consumo de RAM durante execução
- **Taxa de Erro**: Percentual de operações falhadas
- **Concorrência**: Performance em operações simultâneas

## 🔧 Configurações Avançadas

### Personalizando Testes

```php
// Configurar número de iterações
$tester = new AdvancedIntegrationTester();
$tester->setIterations(10);

// Configurar timeout
$tester->setTimeout(30);

// Habilitar debug detalhado
$tester->setDebug(true);
```

### Configurações de Performance

```php
// Configurar duração do teste de carga
$performanceTester = new PerformanceTester();
$performanceTester->setLoadTestDuration(60); // 60 segundos

// Configurar tamanhos de lote
$performanceTester->setBatchSizes([5, 10, 20, 50]);
```

## 🐛 Tratamento de Erros

### Erros Comuns

1. **Credenciais Inválidas**:
```
❌ Erro: Falha na autenticação
Solução: Verificar XGATE_EMAIL e XGATE_PASSWORD no .env
```

2. **Rate Limiting**:
```
❌ Erro: Too Many Requests (429)
Solução: Aumentar delays entre operações
```

3. **Timeout de Conexão**:
```
❌ Erro: Connection timeout
Solução: Verificar XGATE_BASE_URL e conectividade
```

### Debug Avançado

```php
// Habilitar logs detalhados
$tester->setDebug(true);

// Capturar stack traces
$tester->setVerboseErrors(true);

// Salvar logs em arquivo
$tester->setLogFile('debug.log');
```

## 📈 Interpretando Resultados

### Benchmarks de Performance

| Operação | Tempo Aceitável | Tempo Crítico |
|----------|----------------|---------------|
| Autenticação | < 2000ms | > 5000ms |
| Criar Cliente | < 1000ms | > 3000ms |
| Buscar Cliente | < 500ms | > 1500ms |
| Listar Clientes | < 800ms | > 2000ms |
| Operações PIX | < 1200ms | > 3500ms |

### Uso de Memória

| Cenário | Uso Normal | Uso Crítico |
|---------|------------|-------------|
| Operações Básicas | < 5MB | > 20MB |
| Operações em Lote | < 10MB | > 50MB |
| Teste de Carga | < 15MB | > 100MB |

### Taxa de Sucesso

- **Excelente**: > 98%
- **Boa**: 95-98%
- **Aceitável**: 90-95%
- **Crítica**: < 90%

## 🔄 Integração Contínua

### GitHub Actions (exemplo)

```yaml
name: Testes Avançados

on: [push, pull_request]

jobs:
  advanced-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          
      - name: Install dependencies
        run: composer install
        
      - name: Run integration tests
        run: php examples/advanced_integration_test.php
        env:
          XGATE_EMAIL: ${{ secrets.XGATE_EMAIL }}
          XGATE_PASSWORD: ${{ secrets.XGATE_PASSWORD }}
          
      - name: Run performance tests
        run: php examples/performance_test.php
        env:
          XGATE_EMAIL: ${{ secrets.XGATE_EMAIL }}
          XGATE_PASSWORD: ${{ secrets.XGATE_PASSWORD }}
```

## 📚 Documentação Adicional

### Arquivos Relacionados
- `tests/Integration/XGateIntegrationTest.php` - Testes unitários PHPUnit
- `examples/integration_test.php` - Teste de integração básico
- `examples/config.example.php` - Configuração de exemplo
- `LLMs.md` - Documentação técnica completa

### Links Úteis
- [Documentação da API XGATE](https://docs.xgate.com)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Composer Documentation](https://getcomposer.org/doc/)

## 🤝 Contribuindo

Para contribuir com novos testes:

1. **Seguir padrões existentes**:
   - Usar português brasileiro para comentários
   - Nomes de variáveis em inglês
   - Tratamento robusto de erros

2. **Adicionar documentação**:
   - Comentários explicativos
   - Exemplos de uso
   - Métricas esperadas

3. **Testar completamente**:
   - Cenários de sucesso
   - Cenários de erro
   - Edge cases

---

## 📞 Suporte

Para dúvidas ou problemas:
- Consulte a documentação em `LLMs.md`
- Verifique os logs de debug
- Teste com credenciais válidas em ambiente de desenvolvimento

**Última atualização**: Dezembro 2024 