# Testes de Integração - SDK XGATE

Este diretório contém arquivos para testar o SDK da XGATE com credenciais reais, validando o fluxo completo de cadastro de cliente e geração de depósitos por cripto.

## 📋 Arquivos Disponíveis

### 1. `integration_test.php`
Arquivo principal de teste que executa o fluxo completo do SDK:
- ✅ Autenticação com credenciais reais
- ✅ Cadastro de cliente de teste
- ✅ Geração de depósito
- ✅ Verificação de status do depósito
- ✅ Listagem de moedas suportadas
- ✅ Busca de cliente criado
- ✅ Logout seguro

### 2. `config.example.php`
Arquivo de exemplo de configuração com todas as variáveis necessárias.

### 3. `../tests/Integration/XGateIntegrationTest.php`
Teste unitário automatizado usando PHPUnit para validação contínua.

## 🚀 Como Executar

### Pré-requisitos
1. Credenciais válidas da XGATE (email e senha)
2. PHP 8.1 ou superior
3. Composer instalado
4. Dependências do projeto instaladas (`composer install`)

### Configuração

#### Opção 1: Arquivo .env (Recomendado)
1. Crie um arquivo `.env` na raiz do projeto:
```bash
# Credenciais obrigatórias
XGATE_EMAIL=seu-email@xgate.com
XGATE_PASSWORD=sua-senha-secreta

# Configurações opcionais
XGATE_API_KEY=sua-api-key
XGATE_BASE_URL=https://api.xgate.com
XGATE_ENVIRONMENT=development
```

#### Opção 2: Variáveis de Ambiente
```bash
export XGATE_EMAIL="seu-email@xgate.com"
export XGATE_PASSWORD="sua-senha-secreta"
```

### Executando o Teste Manual

```bash
# Navegar para o diretório do projeto
cd /caminho/para/xgate-php-sdk

# Executar o teste de integração
php examples/integration_test.php
```

### Executando os Testes Automatizados

```bash
# Executar todos os testes de integração
./vendor/bin/phpunit tests/Integration/

# Executar apenas o teste de integração da XGATE
./vendor/bin/phpunit tests/Integration/XGateIntegrationTest.php

# Executar com mais detalhes
./vendor/bin/phpunit tests/Integration/XGateIntegrationTest.php --verbose

# Executar apenas o grupo de testes de integração
./vendor/bin/phpunit --group integration
```

## 📊 O Que é Testado

### Fluxo Principal
1. **Inicialização do Cliente**
   - Configuração correta do SDK
   - Validação da versão
   - Verificação dos componentes internos

2. **Autenticação**
   - Login com credenciais reais
   - Verificação de status de autenticação
   - Tratamento de erros de autenticação

3. **Gerenciamento de Clientes**
   - Cadastro de novo cliente
   - Busca de cliente por ID
   - Validação de dados do cliente

4. **Operações de Depósito**
   - Criação de depósito via cripto
   - Verificação de status da transação
   - Listagem de moedas suportadas

5. **Segurança**
   - Logout seguro
   - Limpeza de sessão

### Cenários de Erro
- Credenciais inválidas
- Dados malformados
- Problemas de conectividade
- Validações de API

## 🔧 Configurações Avançadas

### Variáveis de Ambiente Disponíveis

| Variável | Obrigatória | Padrão | Descrição |
|----------|-------------|--------|-----------|
| `XGATE_EMAIL` | ✅ | - | Email de autenticação |
| `XGATE_PASSWORD` | ✅ | - | Senha de autenticação |
| `XGATE_API_KEY` | ❌ | `test-api-key` | Chave da API |
| `XGATE_BASE_URL` | ❌ | `https://api.xgate.com` | URL base da API |
| `XGATE_ENVIRONMENT` | ❌ | `development` | Ambiente (development/production) |
| `XGATE_TIMEOUT` | ❌ | `30` | Timeout em segundos |
| `XGATE_DEBUG` | ❌ | `true` | Habilitar logs de debug |

### Personalização dos Testes

Você pode personalizar os dados de teste editando as variáveis no arquivo `integration_test.php`:

```php
// Personalizar dados do cliente
$customerData = [
    'name' => 'Seu Nome Teste',
    'email' => 'seu.email.teste@exemplo.com',
    'phone' => '+5511999999999',
    'document' => '12345678901',
    'documentType' => 'cpf',
];

// Personalizar dados do depósito
$depositAmount = '250.00';
$depositCurrency = 'BRL';
```

## 🛡️ Segurança

### Boas Práticas
- ✅ Nunca commite credenciais reais no código
- ✅ Use variáveis de ambiente para dados sensíveis
- ✅ Execute testes apenas em ambiente de desenvolvimento
- ✅ Monitore os logs para detectar problemas
- ✅ Limpe dados de teste após execução

### Dados Sensíveis
- Credenciais são mascaradas nos logs
- Valores monetários são parcialmente ocultados
- IDs de cliente são tratados com cuidado

## 📈 Interpretando os Resultados

### Saída Esperada
```
=== Teste de Integração - SDK XGATE ===

📧 Email configurado: seu***@exemplo.com
🔑 Senha configurada: **********

1. 🚀 Inicializando cliente XGATE...
   ✅ Cliente inicializado com sucesso!
   📦 Versão do SDK: 1.0.0-dev
   🌐 Base URL: https://api.xgate.com
   🔧 Ambiente: development

2. 🔐 Realizando autenticação...
   ✅ Autenticação realizada com sucesso!
   👤 Usuário autenticado: seu-email@exemplo.com

[... continua com todos os passos ...]

🎉 TESTE DE INTEGRAÇÃO CONCLUÍDO COM SUCESSO!
```

### Códigos de Saída
- `0`: Teste executado com sucesso
- `1`: Erro de configuração ou falha no teste

## 🐛 Solução de Problemas

### Problemas Comuns

#### Credenciais não encontradas
```
❌ Erro: Credenciais não encontradas!
```
**Solução**: Configure `XGATE_EMAIL` e `XGATE_PASSWORD` no arquivo `.env`

#### Erro de autenticação
```
❌ Erro de autenticação: Invalid credentials
```
**Solução**: Verifique se suas credenciais estão corretas na XGATE

#### Erro de conectividade
```
❌ Erro de rede: Connection timeout
```
**Solução**: Verifique sua conexão com a internet e a URL da API

#### Erro de API
```
❌ Erro da API XGATE: Invalid request format
```
**Solução**: Verifique se a versão do SDK é compatível com a API

### Debug Avançado

Para mais informações de debug, defina:
```bash
export XGATE_DEBUG=true
export XGATE_LOG_LEVEL=debug
```

## 📞 Suporte

Se você encontrar problemas:

1. Verifique se todas as dependências estão instaladas
2. Confirme que suas credenciais estão corretas
3. Execute os testes em ambiente de desenvolvimento
4. Consulte os logs para mais detalhes
5. Abra uma issue no repositório do projeto

## 🔄 Integração Contínua

Para usar estes testes em CI/CD:

```yaml
# Exemplo para GitHub Actions
env:
  XGATE_EMAIL: ${{ secrets.XGATE_EMAIL }}
  XGATE_PASSWORD: ${{ secrets.XGATE_PASSWORD }}

script:
  - composer install
  - ./vendor/bin/phpunit tests/Integration/ --group integration
```

## 📝 Próximos Passos

Após executar os testes com sucesso:

1. ✅ Implemente testes automatizados em sua pipeline
2. ✅ Customize os testes para seus casos de uso específicos
3. ✅ Monitore as transações de teste no painel da XGATE
4. ✅ Desenvolva testes para cenários de edge cases
5. ✅ Implemente testes de performance e carga 