<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Model\Customer;
use XGate\Resource\CustomerResource;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;

/**
 * Exemplo de uso do Customer Resource
 *
 * Este exemplo demonstra como usar o CustomerResource para realizar
 * operações CRUD (Create, Read, Update, Delete) com clientes através
 * da API XGATE.
 */

try {
    // Configuração do cliente XGATE
    $config = [
        'api_key' => 'your-xgate-api-key-here-32-chars-minimum',
        'base_url' => 'https://api.xgate.com.br',
        'environment' => 'sandbox', // ou 'production'
        'timeout' => 30,
        'debug' => true,
    ];

    // Inicializar cliente XGATE
    $client = new XGateClient($config);

    // Autenticar (se necessário)
    $client->authenticate();

    // Obter o CustomerResource
    $customerResource = new CustomerResource(
        $client->getHttpClient(),
        $client->getLogger()
    );

    echo "=== Exemplo de Uso do Customer Resource ===\n\n";

    // 1. Criar novo cliente
    echo "1. Criando novo cliente...\n";
    $newCustomer = $customerResource->create(
        name: 'João Silva',
        email: 'joao.silva@example.com',
        phone: '+5511999999999',
        document: '12345678901',
        documentType: 'cpf',
        metadata: [
            'source' => 'website',
            'campaign' => 'summer-2024',
        ]
    );

    echo "Cliente criado com sucesso!\n";
    echo "ID: {$newCustomer->id}\n";
    echo "Nome: {$newCustomer->name}\n";
    echo "Email: {$newCustomer->email}\n";
    echo "Status: {$newCustomer->status}\n\n";

    // 2. Buscar cliente por ID
    echo "2. Buscando cliente por ID...\n";
    $retrievedCustomer = $customerResource->get($newCustomer->id);
    echo "Cliente encontrado: {$retrievedCustomer->getDisplayName()}\n";
    echo "Email válido: " . ($retrievedCustomer->hasValidEmail() ? 'Sim' : 'Não') . "\n";
    echo "Ativo: " . ($retrievedCustomer->isActive() ? 'Sim' : 'Não') . "\n\n";

    // 3. Atualizar informações do cliente
    echo "3. Atualizando informações do cliente...\n";
    $updatedCustomer = $customerResource->update($newCustomer->id, [
        'name' => 'João Silva Santos',
        'phone' => '+5511888888888',
        'metadata' => [
            'source' => 'website',
            'campaign' => 'summer-2024',
            'updated_reason' => 'customer_request',
        ],
    ]);

    echo "Cliente atualizado!\n";
    echo "Novo nome: {$updatedCustomer->name}\n";
    echo "Novo telefone: {$updatedCustomer->phone}\n\n";

    // 4. Listar clientes com paginação
    echo "4. Listando clientes (página 1, limite 5)...\n";
    $customersList = $customerResource->list(page: 1, limit: 5);

    echo "Total de clientes encontrados: " . count($customersList['customers']) . "\n";
    echo "Informações de paginação:\n";
    echo "- Página atual: " . ($customersList['pagination']['page'] ?? 'N/A') . "\n";
    echo "- Total de registros: " . ($customersList['pagination']['total'] ?? 'N/A') . "\n";
    echo "- Total de páginas: " . ($customersList['pagination']['pages'] ?? 'N/A') . "\n\n";

    foreach ($customersList['customers'] as $customer) {
        echo "- {$customer->getDisplayName()} ({$customer->email})\n";
    }
    echo "\n";

    // 5. Buscar clientes por email ou nome
    echo "5. Buscando clientes por termo 'João'...\n";
    $searchResults = $customerResource->search('João', limit: 10);

    echo "Resultados da busca: " . count($searchResults) . " clientes encontrados\n";
    foreach ($searchResults as $customer) {
        echo "- {$customer->getDisplayName()} ({$customer->email})\n";
    }
    echo "\n";

    // 6. Listar clientes com filtros
    echo "6. Listando clientes ativos com filtros...\n";
    $filteredList = $customerResource->list(
        page: 1,
        limit: 10,
        filters: [
            'status' => 'active',
            'document_type' => 'cpf',
        ]
    );

    echo "Clientes ativos com CPF: " . count($filteredList['customers']) . "\n";
    foreach ($filteredList['customers'] as $customer) {
        echo "- {$customer->name} - {$customer->documentType}: {$customer->document}\n";
    }
    echo "\n";

    // 7. Demonstrar serialização JSON
    echo "7. Demonstrando serialização JSON...\n";
    $customerJson = $newCustomer->toJson();
    echo "Cliente em JSON:\n{$customerJson}\n\n";

    // Deserializar de volta
    $customerFromJson = Customer::fromJson($customerJson);
    echo "Cliente deserializado: {$customerFromJson->getDisplayName()}\n\n";

    // 8. Demonstrar conversão para array
    echo "8. Demonstrando conversão para array...\n";
    $customerArray = $newCustomer->toArray();
    echo "Campos do cliente:\n";
    foreach ($customerArray as $field => $value) {
        if (is_array($value)) {
            echo "- {$field}: " . json_encode($value, JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "- {$field}: {$value}\n";
        }
    }
    echo "\n";

    // 9. Deletar cliente (opcional - descomente se quiser testar)
    /*
    echo "9. Deletando cliente...\n";
    $deleteSuccess = $customerResource->delete($newCustomer->id);
    
    if ($deleteSuccess) {
        echo "Cliente deletado com sucesso!\n";
    } else {
        echo "Falha ao deletar cliente.\n";
    }
    */

    echo "=== Exemplo concluído com sucesso! ===\n";

} catch (ApiException $e) {
    echo "Erro da API: " . $e->getMessage() . "\n";
    echo "Código HTTP: " . $e->getHttpCode() . "\n";
    echo "Response: " . $e->getResponse() . "\n";
} catch (NetworkException $e) {
    echo "Erro de rede: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage() . "\n";
}

/**
 * Exemplo de criação manual de CustomerDTO
 */
function exemploCustomerDTO(): void
{
    echo "\n=== Exemplo de CustomerDTO Manual ===\n";

    // Criar customer do zero
    $customer = new Customer(
        id: null,
        name: 'Maria Santos',
        email: 'maria@example.com',
        phone: '+5511777777777',
        document: '98765432100',
        documentType: 'cpf',
        status: 'active'
    );

    echo "Customer criado:\n";
    echo "- Nome: {$customer->name}\n";
    echo "- Email: {$customer->email}\n";
    echo "- Telefone: {$customer->phone}\n";
    echo "- Documento: {$customer->document} ({$customer->documentType})\n";
    echo "- Status: {$customer->status}\n";
    echo "- Email válido: " . ($customer->hasValidEmail() ? 'Sim' : 'Não') . "\n";
    echo "- Ativo: " . ($customer->isActive() ? 'Sim' : 'Não') . "\n";

    // Criar customer a partir de array (simulando resposta da API)
    $apiResponse = [
        'id' => 'customer-456',
        'name' => 'Pedro Oliveira',
        'email' => 'pedro@example.com',
        'phone' => '+5511666666666',
        'document' => '11122233344',
        'document_type' => 'cpf',
        'status' => 'active',
        'created_at' => '2024-01-15T10:30:00Z',
        'updated_at' => '2024-01-15T10:30:00Z',
        'metadata' => [
            'source' => 'mobile_app',
            'version' => '1.2.0',
        ],
    ];

    $customerFromApi = Customer::fromArray($apiResponse);

    echo "\nCustomer da API:\n";
    echo "- ID: {$customerFromApi->id}\n";
    echo "- Nome: {$customerFromApi->getDisplayName()}\n";
    echo "- Criado em: " . ($customerFromApi->createdAt?->format('d/m/Y H:i:s') ?? 'N/A') . "\n";
    echo "- Metadata: " . json_encode($customerFromApi->metadata, JSON_UNESCAPED_UNICODE) . "\n";
}

// Executar exemplo adicional
exemploCustomerDTO(); 