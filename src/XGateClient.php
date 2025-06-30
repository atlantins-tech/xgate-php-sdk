<?php

declare(strict_types=1);

namespace XGate;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use XGate\Authentication\AuthenticationManager;
use XGate\Authentication\AuthenticationManagerInterface;
use XGate\Configuration\ConfigurationManager;
use XGate\Exception\ApiException;
use XGate\Exception\AuthenticationException;
use XGate\Exception\XGateException;
use XGate\Http\HttpClient;

/**
 * Cliente principal do SDK da XGATE
 *
 * Esta é a classe principal que orquestra todas as operações do SDK,
 * fornecendo uma interface unificada para interagir com a API da XGATE.
 * Integra gerenciamento de configuração, autenticação, requisições HTTP
 * e tratamento de erros.
 *
 * @package XGate
 * @author XGate PHP SDK Contributors
 *
 * @example
 * ```php
 * // Configuração básica
 * $client = new XGateClient([
 *     'api_key' => 'your-api-key',
 *     'base_url' => 'https://api.xgate.com',
 *     'environment' => 'production'
 * ]);
 *
 * // Autenticação
 * $client->authenticate('user@example.com', 'password');
 *
 * // Fazer requisições
 * $response = $client->get('/users');
 * ```
 */
class XGateClient
{
    /**
     * Versão do SDK
     */
    public const VERSION = '1.0.0-dev';

    /**
     * Gerenciador de configuração
     *
     * @var ConfigurationManager
     */
    private ConfigurationManager $config;

    /**
     * Cliente HTTP para requisições
     *
     * @var HttpClient
     */
    private HttpClient $httpClient;

    /**
     * Gerenciador de autenticação
     *
     * @var AuthenticationManagerInterface
     */
    private AuthenticationManagerInterface $authManager;

    /**
     * Logger para registro de operações
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Cache para armazenamento de dados temporários
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Indica se o cliente foi inicializado
     *
     * @var bool
     */
    private bool $initialized = false;

    /**
     * Cria uma nova instância do XGateClient
     *
     * @param array<string, mixed>|ConfigurationManager $config Configuração do SDK ou array de configurações
     * @param LoggerInterface|null $logger Logger personalizado (opcional)
     * @param CacheInterface|null $cache Cache personalizado (opcional)
     *
     * @throws XGateException Se a configuração for inválida
     *
     * @example
     * ```php
     * // Com array de configuração
     * $client = new XGateClient([
     *     'api_key' => 'your-api-key',
     *     'base_url' => 'https://api.xgate.com'
     * ]);
     *
     * // Com ConfigurationManager personalizado
     * $config = new ConfigurationManager(['api_key' => 'key']);
     * $client = new XGateClient($config);
     * ```
     */
    public function __construct(
        array|ConfigurationManager $config,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null
    ) {
        $this->initializeConfiguration($config);
        $this->initializeCache($cache);
        $this->initializeLogger($logger);
        $this->initializeHttpClient();
        $this->initializeAuthenticationManager();
        $this->initialized = true;

        $this->logger->info('XGateClient initialized successfully', [
            'version' => self::VERSION,
            'environment' => $this->config->getEnvironment(),
            'base_url' => $this->config->getBaseUrl(),
        ]);
    }

    /**
     * Autentica o usuário com email e senha
     *
     * @param string $email Email do usuário
     * @param string $password Senha do usuário
     * @return bool True se a autenticação foi bem-sucedida
     *
     * @throws AuthenticationException Se as credenciais forem inválidas
     * @throws XGateException Se houver erro de rede ou API
     *
     * @example
     * ```php
     * try {
     *     $success = $client->authenticate('user@example.com', 'password123');
     *     if ($success) {
     *         echo "Autenticado com sucesso!";
     *     }
     * } catch (AuthenticationException $e) {
     *     echo "Erro de autenticação: " . $e->getMessage();
     * }
     * ```
     */
    public function authenticate(string $email, string $password): bool
    {
        $this->ensureInitialized();

        try {
            $success = $this->authManager->login($email, $password);

            if ($success) {
                $this->logger->info('User authenticated successfully', [
                    'email' => $email,
                ]);
            }

            return $success;
        } catch (AuthenticationException $e) {
            $this->logger->error('Authentication failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verifica se o usuário está autenticado
     *
     * @return bool True se o usuário está autenticado
     *
     * @example
     * ```php
     * if ($client->isAuthenticated()) {
     *     // Fazer operações que requerem autenticação
     *     $data = $client->get('/protected-endpoint');
     * } else {
     *     // Redirecionar para login
     *     $client->authenticate($email, $password);
     * }
     * ```
     */
    public function isAuthenticated(): bool
    {
        $this->ensureInitialized();

        return $this->authManager->isAuthenticated();
    }

    /**
     * Faz logout do usuário atual
     *
     * @return bool True se o logout foi bem-sucedido
     *
     * @example
     * ```php
     * if ($client->logout()) {
     *     echo "Logout realizado com sucesso!";
     * }
     * ```
     */
    public function logout(): bool
    {
        $this->ensureInitialized();

        $success = $this->authManager->logout();

        if ($success) {
            $this->logger->info('User logged out successfully');
        }

        return $success;
    }

    /**
     * Executa requisição GET
     *
     * @param string $uri URI da requisição (relativa à base URL)
     * @param array<string, mixed> $options Opções adicionais da requisição
     * @return array<string, mixed> Dados da resposta decodificados
     *
     * @throws XGateException Se houver erro na requisição
     *
     * @example
     * ```php
     * // Buscar lista de usuários
     * $users = $client->get('/users');
     *
     * // Com parâmetros de query
     * $users = $client->get('/users', [
     *     'query' => ['limit' => 10, 'page' => 1]
     * ]);
     * ```
     */
    public function get(string $uri, array $options = []): array
    {
        $this->ensureInitialized();

        return $this->makeRequest('GET', $uri, $options);
    }

    /**
     * Executa requisição POST
     *
     * @param string $uri URI da requisição (relativa à base URL)
     * @param array<string, mixed> $data Dados a serem enviados
     * @param array<string, mixed> $options Opções adicionais da requisição
     * @return array<string, mixed> Dados da resposta decodificados
     *
     * @throws XGateException Se houver erro na requisição
     *
     * @example
     * ```php
     * // Criar novo usuário
     * $newUser = $client->post('/users', [
     *     'name' => 'João Silva',
     *     'email' => 'joao@example.com'
     * ]);
     * ```
     */
    public function post(string $uri, array $data = [], array $options = []): array
    {
        $this->ensureInitialized();
        $options['json'] = $data;

        return $this->makeRequest('POST', $uri, $options);
    }

    /**
     * Executa requisição PUT
     *
     * @param string $uri URI da requisição (relativa à base URL)
     * @param array<string, mixed> $data Dados a serem enviados
     * @param array<string, mixed> $options Opções adicionais da requisição
     * @return array<string, mixed> Dados da resposta decodificados
     *
     * @throws XGateException Se houver erro na requisição
     *
     * @example
     * ```php
     * // Atualizar usuário
     * $updatedUser = $client->put('/users/123', [
     *     'name' => 'João Santos'
     * ]);
     * ```
     */
    public function put(string $uri, array $data = [], array $options = []): array
    {
        $this->ensureInitialized();
        $options['json'] = $data;

        return $this->makeRequest('PUT', $uri, $options);
    }

    /**
     * Executa requisição DELETE
     *
     * @param string $uri URI da requisição (relativa à base URL)
     * @param array<string, mixed> $options Opções adicionais da requisição
     * @return array<string, mixed> Dados da resposta decodificados
     *
     * @throws XGateException Se houver erro na requisição
     *
     * @example
     * ```php
     * // Deletar usuário
     * $result = $client->delete('/users/123');
     * ```
     */
    public function delete(string $uri, array $options = []): array
    {
        $this->ensureInitialized();

        return $this->makeRequest('DELETE', $uri, $options);
    }

    /**
     * Executa requisição PATCH
     *
     * @param string $uri URI da requisição (relativa à base URL)
     * @param array<string, mixed> $data Dados a serem enviados
     * @param array<string, mixed> $options Opções adicionais da requisição
     * @return array<string, mixed> Dados da resposta decodificados
     *
     * @throws XGateException Se houver erro na requisição
     *
     * @example
     * ```php
     * // Atualização parcial do usuário
     * $result = $client->patch('/users/123', [
     *     'status' => 'active'
     * ]);
     * ```
     */
    public function patch(string $uri, array $data = [], array $options = []): array
    {
        $this->ensureInitialized();
        $options['json'] = $data;

        return $this->makeRequest('PATCH', $uri, $options);
    }

    /**
     * Obtém a versão do SDK
     *
     * @return string Versão do SDK
     *
     * @example
     * ```php
     * echo "Versão do SDK: " . $client->getVersion();
     * ```
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Obtém o gerenciador de configuração
     *
     * @return ConfigurationManager Instância do gerenciador de configuração
     *
     * @example
     * ```php
     * $config = $client->getConfiguration();
     * echo "Base URL: " . $config->getBaseUrl();
     * ```
     */
    public function getConfiguration(): ConfigurationManager
    {
        return $this->config;
    }

    /**
     * Obtém o cliente HTTP
     *
     * @return HttpClient Instância do cliente HTTP
     *
     * @example
     * ```php
     * $httpClient = $client->getHttpClient();
     * $response = $httpClient->get('/custom-endpoint');
     * ```
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Obtém o gerenciador de autenticação
     *
     * @return AuthenticationManagerInterface Instância do gerenciador de autenticação
     *
     * @example
     * ```php
     * $authManager = $client->getAuthenticationManager();
     * $token = $authManager->getToken();
     * ```
     */
    public function getAuthenticationManager(): AuthenticationManagerInterface
    {
        return $this->authManager;
    }

    /**
     * Obtém o logger
     *
     * @return LoggerInterface Instância do logger
     *
     * @example
     * ```php
     * $logger = $client->getLogger();
     * $logger->info('Operação personalizada realizada');
     * ```
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Obtém o cache
     *
     * @return CacheInterface Instância do cache
     *
     * @example
     * ```php
     * $cache = $client->getCache();
     * $cache->set('custom_key', $data, 3600);
     * ```
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Verifica se o cliente está inicializado
     *
     * @return bool True se o cliente está inicializado
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Inicializa a configuração
     *
     * @param array<string, mixed>|ConfigurationManager $config
     * @throws XGateException Se a configuração for inválida
     */
    private function initializeConfiguration(array|ConfigurationManager $config): void
    {
        try {
            if ($config instanceof ConfigurationManager) {
                $this->config = $config;
            } else {
                $this->config = ConfigurationManager::fromArray($config);
            }
        } catch (\Exception $e) {
            throw new ApiException(
                'Falha ao inicializar configuração: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Inicializa o cache
     *
     * @param CacheInterface|null $cache Cache personalizado
     */
    private function initializeCache(?CacheInterface $cache): void
    {
        if ($cache !== null) {
            $this->cache = $cache;
        } else {
            // Cache padrão usando ArrayAdapter (em memória)
            $adapter = new ArrayAdapter();
            $this->cache = new Psr16Cache($adapter);
        }
    }

    /**
     * Inicializa o logger
     *
     * @param LoggerInterface|null $logger Logger personalizado
     */
    private function initializeLogger(?LoggerInterface $logger): void
    {
        if ($logger !== null) {
            $this->logger = $logger;
        } else {
            // Cria um logger padrão usando Monolog
            $this->logger = new \Monolog\Logger('xgate-sdk');
            $handler = new \Monolog\Handler\StreamHandler(
                $this->config->getLogFile() ?? 'php://stderr',
                $this->config->isDebugMode() ? \Monolog\Level::Debug : \Monolog\Level::Info
            );
            $this->logger->pushHandler($handler);
        }
    }

    /**
     * Inicializa o cliente HTTP
     */
    private function initializeHttpClient(): void
    {
        $this->httpClient = new HttpClient($this->config, $this->logger);
    }

    /**
     * Inicializa o gerenciador de autenticação
     */
    private function initializeAuthenticationManager(): void
    {
        $this->authManager = new AuthenticationManager(
            $this->httpClient,
            $this->cache
        );
    }

    /**
     * Executa uma requisição HTTP
     *
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param array<string, mixed> $options Opções da requisição
     * @return array<string, mixed> Dados da resposta decodificados
     *
     * @throws XGateException Se houver erro na requisição
     */
    private function makeRequest(string $method, string $uri, array $options): array
    {
        try {
            // Adiciona headers de autenticação se o usuário estiver autenticado
            if ($this->isAuthenticated()) {
                $authHeaders = $this->authManager->getAuthorizationHeader();
                $options['headers'] = array_merge($options['headers'] ?? [], $authHeaders);
            }

            $response = $this->httpClient->request($method, $uri, $options);
            $body = (string) $response->getBody();

            // Tenta decodificar JSON
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException(
                    'Resposta da API não é um JSON válido: ' . json_last_error_msg()
                );
            }

            return $data ?? [];
        } catch (XGateException $e) {
            // Re-lança exceções do SDK sem modificar
            throw $e;
        } catch (\Exception $e) {
            throw new ApiException(
                'Erro inesperado na requisição: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Garante que o cliente está inicializado
     *
     * @throws XGateException Se o cliente não estiver inicializado
     */
    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            throw new ApiException('XGateClient não foi inicializado corretamente');
        }
    }
}
