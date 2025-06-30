<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XGate\XGateClient;
use XGate\Model\PixKey;
use XGate\Exception\ApiException;
use XGate\Exception\NetworkException;

/**
 * PIX Resource Usage Example
 *
 * This example demonstrates how to use the PixResource to manage PIX keys
 * through the XGATE API, including registering, retrieving, updating,
 * and listing PIX keys with proper error handling.
 */

try {
    // Initialize XGATE client with configuration
    $client = new XGateClient([
        'api_key' => 'your-api-key-here',
        'base_url' => 'https://api.xgate.com.br',
        'environment' => 'production', // or 'sandbox'
        'timeout' => 30,
        'debug' => true
    ]);

    // Authenticate with the API
    $client->authenticate('username', 'password');

    echo "=== XGATE PIX Resource Usage Example ===\n\n";

    // 1. Register a new PIX key (Email)
    echo "1. Registering new email PIX key...\n";
    
    $newPixKey = $client->pix()->register(
        type: 'email',
        key: 'joao.silva@example.com',
        accountHolderName: 'João Silva',
        accountHolderDocument: '12345678901',
        bankCode: '001',
        accountNumber: '12345-6',
        accountType: 'checking',
        metadata: [
            'registration_source' => 'sdk_example',
            'customer_id' => 'customer-123'
        ]
    );

    echo "PIX key registered successfully!\n";
    echo "ID: {$newPixKey->id}\n";
    echo "Type: {$newPixKey->type}\n";
    echo "Key: {$newPixKey->key}\n";
    echo "Status: {$newPixKey->status}\n";
    echo "Account Holder: {$newPixKey->accountHolderName}\n";
    echo "Display Name: {$newPixKey->getDisplayName()}\n\n";

    // 2. Register a CPF PIX key
    echo "2. Registering CPF PIX key...\n";
    
    $cpfPixKey = $client->pix()->register(
        type: 'cpf',
        key: '12345678901',
        accountHolderName: 'Maria Santos',
        accountHolderDocument: '12345678901',
        bankCode: '001'
    );

    echo "CPF PIX key registered!\n";
    echo "ID: {$cpfPixKey->id}\n";
    echo "Display Name: {$cpfPixKey->getDisplayName()}\n\n";

    // 3. Register a Phone PIX key
    echo "3. Registering phone PIX key...\n";
    
    $phonePixKey = $client->pix()->register(
        type: 'phone',
        key: '+5511999999999',
        accountHolderName: 'Carlos Oliveira'
    );

    echo "Phone PIX key registered!\n";
    echo "Display Name: {$phonePixKey->getDisplayName()}\n\n";

    // 4. Register a Random UUID PIX key
    echo "4. Registering random PIX key...\n";
    
    $randomPixKey = $client->pix()->register(
        type: 'random',
        key: '123e4567-e89b-12d3-a456-426614174000',
        accountHolderName: 'Ana Costa'
    );

    echo "Random PIX key registered!\n";
    echo "Display Name: {$randomPixKey->getDisplayName()}\n\n";

    // 5. Get PIX key by ID
    echo "5. Retrieving PIX key by ID...\n";
    
    $retrievedPixKey = $client->pix()->get($newPixKey->id);
    
    echo "PIX key retrieved:\n";
    echo "ID: {$retrievedPixKey->id}\n";
    echo "Type: {$retrievedPixKey->type}\n";
    echo "Key: {$retrievedPixKey->key}\n";
    echo "Status: {$retrievedPixKey->status}\n\n";

    // 6. Update PIX key information
    echo "6. Updating PIX key information...\n";
    
    $updatedPixKey = $client->pix()->update($newPixKey->id, [
        'account_holder_name' => 'João da Silva Santos',
        'metadata' => [
            'registration_source' => 'sdk_example',
            'customer_id' => 'customer-123',
            'updated_reason' => 'name_change',
            'updated_at' => date('c')
        ]
    ]);

    echo "PIX key updated successfully!\n";
    echo "New account holder name: {$updatedPixKey->accountHolderName}\n\n";

    // 7. List all PIX keys with pagination
    echo "7. Listing PIX keys (first page)...\n";
    
    $pixKeys = $client->pix()->list(page: 1, limit: 10);
    
    echo "Found " . count($pixKeys) . " PIX keys:\n";
    foreach ($pixKeys as $pixKey) {
        echo "- {$pixKey->getDisplayName()} ({$pixKey->type}) - Status: {$pixKey->status}\n";
    }
    echo "\n";

    // 8. List PIX keys with filters
    echo "8. Listing active email PIX keys...\n";
    
    $activeEmailPixKeys = $client->pix()->list(
        page: 1,
        limit: 5,
        filters: [
            'type' => 'email',
            'status' => 'active'
        ]
    );

    echo "Found " . count($activeEmailPixKeys) . " active email PIX keys:\n";
    foreach ($activeEmailPixKeys as $pixKey) {
        echo "- {$pixKey->getDisplayName()} - Created: {$pixKey->createdAt?->format('Y-m-d H:i:s')}\n";
    }
    echo "\n";

    // 9. Search PIX keys
    echo "9. Searching PIX keys by domain...\n";
    
    $searchResults = $client->pix()->search('example.com', 5);
    
    echo "Found " . count($searchResults) . " PIX keys matching 'example.com':\n";
    foreach ($searchResults as $pixKey) {
        echo "- {$pixKey->getDisplayName()} ({$pixKey->type})\n";
    }
    echo "\n";

    // 10. Find PIX key by key value
    echo "10. Finding PIX key by value...\n";
    
    $foundPixKey = $client->pix()->findByKey('email', 'joao.silva@example.com');
    
    if ($foundPixKey !== null) {
        echo "PIX key found:\n";
        echo "ID: {$foundPixKey->id}\n";
        echo "Type: {$foundPixKey->type}\n";
        echo "Key: {$foundPixKey->key}\n";
        echo "Status: {$foundPixKey->status}\n\n";
    } else {
        echo "PIX key not found.\n\n";
    }

    // 11. Demonstrate PIX key type checking
    echo "11. Demonstrating PIX key type checking...\n";
    
    foreach ([$newPixKey, $cpfPixKey, $phonePixKey, $randomPixKey] as $pixKey) {
        echo "PIX Key: {$pixKey->getDisplayName()}\n";
        echo "  Is CPF: " . ($pixKey->isCpf() ? 'Yes' : 'No') . "\n";
        echo "  Is CNPJ: " . ($pixKey->isCnpj() ? 'Yes' : 'No') . "\n";
        echo "  Is Email: " . ($pixKey->isEmail() ? 'Yes' : 'No') . "\n";
        echo "  Is Phone: " . ($pixKey->isPhone() ? 'Yes' : 'No') . "\n";
        echo "  Is Random: " . ($pixKey->isRandom() ? 'Yes' : 'No') . "\n";
        echo "  Is Active: " . ($pixKey->isActive() ? 'Yes' : 'No') . "\n";
        echo "\n";
    }

    // 12. JSON serialization/deserialization
    echo "12. Demonstrating JSON serialization...\n";
    
    $pixKeyJson = $newPixKey->toJson();
    echo "PIX key as JSON:\n{$pixKeyJson}\n\n";
    
    $pixKeyFromJson = PixKey::fromJson($pixKeyJson);
    echo "PIX key reconstructed from JSON:\n";
    echo "ID: {$pixKeyFromJson->id}\n";
    echo "Type: {$pixKeyFromJson->type}\n";
    echo "Key: {$pixKeyFromJson->key}\n\n";

    // 13. Array conversion
    echo "13. Demonstrating array conversion...\n";
    
    $pixKeyArray = $newPixKey->toArray();
    echo "PIX key as array:\n";
    print_r($pixKeyArray);
    
    $pixKeyFromArray = PixKey::fromArray($pixKeyArray);
    echo "PIX key reconstructed from array:\n";
    echo "ID: {$pixKeyFromArray->id}\n";
    echo "Display Name: {$pixKeyFromArray->getDisplayName()}\n\n";

    // 14. Delete PIX key (commented out to avoid actually deleting)
    echo "14. Deleting PIX key (demonstration)...\n";
    /*
    $deleteSuccess = $client->pix()->delete($phonePixKey->id);
    
    if ($deleteSuccess) {
        echo "PIX key deleted successfully!\n";
    } else {
        echo "Failed to delete PIX key.\n";
    }
    */
    echo "PIX key deletion would be performed here (commented out for safety).\n\n";

    echo "=== PIX Resource Example Completed Successfully ===\n";

} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
    echo "Response Code: " . $e->getCode() . "\n";
    
    if ($e->getResponseBody()) {
        echo "Response Body: " . $e->getResponseBody() . "\n";
    }
    
    exit(1);
} catch (NetworkException $e) {
    echo "Network Error: " . $e->getMessage() . "\n";
    echo "Previous Exception: " . ($e->getPrevious() ? $e->getPrevious()->getMessage() : 'None') . "\n";
    
    exit(1);
} catch (Exception $e) {
    echo "Unexpected Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    exit(1);
} 