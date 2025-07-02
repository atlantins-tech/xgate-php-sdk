# 🚀 Guia de Início Rápido - XGATE PHP SDK

Este guia te ajudará a começar a usar o XGATE PHP SDK em poucos minutos.

## 📦 Instalação Rápida

```bash
composer require xgate/php-sdk
```

## ⚡ Configuração Básica

```php
<?php
require_once 'vendor/autoload.php';

use XGate\XGateClient;

// Configuração mínima
$client = new XGateClient([
    'api_key' => 'sua-api-key-aqui',
    'environment' => 'development' // ou 'production'
]);
```

## 🏃‍♂️ Primeiros Passos

### 1. Criar um Cliente

```php
$customer = $client->customers()->create([
    'name' => 'João Silva',
    'email' => 'joao@exemplo.com',
    'document' => '12345678901',
    'document_type' => 'cpf',
    'phone' => '+5511999999999'
]);

echo "Cliente criado: " . $customer['id'];
```

### 2. Fazer um Depósito PIX

```php
$deposit = $client->pix()->createDeposit([
    'customer_id' => $customer['id'],
    'amount' => 100.00,
    'currency' => 'BRL',
    'description' => 'Depósito via PIX',
    'pix_key' => 'joao@exemplo.com',
    'pix_key_type' => 'email'
]);

echo "QR Code PIX: " . $deposit['qr_code'];
```

### 3. Fazer um Saque PIX

```php
$withdrawal = $client->pix()->createWithdrawal([
    'customer_id' => $customer['id'],
    'amount' => 50.00,
    'currency' => 'BRL',
    'description' => 'Saque via PIX',
    'pix_key' => '+5511999999999',
    'pix_key_type' => 'phone',
    'recipient_name' => 'João Silva',
    'recipient_document' => '12345678901'
]);

echo "Saque criado: " . $withdrawal['id'];
```

## 🛡️ Tratamento de Erros

```php
use XGate\Exception\{ValidationException, RateLimitException, XGateException};

try {
    $result = $client->customers()->create($data);
} catch (ValidationException $e) {
    // Erros de validação
    foreach ($e->getValidationErrors() as $field => $errors) {
        echo "Erro no campo {$field}: " . implode(', ', $errors);
    }
} catch (RateLimitException $e) {
    // Rate limit excedido
    echo "Aguarde " . $e->getRetryAfter() . " segundos";
} catch (XGateException $e) {
    // Outros erros do SDK
    echo "Erro: " . $e->getMessage();
}
```

## 📚 Próximos Passos

1. **Veja os exemplos completos** em `examples/`
2. **Leia a documentação completa** no [README.md](README.md)
3. **Configure webhooks** para receber notificações
4. **Implemente logs** para monitoramento
5. **Use cache** para melhorar performance

## 🔗 Links Úteis

- [Documentação Completa](README.md)
- [Exemplos de Código](examples/)
- [Tratamento de Erros](examples/error_handling_example.php)
- [Uso Avançado](examples/advanced_usage_example.php)

## 💡 Dicas Importantes

- ✅ **Sempre use try/catch** para capturar exceções
- ✅ **Configure logs** para debug e monitoramento
- ✅ **Use cache** para melhorar performance
- ✅ **Implemente webhooks** para notificações em tempo real
- ✅ **Teste no ambiente de desenvolvimento** antes de produção

---

**Precisa de ajuda?** Consulte os exemplos na pasta `examples/` ou a documentação completa no README.md! 