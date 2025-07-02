<?php

declare(strict_types=1);

namespace XGate\Configuration;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Gerenciador de configuração do SDK da XGATE
 *
 * Esta classe é responsável por carregar e gerenciar todas as configurações
 * do SDK, incluindo credenciais da API, configurações de ambiente e opções
 * de comportamento. Suporta carregamento de variáveis de ambiente através
 * de arquivos .env e validação de configurações obrigatórias.
 *
 * @package XGate\Configuration
 * @author  XGATE Development Team
 * @since   1.0.0
 *
 * @example
 * ```php
 * // Uso básico
 * $config = new ConfigurationManager();
 * $apiKey = $config->getApiKey();
 *
 * // Com arquivo .env personalizado
 * $config = new ConfigurationManager('/custom/path/.env');
 *
 * // Com configurações manuais
 * $config = ConfigurationManager::fromArray([
 *     'api_key' => 'your-api-key',
 *     'environment' => 'production'
 * ]);
 * ```
 */
class ConfigurationManager
{
    /**
     * Chave da API XGATE
     *
     * @var string|null
     */
    private ?string $apiKey = null;

    /**
     * URL base da API XGATE
     *
     * @var string
     */
    private string $baseUrl = 'https://api.xgate.global';

    /**
     * Ambiente atual (development, production)
     *
     * @var string
     */
    private string $environment = 'production';

    /**
     * Timeout para requisições HTTP em segundos
     *
     * @var int
     */
    private int $timeout = 30;

    /**
     * Número máximo de tentativas para requisições
     *
     * @var int
     */
    private int $maxRetries = 3;

    /**
     * Habilita logs de debug
     *
     * @var bool
     */
    private bool $debugMode = false;

    /**
     * Caminho para arquivo de log personalizado
     *
     * @var string|null
     */
    private ?string $logFile = null;

    /**
     * Headers HTTP personalizados
     *
     * @var array<string, string>
     */
    private array $customHeaders = [];

    /**
     * Configurações de proxy
     *
     * @var array<string, mixed>
     */
    private array $proxySettings = [];

    /**
     * URL do proxy
     *
     * @var string|null
     */
    private ?string $proxyUrl = null;

    /**
     * Autenticação do proxy (username:password)
     *
     * @var string|null
     */
    private ?string $proxyAuth = null;

    /**
     * Indica se as configurações foram validadas
     *
     * @var bool
     */
    private bool $isValidated = false;

    /**
     * Construtor do ConfigurationManager
     *
     * @param string|null $envFile Caminho para arquivo .env personalizado
     * @param bool        $autoLoad Se deve carregar automaticamente as configurações
     *
     * @throws RuntimeException Se houver erro ao carregar o arquivo .env
     */
    public function __construct(?string $envFile = null, bool $autoLoad = true)
    {
        if ($autoLoad) {
            $this->loadFromEnvironment($envFile);
        }
    }

    /**
     * Cria uma instância a partir de um array de configurações
     *
     * @param array<string, mixed> $config Array com as configurações
     * @param bool                 $validate Se deve validar as configurações
     *
     * @return self
     *
     * @throws InvalidArgumentException Se as configurações forem inválidas
     */
    public static function fromArray(array $config, bool $validate = true): self
    {
        $instance = new self(null, false);
        $instance->loadFromArray($config);

        if ($validate) {
            $instance->validate();
        }

        return $instance;
    }

    /**
     * Carrega configurações a partir de variáveis de ambiente
     *
     * @param string|null $envFile Caminho para arquivo .env personalizado
     *
     * @throws RuntimeException Se houver erro ao carregar o arquivo .env
     */
    public function loadFromEnvironment(?string $envFile = null): void
    {
        // Carrega arquivo .env se especificado
        if ($envFile !== null) {
            if (!file_exists($envFile)) {
                throw new RuntimeException("Arquivo .env não encontrado: {$envFile}");
            }

            try {
                $dotenv = new Dotenv();
                $dotenv->loadEnv($envFile);
            } catch (\Exception $e) {
                throw new RuntimeException("Erro ao carregar arquivo .env: " . $e->getMessage(), 0, $e);
            }
        }

        // Carrega configurações das variáveis de ambiente
        $this->apiKey = $this->getEnvVar('XGATE_API_KEY');
        $this->baseUrl = $this->getEnvVar('XGATE_BASE_URL') ?? $this->baseUrl;
        $this->environment = $this->getEnvVar('XGATE_ENVIRONMENT') ?? $this->environment;
        $this->timeout = (int) $this->getEnvVar('XGATE_TIMEOUT', (string) $this->timeout);
        $this->maxRetries = (int) $this->getEnvVar('XGATE_MAX_RETRIES', (string) $this->maxRetries);
        $this->debugMode = $this->getBoolEnvVar('XGATE_DEBUG', $this->debugMode);
        $this->logFile = $this->getEnvVar('XGATE_LOG_FILE');

        // Carrega headers personalizados
        $customHeaders = $this->getEnvVar('XGATE_CUSTOM_HEADERS');
        if ($customHeaders !== null) {
            $this->customHeaders = $this->parseJsonEnvVar($customHeaders, 'XGATE_CUSTOM_HEADERS');
        }

        // Carrega configurações de proxy
        $proxySettings = $this->getEnvVar('XGATE_PROXY_SETTINGS');
        if ($proxySettings !== null) {
            $this->proxySettings = $this->parseJsonEnvVar($proxySettings, 'XGATE_PROXY_SETTINGS');
        }

        // Carrega configurações individuais de proxy
        $this->proxyUrl = $this->getEnvVar('XGATE_PROXY_URL');
        $this->proxyAuth = $this->getEnvVar('XGATE_PROXY_AUTH');

        $this->isValidated = false;
    }

    /**
     * Carrega configurações a partir de um array
     *
     * @param array<string, mixed> $config Array com as configurações
     */
    public function loadFromArray(array $config): void
    {
        $this->apiKey = $config['api_key'] ?? $this->apiKey;
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
        $this->environment = $config['environment'] ?? $this->environment;
        $this->timeout = $config['timeout'] ?? $this->timeout;
        $this->maxRetries = $config['max_retries'] ?? $this->maxRetries;
        $this->debugMode = $config['debug_mode'] ?? $this->debugMode;
        $this->logFile = $config['log_file'] ?? $this->logFile;
        $this->customHeaders = $config['custom_headers'] ?? $this->customHeaders;
        $this->proxySettings = $config['proxy_settings'] ?? $this->proxySettings;
        $this->proxyUrl = $config['proxy_url'] ?? $this->proxyUrl;
        $this->proxyAuth = $config['proxy_auth'] ?? $this->proxyAuth;

        $this->isValidated = false;
    }

    /**
     * Valida as configurações carregadas
     *
     * Verifica se todas as configurações obrigatórias estão presentes
     * e possuem valores válidos. A API key é opcional já que a autenticação
     * é feita via email/senha que gera um token de acesso.
     *
     * @throws InvalidArgumentException Se alguma configuração for inválida
     */
    public function validate(): void
    {
        $errors = [];

        // API Key é opcional - apenas valida formato se fornecida
        if ($this->apiKey !== null && !$this->isValidApiKey($this->apiKey)) {
            $errors[] = 'API Key possui formato inválido (deve ter pelo menos 32 caracteres alfanuméricos)';
        }

        // Valida Base URL
        if (!filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Base URL inválida: ' . $this->baseUrl;
        }

        // Valida ambiente
        if (!in_array($this->environment, ['development', 'production'], true)) {
            $errors[] = 'Ambiente deve ser "development" ou "production"';
        }

        // Valida timeout
        if ($this->timeout < 1 || $this->timeout > 300) {
            $errors[] = 'Timeout deve estar entre 1 e 300 segundos';
        }

        // Valida max retries
        if ($this->maxRetries < 0 || $this->maxRetries > 10) {
            $errors[] = 'Max retries deve estar entre 0 e 10';
        }

        // Valida arquivo de log
        if ($this->logFile !== null && !is_writable(dirname($this->logFile))) {
            $errors[] = 'Diretório do arquivo de log não é gravável: ' . dirname($this->logFile);
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException('Configurações inválidas: ' . implode(', ', $errors));
        }

        $this->isValidated = true;
    }

    /**
     * Retorna a chave da API (opcional)
     *
     * @return string|null A API key se fornecida, null caso contrário
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Retorna a URL base da API
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Retorna o ambiente atual
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Retorna o timeout para requisições
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Retorna o número máximo de tentativas
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Verifica se o modo debug está habilitado
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * Retorna o caminho do arquivo de log
     *
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    /**
     * Retorna os headers HTTP personalizados
     *
     * @return array<string, string>
     */
    public function getCustomHeaders(): array
    {
        return $this->customHeaders;
    }

    /**
     * Retorna as configurações de proxy
     *
     * @return array<string, mixed>
     */
    public function getProxySettings(): array
    {
        $this->ensureValidated();

        return $this->proxySettings;
    }

    /**
     * Retorna o número de tentativas de retry
     *
     * @return int
     */
    public function getRetryAttempts(): int
    {
        $this->ensureValidated();

        return $this->maxRetries;
    }

    /**
     * Retorna os headers personalizados
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        $this->ensureValidated();

        return $this->customHeaders;
    }

    /**
     * Retorna a URL do proxy
     *
     * @return string|null
     */
    public function getProxyUrl(): ?string
    {
        $this->ensureValidated();

        return $this->proxyUrl;
    }

    /**
     * Retorna as credenciais de autenticação do proxy
     *
     * @return string|null
     */
    public function getProxyAuth(): ?string
    {
        $this->ensureValidated();

        return $this->proxyAuth;
    }

    /**
     * Verifica se está em modo de produção
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Verifica se está em modo de desenvolvimento
     *
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return $this->environment === 'development';
    }

    /**
     * Retorna todas as configurações como array
     *
     * @param bool $includeSecrets Se deve incluir informações sensíveis
     *
     * @return array<string, mixed>
     */
    public function toArray(bool $includeSecrets = false): array
    {
        return [
            'api_key' => $includeSecrets ? $this->apiKey : $this->maskApiKey(),
            'base_url' => $this->baseUrl,
            'environment' => $this->environment,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries,
            'debug_mode' => $this->debugMode,
            'log_file' => $this->logFile,
            'custom_headers' => $this->customHeaders,
            'proxy_settings' => $includeSecrets ? $this->proxySettings : $this->maskProxySettings(),
            'is_validated' => $this->isValidated,
        ];
    }

    /**
     * Obtém uma variável de ambiente
     *
     * @param string      $name Nome da variável
     * @param string|null $default Valor padrão
     *
     * @return string|null
     */
    private function getEnvVar(string $name, ?string $default = null): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? $default;
        if ($value === '' || $value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Obtém uma variável de ambiente como boolean
     *
     * @param string $name Nome da variável
     * @param bool   $default Valor padrão
     *
     * @return bool
     */
    private function getBoolEnvVar(string $name, bool $default = false): bool
    {
        $value = $this->getEnvVar($name);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Analisa uma variável de ambiente JSON
     *
     * @param string $value Valor JSON
     * @param string $varName Nome da variável (para erro)
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException Se o JSON for inválido
     */
    private function parseJsonEnvVar(string $value, string $varName): array
    {
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                "Variável de ambiente {$varName} contém JSON inválido: " . json_last_error_msg()
            );
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Valida o formato da API Key
     *
     * @param string $apiKey
     *
     * @return bool
     */
    private function isValidApiKey(string $apiKey): bool
    {
        // API Keys da XGATE devem ter pelo menos 32 caracteres alfanuméricos
        return strlen($apiKey) >= 32 && preg_match('/^[a-zA-Z0-9_-]+$/', $apiKey) === 1;
    }

    /**
     * Mascara a API Key para logs/debug
     *
     * @return string|null
     */
    private function maskApiKey(): ?string
    {
        if ($this->apiKey === null) {
            return null;
        }
        
        if (strlen($this->apiKey) < 8) {
            return '***';
        }

        return substr($this->apiKey, 0, 4) . '***' . substr($this->apiKey, -4);
    }

    /**
     * Mascara configurações sensíveis do proxy
     *
     * @return array<string, mixed>
     */
    private function maskProxySettings(): array
    {
        $masked = $this->proxySettings;
        if (isset($masked['password'])) {
            $masked['password'] = '***';
        }

        return $masked;
    }

    /**
     * Garante que as configurações foram validadas
     *
     * @throws RuntimeException Se não foi validado
     */
    private function ensureValidated(): void
    {
        if (!$this->isValidated) {
            throw new RuntimeException('Configurações devem ser validadas antes do uso. Chame validate()');
        }
    }
}
