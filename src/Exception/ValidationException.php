<?php

declare(strict_types=1);

namespace XGate\Exception;

/**
 * Exceção para erros de validação de entrada
 *
 * Esta exceção é lançada quando dados de entrada não atendem aos
 * requisitos de validação antes de fazer chamadas para a API.
 * Inclui informações detalhadas sobre quais campos falharam
 * na validação e os motivos específicos.
 *
 * @package XGate\Exception
 * @author XGate PHP SDK Contributors
 * @version 1.0.0
 *
 * @example
 * ```php
 * try {
 *     $transaction = new Transaction(['amount' => -100]);
 * } catch (ValidationException $e) {
 *     // Captura erros de validação
 *     echo "Erro de validação: " . $e->getMessage();
 *     print_r($e->getValidationErrors());
 * }
 * ```
 */
class ValidationException extends XGateException
{
    /**
     * Erros de validação específicos por campo
     *
     * @var array<string, array<string>>
     */
    private array $validationErrors = [];

    /**
     * Campo que falhou na validação (se aplicável)
     *
     * @var string|null
     */
    private ?string $failedField = null;

    /**
     * Valor que falhou na validação (se aplicável)
     *
     * @var mixed
     */
    private mixed $failedValue = null;

    /**
     * Regra de validação que falhou (se aplicável)
     *
     * @var string|null
     */
    private ?string $failedRule = null;

    /**
     * Construtor da ValidationException
     *
     * @param string $message Mensagem de erro principal
     * @param array<string, array<string>> $validationErrors Erros específicos por campo
     * @param string|null $failedField Campo que falhou (opcional)
     * @param mixed $failedValue Valor que falhou (opcional)
     * @param string|null $failedRule Regra que falhou (opcional)
     * @param int $code Código do erro
     * @param \Throwable|null $previous Exceção anterior na cadeia
     */
    public function __construct(
        string $message = 'Validation failed',
        array $validationErrors = [],
        ?string $failedField = null,
        mixed $failedValue = null,
        ?string $failedRule = null,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->validationErrors = $validationErrors;
        $this->failedField = $failedField;
        $this->failedValue = $failedValue;
        $this->failedRule = $failedRule;

        // Se não foi fornecida mensagem específica, gera uma baseada nos erros
        if ($message === 'Validation failed' && !empty($validationErrors)) {
            $message = $this->generateMessageFromErrors();
        }

        // Adiciona contexto específico de validação
        $context = [
            'validation_errors' => $validationErrors,
            'failed_field' => $failedField,
            'failed_value' => $this->maskSensitiveValue($failedValue),
            'failed_rule' => $failedRule,
        ];

        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Obtém todos os erros de validação organizados por campo
     *
     * @return array<string, array<string>>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Obtém erros de validação para um campo específico
     *
     * @param string $field Nome do campo
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->validationErrors[$field] ?? [];
    }

    /**
     * Obtém o primeiro erro de um campo específico
     *
     * @param string $field Nome do campo
     * @return string|null
     */
    public function getFirstFieldError(string $field): ?string
    {
        $errors = $this->getFieldErrors($field);
        return $errors[0] ?? null;
    }

    /**
     * Obtém todos os erros como uma lista simples
     *
     * @return array<string>
     */
    public function getAllErrors(): array
    {
        $allErrors = [];
        foreach ($this->validationErrors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        return $allErrors;
    }

    /**
     * Verifica se um campo específico tem erros
     *
     * @param string $field Nome do campo
     * @return bool
     */
    public function hasFieldError(string $field): bool
    {
        return !empty($this->validationErrors[$field]);
    }

    /**
     * Obtém o campo que falhou na validação
     *
     * @return string|null
     */
    public function getFailedField(): ?string
    {
        return $this->failedField;
    }

    /**
     * Obtém o valor que falhou na validação (mascarado se sensível)
     *
     * @return mixed
     */
    public function getFailedValue(): mixed
    {
        return $this->maskSensitiveValue($this->failedValue);
    }

    /**
     * Obtém a regra de validação que falhou
     *
     * @return string|null
     */
    public function getFailedRule(): ?string
    {
        return $this->failedRule;
    }

    /**
     * Adiciona um erro de validação para um campo
     *
     * @param string $field Nome do campo
     * @param string $error Mensagem de erro
     * @return static
     */
    public function addFieldError(string $field, string $error): static
    {
        if (!isset($this->validationErrors[$field])) {
            $this->validationErrors[$field] = [];
        }
        $this->validationErrors[$field][] = $error;
        
        // Atualiza o contexto
        $this->addContext('validation_errors', $this->validationErrors);
        
        return $this;
    }

    /**
     * Verifica se a exceção representa um erro de campo obrigatório
     *
     * @return bool
     */
    public function isRequiredFieldError(): bool
    {
        return $this->failedRule === 'required' || 
               stripos($this->getMessage(), 'required') !== false ||
               stripos($this->getMessage(), 'obrigatório') !== false;
    }

    /**
     * Verifica se a exceção representa um erro de formato
     *
     * @return bool
     */
    public function isFormatError(): bool
    {
        return in_array($this->failedRule, ['format', 'pattern', 'regex', 'email', 'url']) ||
               stripos($this->getMessage(), 'format') !== false ||
               stripos($this->getMessage(), 'formato') !== false;
    }

    /**
     * Verifica se a exceção representa um erro de tipo de dados
     *
     * @return bool
     */
    public function isTypeError(): bool
    {
        return in_array($this->failedRule, ['type', 'numeric', 'integer', 'string', 'boolean']) ||
               stripos($this->getMessage(), 'type') !== false ||
               stripos($this->getMessage(), 'tipo') !== false;
    }

    /**
     * Gera mensagem de erro baseada nos erros de validação
     *
     * @return string
     */
    private function generateMessageFromErrors(): string
    {
        if (empty($this->validationErrors)) {
            return 'Validation failed';
        }

        $errorCount = array_sum(array_map('count', $this->validationErrors));
        $fieldCount = count($this->validationErrors);

        if ($fieldCount === 1 && $errorCount === 1) {
            $field = array_key_first($this->validationErrors);
            $error = $this->validationErrors[$field][0];
            return "Validation failed for field '{$field}': {$error}";
        }

        return "Validation failed for {$fieldCount} field(s) with {$errorCount} error(s)";
    }

    /**
     * Mascara valores sensíveis para logs seguros
     *
     * @param mixed $value Valor a ser mascarado
     * @return mixed
     */
    private function maskSensitiveValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Lista de campos sensíveis que devem ser mascarados
        $sensitivePatterns = [
            '/password/i',
            '/secret/i', 
            '/token/i',
            '/key/i',
            '/auth/i',
            '/credential/i',
        ];

        $fieldName = $this->failedField ?? '';
        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return str_repeat('*', min(strlen($value), 8));
            }
        }

        return $value;
    }

    /**
     * Converte a exceção para array com informações de validação
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'validation_errors' => $this->validationErrors,
            'failed_field' => $this->failedField,
            'failed_value' => $this->getFailedValue(), // Já mascarado
            'failed_rule' => $this->failedRule,
            'error_count' => array_sum(array_map('count', $this->validationErrors)),
            'field_count' => count($this->validationErrors),
        ]);
    }

    /**
     * Representação em string da exceção
     *
     * @return string
     */
    public function __toString(): string
    {
        $result = parent::__toString();
        
        if (!empty($this->validationErrors)) {
            $result .= "\nValidation Errors:\n";
            foreach ($this->validationErrors as $field => $errors) {
                foreach ($errors as $error) {
                    $result .= "  - {$field}: {$error}\n";
                }
            }
        }
        
        return $result;
    }

    /**
     * Cria uma ValidationException para campo obrigatório
     *
     * @param string $field Nome do campo
     * @param mixed $value Valor fornecido (opcional)
     * @return static
     */
    public static function required(string $field, mixed $value = null): static
    {
        return new static(
            message: "The field '{$field}' is required",
            validationErrors: [$field => ['This field is required']],
            failedField: $field,
            failedValue: $value,
            failedRule: 'required'
        );
    }

    /**
     * Cria uma ValidationException para formato inválido
     *
     * @param string $field Nome do campo
     * @param mixed $value Valor fornecido
     * @param string $expectedFormat Formato esperado
     * @return static
     */
    public static function invalidFormat(string $field, mixed $value, string $expectedFormat): static
    {
        return new static(
            message: "The field '{$field}' has invalid format. Expected: {$expectedFormat}",
            validationErrors: [$field => ["Invalid format. Expected: {$expectedFormat}"]],
            failedField: $field,
            failedValue: $value,
            failedRule: 'format'
        );
    }

    /**
     * Cria uma ValidationException para tipo inválido
     *
     * @param string $field Nome do campo
     * @param mixed $value Valor fornecido
     * @param string $expectedType Tipo esperado
     * @return static
     */
    public static function invalidType(string $field, mixed $value, string $expectedType): static
    {
        $actualType = get_debug_type($value);
        return new static(
            message: "The field '{$field}' must be of type {$expectedType}, {$actualType} given",
            validationErrors: [$field => ["Must be of type {$expectedType}, {$actualType} given"]],
            failedField: $field,
            failedValue: $value,
            failedRule: 'type'
        );
    }
} 