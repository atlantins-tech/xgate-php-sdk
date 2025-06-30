<?php

declare(strict_types=1);

namespace XGate;

/**
 * Cliente principal do SDK da XGATE
 *
 * Esta é uma classe básica criada para validação da estrutura do projeto.
 * Será expandida nas próximas tarefas.
 */
class XGateClient
{
    /**
     * Versão do SDK
     */
    public const VERSION = '1.0.0-dev';

    /**
     * Construtor da classe XGateClient
     */
    public function __construct()
    {
        // Implementação será adicionada nas próximas tarefas
    }

    /**
     * Retorna a versão do SDK
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }
}
