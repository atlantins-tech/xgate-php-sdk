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
use XGate\Resource\CustomerResource;
use XGate\Resource\ExchangeRateResource;
use XGate\Resource\CryptoPaymentResource;

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
     * Resource para operações de clientes
     *
     * @var CustomerResource|null
     */
    private ?CustomerResource $customerResource = null;

    /**
     * Resource para operações de taxa de câmbio
     *
     * @var ExchangeRateResource|null
     */
    private ?ExchangeRateResource $exchangeRateResource = null;

    /**
     * Resource para operações de pagamentos em criptomoedas
     *
     * @var CryptoPaymentResource|null
     */
    private ?CryptoPaymentResource $cryptoPaymentResource = null;

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
     * Obtém o resource para operações de clientes
     *
     * @return CustomerResource Resource de clientes
     *
     * @example
     * ```php
     * $customerResource = $client->getCustomerResource();
     * $customer = $customerResource->create('João Silva', 'joao@example.com');
     * ```
     */
    public function getCustomerResource(): CustomerResource
    {
        $this->ensureInitialized();

        if ($this->customerResource === null) {
            $this->customerResource = new CustomerResource($this->httpClient, $this->logger);
        }

        return $this->customerResource;
    }

    /**
     * Obtém o resource de taxas de câmbio
     *
     * Fornece acesso aos métodos de consulta de taxas de câmbio entre moedas.
     * O resource é criado sob demanda e reutilizado em chamadas subsequentes.
     *
     * @return ExchangeRateResource Resource de taxas de câmbio
     *
     * @example
     * ```php
     * $exchangeResource = $client->getExchangeRateResource();
     * $rate = $exchangeResource->getExchangeRate('BRL', 'USDT');
     * echo "1 USDT = " . $rate['rate'] . " BRL";
     * ```
     */
    public function getExchangeRateResource(): ExchangeRateResource
    {
        $this->ensureInitialized();

        if ($this->exchangeRateResource === null) {
            $this->exchangeRateResource = new ExchangeRateResource($this, $this->logger);
        }

        return $this->exchangeRateResource;
    }

    /**
     * Obtém taxa de câmbio entre duas moedas
     *
     * Método de conveniência que delega para o ExchangeRateResource.
     * Facilita o uso direto do cliente sem precisar obter o resource.
     *
     * @param string $fromCurrency Moeda de origem (ex: 'BRL', 'USD')
     * @param string $toCurrency Moeda de destino (ex: 'USDT', 'BTC')
     *
     * @return array Dados da taxa de câmbio
     *
     * @throws ApiException Se a API retornar erro
     * @throws NetworkException Se houver problema de conectividade
     *
     * @example
     * ```php
     * $rate = $client->getExchangeRate('BRL', 'USDT');
     * echo "1 USDT = " . $rate['rate'] . " BRL";
     * ```
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): array
    {
        return $this->getExchangeRateResource()->getExchangeRate($fromCurrency, $toCurrency);
    }

    /**
     * Converte um valor de uma moeda para outra
     *
     * Método de conveniência que obtém a taxa de câmbio e calcula
     * o valor convertido em uma única operação.
     *
     * @param float $amount Valor a ser convertido
     * @param string $fromCurrency Moeda de origem
     * @param string $toCurrency Moeda de destino
     *
     * @return array Resultado da conversão com valor original, taxa e valor convertido
     *
     * @throws ApiException Se a API retornar erro
     * @throws NetworkException Se houver problema de conectividade
     *
     * @example
     * ```php
     * $conversion = $client->convertAmount(100.0, 'BRL', 'USDT');
     * echo "R$ 100,00 = " . $conversion['converted_amount'] . " USDT";
     * ```
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): array
    {
        return $this->getExchangeRateResource()->convertAmount($amount, $fromCurrency, $toCurrency);
    }

    /**
     * Obtém taxa de câmbio de criptomoeda com dados detalhados
     *
     * Método de conveniência para obter informações detalhadas sobre
     * criptomoedas incluindo volume, market cap e variações.
     *
     * @param string $cryptoCurrency Criptomoeda (ex: 'USDT', 'BTC')
     * @param string $fiatCurrency Moeda fiduciária (ex: 'BRL', 'USD')
     *
     * @return array Dados detalhados da criptomoeda
     *
     * @throws ApiException Se a API retornar erro
     * @throws NetworkException Se houver problema de conectividade
     *
     * @example
     * ```php
     * $cryptoData = $client->getCryptoRate('USDT', 'BRL');
     * echo "USDT: " . $cryptoData['rate'] . " BRL (Volume 24h: " . $cryptoData['volume_24h'] . ")";
     * ```
     */
    public function getCryptoRate(string $cryptoCurrency, string $fiatCurrency): array
    {
        return $this->getExchangeRateResource()->getCryptoRate($cryptoCurrency, $fiatCurrency);
    }

    /**
     * Obtém o resource de pagamentos em criptomoedas
     *
     * Fornece acesso aos métodos de pagamento em criptomoedas como USDT.
     * O resource é criado sob demanda e reutilizado em chamadas subsequentes.
     *
     * @return CryptoPaymentResource Resource de pagamentos em criptomoedas
     *
     * @example
     * ```php
     * $cryptoResource = $client->getCryptoPaymentResource();
     * $payment = $cryptoResource->createPayment([
     *     'amount' => 100.0,
     *     'currency' => 'BRL',
     *     'crypto_currency' => 'USDT',
     *     'client_id' => 'client_123'
     * ]);
     * ```
     */
    public function getCryptoPaymentResource(): CryptoPaymentResource
    {
        $this->ensureInitialized();

        if ($this->cryptoPaymentResource === null) {
            $this->cryptoPaymentResource = new CryptoPaymentResource($this->httpClient, $this->logger);
        }

        return $this->cryptoPaymentResource;
    }

    /**
     * Cria um pagamento em criptomoeda
     *
     * Método de conveniência que delega para o CryptoPaymentResource.
     * Facilita a criação de pagamentos USDT diretamente do cliente.
     *
     * @param array $paymentData Dados do pagamento incluindo valor, moeda e detalhes
     *
     * @return array Dados do pagamento criado com endereço da carteira e QR code
     *
     * @throws ApiException Se a API retornar erro
     * @throws NetworkException Se houver problema de conectividade
     *
     * @example
     * ```php
     * $payment = $client->createPayment([
     *     'amount' => 250.00,
     *     'currency' => 'BRL',
     *     'crypto_currency' => 'USDT',
     *     'network' => 'TRC20',
     *     'client_id' => 'client_456',
     *     'order_id' => 'order_789',
     *     'description' => 'Pagamento por serviços'
     * ]);
     * 
     * echo "Endereço da carteira: " . $payment['wallet_address'];
     * echo "QR Code: " . $payment['qr_code'];
     * ```
     */
    public function createPayment(array $paymentData): array
    {
        return $this->getCryptoPaymentResource()->createPayment($paymentData);
    }

    /**
     * Obtém o status de um pagamento em criptomoeda
     *
     * Método de conveniência que verifica o status atual de um pagamento.
     * Útil para confirmar se um pagamento USDT foi processado.
     *
     * @param string $paymentId ID único do pagamento
     *
     * @return array Status do pagamento com detalhes da transação
     *
     * @throws ApiException Se a API retornar erro ou pagamento não encontrado
     * @throws NetworkException Se houver problema de conectividade
     *
     * @example
     * ```php
     * $status = $client->getPaymentStatus('pay_abc123');
     * 
     * if ($status['status'] === 'completed') {
     *     echo "Pagamento confirmado!";
     *     echo "Hash da transação: " . $status['transaction_hash'];
     * } else {
     *     echo "Pagamento ainda pendente...";
     * }
     * ```
     */
    public function getPaymentStatus(string $paymentId): array
    {
        return $this->getCryptoPaymentResource()->getPaymentStatus($paymentId);
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
                // Usa array_replace para evitar duplicação de headers
                $options['headers'] = array_replace($options['headers'] ?? [], $authHeaders);
            }

            $response = $this->httpClient->request($method, $uri, $options);
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new XGateException('Resposta da API não é um JSON válido: ' . json_last_error_msg());
            }

            return $data;
        } catch (ApiException $e) {
            throw new XGateException($e->getMessage(), $e->getCode(), $e);
        } catch (NetworkException $e) {
            throw new XGateException('Erro de rede: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new XGateException('Erro inesperado: ' . $e->getMessage(), 0, $e);
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
