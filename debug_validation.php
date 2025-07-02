<?php

require_once 'vendor/autoload.php';

use XGate\Exception\ValidationException;

// Teste 1: Com failedRule
$exception1 = new ValidationException('Test', [], 'email', null, 'format');
echo "Test 1 - failedRule='format': ";
var_dump($exception1->isFormatError());
echo "getMessage(): " . $exception1->getMessage() . "\n";
echo "getFailedRule(): " . $exception1->getFailedRule() . "\n\n";

// Teste 2: Com mensagem contendo 'format'
$exception2 = new ValidationException('Invalid format');
echo "Test 2 - message='Invalid format': ";
var_dump($exception2->isFormatError());
echo "getMessage(): " . $exception2->getMessage() . "\n";
echo "getFailedRule(): " . ($exception2->getFailedRule() ?? 'null') . "\n\n";

// Teste 3: Com mensagem contendo 'formato'
$exception3 = new ValidationException('Formato inválido');
echo "Test 3 - message='Formato inválido': ";
var_dump($exception3->isFormatError());
echo "getMessage(): " . $exception3->getMessage() . "\n";
echo "getFailedRule(): " . ($exception3->getFailedRule() ?? 'null') . "\n\n";

// Teste 4: Sem correspondência
$exception4 = new ValidationException('Field is required');
echo "Test 4 - message='Field is required': ";
var_dump($exception4->isFormatError());
echo "getMessage(): " . $exception4->getMessage() . "\n";
echo "getFailedRule(): " . ($exception4->getFailedRule() ?? 'null') . "\n\n"; 