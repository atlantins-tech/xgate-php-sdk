<?php

require_once __DIR__ . '/vendor/autoload.php';

use XGate\XGateClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

// Configuração do cliente
$config = [
    'base_url' => 'https://api.xgateglobal.com',
    'environment' => 'production',
    'debug_mode' => true,
    'timeout' => 30
];

// Credenciais para login
$email = 'metamecadmin02314@gmail.com';
$password = 'leVDtcA9BJ11xI0Dt4F2Ew6Z4B';

echo "🔍 Testando headers enviados pelo SDK\n\n";

// Container para capturar requisições
$container = [];
$history = Middleware::history($container);

// Mock handler para simular respostas
$mock = new MockHandler([
    // Resposta do login
    new Response(201, ['Content-Type' => 'application/json'], json_encode([
        'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test_token.signature'
    ])),
    // Resposta da cotação
    new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'rate' => 5.45,
        'from_currency' => 'BRL',
        'to_currency' => 'USDT'
    ]))
]);

$handlerStack = HandlerStack::create($mock);
$handlerStack->push($history);

// Cliente Guzzle customizado para capturar headers
$guzzleClient = new Client(['handler' => $handlerStack]);

try {
    // Cria o cliente XGate
    $client = new XGateClient($config);
    
    // Injeta o cliente Guzzle customizado
    $reflection = new ReflectionClass($client);
    $httpClientProperty = $reflection->getProperty('httpClient');
    $httpClientProperty->setAccessible(true);
    $httpClient = $httpClientProperty->getValue($client);
    
    // Injeta o Guzzle client customizado no HttpClient
    $httpClientReflection = new ReflectionClass($httpClient);
    $guzzleProperty = $httpClientReflection->getProperty('client');
    $guzzleProperty->setAccessible(true);
    $guzzleProperty->setValue($httpClient, $guzzleClient);
    
    echo "🔐 Fazendo login...\n";
    $authenticated = $client->authenticate($email, $password);
    
    if (!$authenticated) {
        throw new Exception("Falha na autenticação");
    }
    
    echo "✅ Login realizado!\n\n";
    
    echo "💱 Fazendo requisição de cotação...\n";
    $conversion = $client->convertAmount(100.0, 'BRL', 'USDT');
    
    echo "✅ Requisição realizada!\n\n";
    
    // Analisa as requisições capturadas
    echo "📋 Análise das requisições:\n\n";
    
    foreach ($container as $index => $transaction) {
        $request = $transaction['request'];
        echo "Requisição " . ($index + 1) . ":\n";
        echo "- Método: " . $request->getMethod() . "\n";
        echo "- URI: " . $request->getUri() . "\n";
        echo "- Headers:\n";
        
        foreach ($request->getHeaders() as $name => $values) {
            echo "  * $name: " . implode(', ', $values) . "\n";
            
            // Verifica duplicação de Authorization
            if (strtolower($name) === 'authorization' && count($values) > 1) {
                echo "  ⚠️  DUPLICAÇÃO DETECTADA! Headers Authorization: " . count($values) . "\n";
                foreach ($values as $i => $value) {
                    echo "    [$i] $value\n";
                }
            }
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📍 Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
} 