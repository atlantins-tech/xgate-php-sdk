<?php

declare(strict_types=1);

namespace XGate\Exception;

use Exception;

/**
 * Classe base para todas as exceções do SDK XGATE
 * 
 * Esta é a classe pai de todas as exceções específicas do SDK,
 * permitindo capturar qualquer erro relacionado ao XGATE com
 * um único catch.
 * 
 * @package XGate\Exception
 * @author XGate PHP SDK Contributors
 * @version 1.0.0
 * 
 * @example
 * ```php
 * try {
 *     // Operações do SDK
 *     $response = $client->get('/api/endpoint');
 * } catch (XGateException $e) {
 *     // Captura qualquer erro do SDK
 *     echo "Erro do XGATE SDK: " . $e->getMessage();
 * }
 * ```
 */
abstract class XGateException extends Exception
{
    /**
     * Contexto adicional da exceção
     * 
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Construtor da XGateException
     * 
     * @param string $message Mensagem de erro
     * @param int $code Código do erro
     * @param \Throwable|null $previous Exceção anterior na cadeia
     * @param array<string, mixed> $context Contexto adicional
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Obtém o contexto adicional da exceção
     * 
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Define o contexto adicional da exceção
     * 
     * @param array<string, mixed> $context
     * @return static
     */
    public function setContext(array $context): static
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Adiciona item ao contexto
     * 
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addContext(string $key, mixed $value): static
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Obtém informações básicas da exceção para logging
     * 
     * @return array<string, mixed>
     */
    public function getLogData(): array
    {
        return [
            'exception_class' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'previous' => $this->getPrevious() ? [
                'class' => get_class($this->getPrevious()),
                'message' => $this->getPrevious()->getMessage(),
                'code' => $this->getPrevious()->getCode(),
            ] : null,
        ];
    }

    /**
     * Converte a exceção para array (implementação padrão)
     * 
     * Subclasses devem sobrescrever este método para fornecer
     * informações mais específicas.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->getLogData();
    }
} 