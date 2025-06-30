<?php

declare(strict_types=1);

namespace XGate\Tests;

use PHPUnit\Framework\TestCase;
use XGate\XGateClient;

/**
 * Teste básico para validação da estrutura do projeto
 */
class XGateClientTest extends TestCase
{
    /**
     * Testa se a classe XGateClient pode ser instanciada
     */
    public function testCanInstantiateXGateClient(): void
    {
        $client = new XGateClient();

        $this->assertInstanceOf(XGateClient::class, $client);
    }

    /**
     * Testa se o método getVersion retorna uma string
     */
    public function testGetVersionReturnsString(): void
    {
        $client = new XGateClient();
        $version = $client->getVersion();

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }

    /**
     * Testa se a versão contém o formato esperado
     */
    public function testVersionFormat(): void
    {
        $client = new XGateClient();
        $version = $client->getVersion();

        $this->assertStringContainsString('1.0.0', $version);
    }
}
