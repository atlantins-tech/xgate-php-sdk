<?php

declare(strict_types=1);

/**
 * Custom Docblock Tags for PHPStan
 * 
 * This file defines custom docblock tags that PHPStan should recognize
 * and not report as unknown annotations.
 * 
 * @package XGate\PHPStan
 */

namespace XGate\PHPStan\Stubs;

/**
 * Stub class to define custom docblock tags for PHPStan
 * 
 * This class is never instantiated - it exists only to provide
 * PHPStan with information about custom docblock tags.
 */
final class CustomDocblockTags
{
    /**
     * API tag - marks methods as part of the public API
     * 
     * @api
     */
    public const API = 'api';

    /**
     * Internal tag - marks methods as internal to the library
     * 
     * @internal
     */
    public const INTERNAL = 'internal';

    /**
     * Experimental tag - marks features as experimental
     * 
     * @experimental
     */
    public const EXPERIMENTAL = 'experimental';

    /**
     * Security tag - marks security-related code
     * 
     * @security
     */
    public const SECURITY = 'security';

    /**
     * Performance tag - marks performance-critical code
     * 
     * @performance
     */
    public const PERFORMANCE = 'performance';

    /**
     * TODO tag - marks code that needs attention
     * 
     * @todo
     */
    public const TODO = 'todo';

    /**
     * FIXME tag - marks code that needs fixing
     * 
     * @fixme
     */
    public const FIXME = 'fixme';

    /**
     * Example usage of custom tags in docblocks
     * 
     * @param string $data The input data
     * @return array<string, mixed> Processed data
     * @throws \InvalidArgumentException When data is invalid
     * 
     * @api This method is part of the public API
     * @security Validates input to prevent injection attacks
     * @performance Uses caching for frequently accessed data
     * @example
     * ```php
     * $result = $this->processData(['key' => 'value']);
     * ```
     * 
     * @since 1.0.0
     * @author XGate SDK Team
     * @see https://docs.xgate.com/api/process-data
     */
    public function exampleMethod(string $data): array
    {
        return [];
    }

    /**
     * Internal helper method
     * 
     * @param mixed $value The value to validate
     * @return bool True if valid
     * 
     * @internal This method is for internal use only
     * @todo Add more validation rules
     */
    private function validateValue(mixed $value): bool
    {
        return true;
    }

    /**
     * Experimental feature
     * 
     * @param array<string, mixed> $config Configuration array
     * @return void
     * 
     * @experimental This feature is experimental and may change
     * @fixme Need to handle edge cases better
     */
    public function experimentalFeature(array $config): void
    {
        // Implementation
    }
}

/**
 * Custom PHPStan type definitions
 * 
 * @phpstan-type XGateConfig array{
 *   api_key: string,
 *   base_url: string,
 *   timeout: int,
 *   retries: int,
 *   environment: 'sandbox'|'production'
 * }
 * 
 * @phpstan-type CustomerData array{
 *   id: string,
 *   name: string,
 *   email: string,
 *   phone?: string,
 *   document: string,
 *   address?: array{
 *     street: string,
 *     number: string,
 *     city: string,
 *     state: string,
 *     zip: string
 *   }
 * }
 * 
 * @phpstan-type PixPaymentData array{
 *   amount: float,
 *   currency: 'BRL',
 *   description: string,
 *   customer: CustomerData,
 *   metadata?: array<string, string>
 * }
 * 
 * @phpstan-type ApiResponse array{
 *   success: bool,
 *   data?: mixed,
 *   error?: array{
 *     code: string,
 *     message: string,
 *     details?: array<string, mixed>
 *   },
 *   meta?: array{
 *     timestamp: string,
 *     request_id: string,
 *     rate_limit?: array{
 *       remaining: int,
 *       reset_at: string
 *     }
 *   }
 * }
 */
final class TypeDefinitions
{
    // This class exists only for type definitions in docblocks
} 