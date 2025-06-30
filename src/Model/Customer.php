<?php

declare(strict_types=1);

namespace XGate\Model;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Customer model representing a customer entity in the XGATE system
 *
 * This model provides data representation and validation for customer
 * information including personal details, contact information, and
 * system metadata like creation and update timestamps.
 *
 * @package XGate\Model
 * @author XGate PHP SDK Contributors
 *
 * @example Basic customer creation
 * ```php
 * $customer = new Customer();
 * $customer->setName('João Silva')
 *          ->setEmail('joao@example.com')
 *          ->setDocument('12345678901')
 *          ->setPhone('+5511999999999');
 *
 * if ($customer->isValid()) {
 *     echo 'Customer data is valid';
 * }
 * ```
 *
 * @example Customer with validation errors
 * ```php
 * $customer = new Customer();
 * $customer->setEmail('invalid-email'); // Invalid email format
 *
 * $violations = $customer->getValidationErrors();
 * foreach ($violations as $violation) {
 *     echo $violation->getMessage();
 * }
 * ```
 */
class Customer
{
    /**
     * Customer unique identifier
     *
     * @var int|null Unique customer ID assigned by the system
     */
    private ?int $id = null;

    /**
     * Customer full name
     *
     * @var string|null Customer's full name (required, 2-100 characters)
     */
    private ?string $name = null;

    /**
     * Customer email address
     *
     * @var string|null Valid email address for communication (required)
     */
    private ?string $email = null;

    /**
     * Customer document number
     *
     * @var string|null Document number (CPF/CNPJ for Brazil, required)
     */
    private ?string $document = null;

    /**
     * Customer phone number
     *
     * @var string|null Phone number in international format (optional)
     */
    private ?string $phone = null;

    /**
     * Customer creation timestamp
     *
     * @var DateTimeImmutable|null When the customer was created in the system
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Customer last update timestamp
     *
     * @var DateTimeImmutable|null When the customer was last updated
     */
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Validator instance for data validation
     *
     * @var ValidatorInterface|null Symfony validator instance
     */
    private ?ValidatorInterface $validator = null;

    /**
     * Create a new Customer instance
     *
     * @param array<string, mixed> $data Optional initial data to populate the customer
     *
     * @example
     * ```php
     * // Empty customer
     * $customer = new Customer();
     *
     * // Customer with initial data
     * $customer = new Customer([
     *     'name' => 'João Silva',
     *     'email' => 'joao@example.com',
     *     'document' => '12345678901'
     * ]);
     * ```
     */
    public function __construct(array $data = [])
    {
        $this->validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        if (!empty($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * Get customer ID
     *
     * @return int|null Customer unique identifier
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set customer ID
     *
     * @param int|null $id Customer unique identifier
     * @return self Returns self for method chaining
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get customer name
     *
     * @return string|null Customer full name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set customer name
     *
     * @param string|null $name Customer full name (2-100 characters)
     * @return self Returns self for method chaining
     *
     * @example
     * ```php
     * $customer->setName('João da Silva Santos');
     * ```
     */
    public function setName(?string $name): self
    {
        $this->name = $name !== null ? trim($name) : null;
        return $this;
    }

    /**
     * Get customer email
     *
     * @return string|null Customer email address
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set customer email
     *
     * @param string|null $email Valid email address
     * @return self Returns self for method chaining
     *
     * @example
     * ```php
     * $customer->setEmail('joao.silva@example.com');
     * ```
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email !== null ? strtolower(trim($email)) : null;
        return $this;
    }

    /**
     * Get customer document
     *
     * @return string|null Customer document number
     */
    public function getDocument(): ?string
    {
        return $this->document;
    }

    /**
     * Set customer document
     *
     * @param string|null $document Document number (CPF/CNPJ)
     * @return self Returns self for method chaining
     *
     * @example
     * ```php
     * $customer->setDocument('12345678901'); // CPF
     * $customer->setDocument('12345678000195'); // CNPJ
     * ```
     */
    public function setDocument(?string $document): self
    {
        $this->document = $document !== null ? preg_replace('/\D/', '', $document) : null;
        return $this;
    }

    /**
     * Get customer phone
     *
     * @return string|null Customer phone number
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Set customer phone
     *
     * @param string|null $phone Phone number in international format
     * @return self Returns self for method chaining
     *
     * @example
     * ```php
     * $customer->setPhone('+5511999999999');
     * $customer->setPhone('11999999999'); // Will be normalized
     * ```
     */
    public function setPhone(?string $phone): self
    {
        if ($phone !== null) {
            // Remove all non-digit characters
            $cleanPhone = preg_replace('/\D/', '', $phone);
            
            // Add country code if missing (assumes Brazil +55)
            if ($cleanPhone !== null && strlen($cleanPhone) === 11 && !str_starts_with($cleanPhone, '55')) {
                $cleanPhone = '55' . $cleanPhone;
            }
            
            $this->phone = $cleanPhone !== null ? '+' . $cleanPhone : null;
        } else {
            $this->phone = null;
        }
        
        return $this;
    }

    /**
     * Get customer creation timestamp
     *
     * @return DateTimeImmutable|null When the customer was created
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Set customer creation timestamp
     *
     * @param DateTimeImmutable|string|null $createdAt Creation timestamp
     * @return self Returns self for method chaining
     */
    public function setCreatedAt(DateTimeImmutable|string|null $createdAt): self
    {
        if (is_string($createdAt)) {
            $this->createdAt = new DateTimeImmutable($createdAt);
        } else {
            $this->createdAt = $createdAt;
        }
        return $this;
    }

    /**
     * Get customer last update timestamp
     *
     * @return DateTimeImmutable|null When the customer was last updated
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set customer last update timestamp
     *
     * @param DateTimeImmutable|string|null $updatedAt Last update timestamp
     * @return self Returns self for method chaining
     */
    public function setUpdatedAt(DateTimeImmutable|string|null $updatedAt): self
    {
        if (is_string($updatedAt)) {
            $this->updatedAt = new DateTimeImmutable($updatedAt);
        } else {
            $this->updatedAt = $updatedAt;
        }
        return $this;
    }

    /**
     * Validate customer data
     *
     * @return bool True if all validation rules pass
     *
     * @example
     * ```php
     * if ($customer->isValid()) {
     *     echo 'Customer data is valid';
     * } else {
     *     $errors = $customer->getValidationErrors();
     *     foreach ($errors as $error) {
     *         echo $error->getMessage();
     *     }
     * }
     * ```
     */
    public function isValid(): bool
    {
        if ($this->validator === null) {
            return false;
        }

        $violations = $this->validator->validate($this);
        return count($violations) === 0;
    }

    /**
     * Get validation errors
     *
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface List of validation violations
     */
    public function getValidationErrors()
    {
        if ($this->validator === null) {
            return [];
        }

        return $this->validator->validate($this);
    }

    /**
     * Convert customer to array representation
     *
     * @param bool $includeTimestamps Whether to include created_at and updated_at
     * @return array<string, mixed> Customer data as associative array
     *
     * @example
     * ```php
     * $data = $customer->toArray();
     * // Returns: ['id' => 1, 'name' => 'João', 'email' => 'joao@example.com', ...]
     *
     * $dataWithoutTimestamps = $customer->toArray(false);
     * // Returns: ['id' => 1, 'name' => 'João', 'email' => 'joao@example.com', ...]
     * ```
     */
    public function toArray(bool $includeTimestamps = true): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'document' => $this->document,
            'phone' => $this->phone,
        ];

        if ($includeTimestamps) {
            $data['created_at'] = $this->createdAt?->format('Y-m-d\TH:i:s\Z');
            $data['updated_at'] = $this->updatedAt?->format('Y-m-d\TH:i:s\Z');
        }

        return $data;
    }

    /**
     * Populate customer from array data
     *
     * @param array<string, mixed> $data Customer data
     * @return self Returns self for method chaining
     *
     * @example
     * ```php
     * $customer->fromArray([
     *     'name' => 'João Silva',
     *     'email' => 'joao@example.com',
     *     'document' => '12345678901'
     * ]);
     * ```
     */
    public function fromArray(array $data): self
    {
        if (isset($data['id'])) {
            $this->setId((int) $data['id']);
        }

        if (isset($data['name'])) {
            $this->setName((string) $data['name']);
        }

        if (isset($data['email'])) {
            $this->setEmail((string) $data['email']);
        }

        if (isset($data['document'])) {
            $this->setDocument((string) $data['document']);
        }

        if (isset($data['phone'])) {
            $this->setPhone((string) $data['phone']);
        }

        if (isset($data['created_at'])) {
            $this->setCreatedAt($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $this->setUpdatedAt($data['updated_at']);
        }

        return $this;
    }

    /**
     * Convert customer to JSON string
     *
     * @param bool $includeTimestamps Whether to include timestamps
     * @return string JSON representation of customer data
     * @throws \JsonException If JSON encoding fails
     *
     * @example
     * ```php
     * $json = $customer->toJson();
     * echo $json; // {"id":1,"name":"João","email":"joao@example.com",...}
     * ```
     */
    public function toJson(bool $includeTimestamps = true): string
    {
        return json_encode($this->toArray($includeTimestamps), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create customer from JSON string
     *
     * @param string $json JSON string containing customer data
     * @return self New customer instance
     * @throws \JsonException If JSON decoding fails
     *
     * @example
     * ```php
     * $json = '{"name":"João","email":"joao@example.com"}';
     * $customer = Customer::fromJson($json);
     * ```
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return new self($data);
    }

    /**
     * Load validation metadata for Symfony Validator
     *
     * @param ClassMetadata $metadata Validation metadata object
     * @return void
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        // Name validation
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'O nome é obrigatório'
        ]));
        $metadata->addPropertyConstraint('name', new Assert\Length([
            'min' => 2,
            'max' => 100,
            'minMessage' => 'O nome deve ter pelo menos {{ limit }} caracteres',
            'maxMessage' => 'O nome não pode ter mais de {{ limit }} caracteres'
        ]));

        // Email validation
        $metadata->addPropertyConstraint('email', new Assert\NotBlank([
            'message' => 'O email é obrigatório'
        ]));
        $metadata->addPropertyConstraint('email', new Assert\Email([
            'message' => 'O email "{{ value }}" não é válido'
        ]));

        // Document validation
        $metadata->addPropertyConstraint('document', new Assert\NotBlank([
            'message' => 'O documento é obrigatório'
        ]));
        $metadata->addPropertyConstraint('document', new Assert\Callback([
            'callback' => [self::class, 'validateDocument']
        ]));

        // Phone validation (optional)
        $metadata->addPropertyConstraint('phone', new Assert\Callback([
            'callback' => [self::class, 'validatePhone']
        ]));
    }

    /**
     * Validate Brazilian document (CPF or CNPJ)
     *
     * @param string|null $document Document to validate
     * @param ExecutionContextInterface $context Validation context
     * @return void
     */
    public static function validateDocument(?string $document, ExecutionContextInterface $context): void
    {
        if ($document === null || $document === '') {
            return; // NotBlank constraint will handle this
        }

        $cleanDocument = preg_replace('/\D/', '', $document);
        
        if ($cleanDocument === null) {
            $context->buildViolation('Documento inválido')
                ->addViolation();
            return;
        }

        $length = strlen($cleanDocument);

        if ($length === 11) {
            // CPF validation
            if (!self::isValidCPF($cleanDocument)) {
                $context->buildViolation('CPF inválido')
                    ->addViolation();
            }
        } elseif ($length === 14) {
            // CNPJ validation
            if (!self::isValidCNPJ($cleanDocument)) {
                $context->buildViolation('CNPJ inválido')
                    ->addViolation();
            }
        } else {
            $context->buildViolation('Documento deve ser um CPF (11 dígitos) ou CNPJ (14 dígitos)')
                ->addViolation();
        }
    }

    /**
     * Validate phone number format
     *
     * @param string|null $phone Phone number to validate
     * @param ExecutionContextInterface $context Validation context
     * @return void
     */
    public static function validatePhone(?string $phone, ExecutionContextInterface $context): void
    {
        if ($phone === null || $phone === '') {
            return; // Phone is optional
        }

        $cleanPhone = preg_replace('/\D/', '', $phone);
        
        if ($cleanPhone === null) {
            $context->buildViolation('Telefone inválido')
                ->addViolation();
            return;
        }

        $length = strlen($cleanPhone);

        // Accept Brazilian phone numbers with or without country code
        if ($length !== 10 && $length !== 11 && $length !== 12 && $length !== 13) {
            $context->buildViolation('Telefone deve ter formato válido (10-13 dígitos)')
                ->addViolation();
        }
    }

    /**
     * Validate Brazilian CPF
     *
     * @param string $cpf CPF to validate (digits only)
     * @return bool True if CPF is valid
     */
    private static function isValidCPF(string $cpf): bool
    {
        // Check for known invalid CPFs
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Calculate first verification digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $digit1) {
            return false;
        }

        // Calculate second verification digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[10] === $digit2;
    }

    /**
     * Validate Brazilian CNPJ
     *
     * @param string $cnpj CNPJ to validate (digits only)
     * @return bool True if CNPJ is valid
     */
    private static function isValidCNPJ(string $cnpj): bool
    {
        // Check for known invalid CNPJs
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Calculate first verification digit
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        // Calculate second verification digit
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cnpj[13] === $digit2;
    }
} 