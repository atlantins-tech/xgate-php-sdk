<?php

declare(strict_types=1);

namespace XGate\Tests\Configuration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use XGate\Configuration\ConfigurationManager;

/**
 * Testes para o ConfigurationManager
 *
 * Esta classe testa todas as funcionalidades do gerenciador de configuração,
 * incluindo carregamento de variáveis de ambiente, validação de configurações
 * e métodos de acesso aos dados.
 */
class ConfigurationManagerTest extends TestCase
{
    /**
     * Configuração antes de cada teste
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Limpa variáveis de ambiente para cada teste
        $this->clearEnvironmentVariables();
    }

    /**
     * Limpeza após cada teste
     */
    protected function tearDown(): void
    {
        $this->clearEnvironmentVariables();
        parent::tearDown();
    }

    /**
     * Testa criação via array com configurações válidas
     */
    public function testFromArrayWithValidConfiguration(): void
    {
        $config = [
            'environment' => 'development',
            'debug_mode' => true,
            'timeout' => 60,
            'max_retries' => 5,
        ];

        $manager = ConfigurationManager::fromArray($config);

        $this->assertEquals($config['environment'], $manager->getEnvironment());
        $this->assertEquals($config['debug_mode'], $manager->isDebugMode());
        $this->assertEquals($config['timeout'], $manager->getTimeout());
        $this->assertEquals($config['max_retries'], $manager->getMaxRetries());
        $this->assertTrue($manager->isDevelopment());
        $this->assertFalse($manager->isProduction());
        $this->assertNull($manager->getApiKey()); // API key opcional
    }

    /**
     * Testa criação via array com configurações inválidas
     */
    public function testFromArrayWithInvalidConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configurações inválidas');

        ConfigurationManager::fromArray([
            'api_key' => 'short', // API key muito curta
            'environment' => 'invalid', // Ambiente inválido
        ]);
    }

    /**
     * Testa carregamento de variáveis de ambiente
     */
    public function testLoadFromEnvironment(): void
    {
        // Define variáveis de ambiente
        $_ENV['XGATE_API_KEY'] = 'env_api_key_12345678901234567890123456789012';
        $_ENV['XGATE_ENVIRONMENT'] = 'production';
        $_ENV['XGATE_DEBUG'] = 'true';
        $_ENV['XGATE_TIMEOUT'] = '45';
        $_ENV['XGATE_MAX_RETRIES'] = '5';

        $manager = new ConfigurationManager();
        $manager->validate();

        $this->assertEquals('env_api_key_12345678901234567890123456789012', $manager->getApiKey());
        $this->assertEquals('production', $manager->getEnvironment());
        $this->assertTrue($manager->isDebugMode());
        $this->assertEquals(45, $manager->getTimeout());
        $this->assertEquals(5, $manager->getMaxRetries());
        $this->assertTrue($manager->isProduction());
        $this->assertFalse($manager->isDevelopment());
    }

    /**
     * Testa carregamento de headers personalizados via JSON
     */
    public function testLoadCustomHeadersFromEnvironment(): void
    {
        $_ENV['XGATE_CUSTOM_HEADERS'] = '{"User-Agent":"XGATE-SDK/1.0","X-Custom":"value"}';

        $manager = new ConfigurationManager();
        $manager->validate();

        $expectedHeaders = [
            'User-Agent' => 'XGATE-SDK/1.0',
            'X-Custom' => 'value',
        ];

        $this->assertEquals($expectedHeaders, $manager->getCustomHeaders());
    }

    /**
     * Testa carregamento de configurações de proxy via JSON
     */
    public function testLoadProxySettingsFromEnvironment(): void
    {
        $_ENV['XGATE_PROXY_SETTINGS'] = '{"host":"proxy.example.com","port":8080,"username":"user"}';

        $manager = new ConfigurationManager();
        $manager->validate();

        $expectedProxy = [
            'host' => 'proxy.example.com',
            'port' => 8080,
            'username' => 'user',
        ];

        $this->assertEquals($expectedProxy, $manager->getProxySettings());
    }

    /**
     * Testa erro ao carregar JSON inválido
     */
    public function testInvalidJsonInEnvironmentVariable(): void
    {
        $_ENV['XGATE_CUSTOM_HEADERS'] = 'invalid-json';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('contém JSON inválido');

        new ConfigurationManager();
    }

    /**
     * Testa validação de URL base
     */
    public function testValidationBaseUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base URL inválida');

        ConfigurationManager::fromArray([
            'base_url' => 'invalid-url',
        ]);
    }

    /**
     * Testa validação de ambiente
     */
    public function testValidationEnvironment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ambiente deve ser "development" ou "production"');

        ConfigurationManager::fromArray([
            'environment' => 'staging',
        ]);
    }

    /**
     * Testa validação de timeout
     */
    public function testValidationTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout deve estar entre 1 e 300 segundos');

        ConfigurationManager::fromArray([
            'timeout' => 500, // Muito alto
        ]);
    }

    /**
     * Testa validação de max retries
     */
    public function testValidationMaxRetries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Max retries deve estar entre 0 e 10');

        ConfigurationManager::fromArray([
            'max_retries' => 15, // Muito alto
        ]);
    }

    /**
     * Testa acesso a API Key sem validação (agora permitido)
     */
    public function testGetApiKeyWithoutValidation(): void
    {
        $manager = new ConfigurationManager(null, false);
        
        // API key é opcional e pode ser acessada sem validação
        $this->assertNull($manager->getApiKey());
    }

    /**
     * Testa valores padrão
     */
    public function testDefaultValues(): void
    {
        $manager = ConfigurationManager::fromArray([]);

        $this->assertEquals('https://api.xgate.global', $manager->getBaseUrl());
        $this->assertEquals('production', $manager->getEnvironment());
        $this->assertEquals(30, $manager->getTimeout());
        $this->assertEquals(3, $manager->getMaxRetries());
        $this->assertFalse($manager->isDebugMode());
        $this->assertNull($manager->getLogFile());
        $this->assertEmpty($manager->getCustomHeaders());
        $this->assertEmpty($manager->getProxySettings());
        $this->assertTrue($manager->isProduction());
        $this->assertFalse($manager->isDevelopment());
        $this->assertNull($manager->getApiKey()); // API key é opcional
    }

    /**
     * Testa conversão para array sem dados sensíveis
     */
    public function testToArrayWithoutSecrets(): void
    {
        $manager = ConfigurationManager::fromArray([
            'api_key' => 'test_api_key_12345678901234567890123456789012',
            'proxy_settings' => ['host' => 'proxy.com', 'password' => 'secret'],
        ]);

        $array = $manager->toArray(false);

        // API Key deve estar mascarada quando fornecida
        $this->assertStringContainsString('***', $array['api_key']);
        $this->assertStringNotContainsString('test_api_key_12345678901234567890123456789012', $array['api_key']);

        // Senha do proxy deve estar mascarada
        $this->assertEquals('***', $array['proxy_settings']['password']);
    }

    /**
     * Testa conversão para array com dados sensíveis
     */
    public function testToArrayWithSecrets(): void
    {
        $apiKey = 'test_api_key_12345678901234567890123456789012';
        $proxyPassword = 'secret_password';

        $manager = ConfigurationManager::fromArray([
            'api_key' => $apiKey,
            'proxy_settings' => ['host' => 'proxy.com', 'password' => $proxyPassword],
        ]);

        $array = $manager->toArray(true);

        // Dados sensíveis devem estar visíveis quando solicitados
        $this->assertEquals($apiKey, $array['api_key']);
        $this->assertEquals($proxyPassword, $array['proxy_settings']['password']);
    }

    /**
     * Testa conversão para array sem API key
     */
    public function testToArrayWithoutApiKey(): void
    {
        $manager = ConfigurationManager::fromArray([
            'environment' => 'development',
            'debug_mode' => true,
        ]);

        $array = $manager->toArray(false);

        // API key deve ser null quando não fornecida
        $this->assertNull($array['api_key']);
        $this->assertEquals('development', $array['environment']);
        $this->assertTrue($array['debug_mode']);
    }

    /**
     * Testa parsing de variáveis boolean
     */
    public function testBooleanEnvironmentVariables(): void
    {
        /** @var array<string, bool> */
        $testCases = [
            'true' => true,
            '1' => true,
            'yes' => true,
            'on' => true,
            'false' => false,
            '0' => false,
            'no' => false,
            'off' => false,
            'invalid' => false,
        ];

        foreach ($testCases as $value => $expected) {
            $_ENV['XGATE_DEBUG'] = $value;

            $manager = new ConfigurationManager();
            $manager->validate();

            $this->assertEquals($expected, $manager->isDebugMode(), "Falha para valor: {$value}");

            $this->clearEnvironmentVariables();
        }
    }

    /**
     * Testa carregamento sem autoload
     */
    public function testConstructorWithoutAutoload(): void
    {
        $manager = new ConfigurationManager(null, false);

        // API key é opcional e pode ser acessada sem validação
        $this->assertNull($manager->getApiKey());
        
        // Configurações padrão devem estar disponíveis
        $this->assertEquals('https://api.xgate.global', $manager->getBaseUrl());
        $this->assertEquals('production', $manager->getEnvironment());
    }

    /**
     * Testa recarregamento de configurações
     */
    public function testReloadConfiguration(): void
    {
        // Primeira configuração
        $_ENV['XGATE_ENVIRONMENT'] = 'development';

        $manager = new ConfigurationManager();
        $manager->validate();

        $this->assertEquals('development', $manager->getEnvironment());

        // Muda variáveis de ambiente
        $_ENV['XGATE_ENVIRONMENT'] = 'production';

        // Recarrega configurações
        $manager->loadFromEnvironment();
        $manager->validate();

        $this->assertEquals('production', $manager->getEnvironment());
    }

    /**
     * Testa validação de formato de API Key (apenas se fornecida)
     */
    public function testValidationApiKeyFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API Key possui formato inválido');

        $manager = ConfigurationManager::fromArray([
            'api_key' => 'short_key', // Menos de 32 caracteres
        ]);
    }

    /**
     * Testa configuração de produção
     */
    public function testProductionConfiguration(): void
    {
        $manager = ConfigurationManager::fromArray([
            'environment' => 'production',
            'debug_mode' => false,
        ]);

        $this->assertTrue($manager->isProduction());
        $this->assertFalse($manager->isDevelopment());
        $this->assertFalse($manager->isDebugMode());
        $this->assertEquals('production', $manager->getEnvironment());
    }

    /**
     * Limpa todas as variáveis de ambiente relacionadas ao XGATE
     */
    private function clearEnvironmentVariables(): void
    {
        /** @var array<string> */
        $envVars = [
            'XGATE_API_KEY',
            'XGATE_BASE_URL',
            'XGATE_ENVIRONMENT',
            'XGATE_TIMEOUT',
            'XGATE_MAX_RETRIES',
            'XGATE_DEBUG',
            'XGATE_LOG_FILE',
            'XGATE_CUSTOM_HEADERS',
            'XGATE_PROXY_SETTINGS',
        ];

        foreach ($envVars as $var) {
            unset($_ENV[$var], $_SERVER[$var]);
        }
    }
}
 