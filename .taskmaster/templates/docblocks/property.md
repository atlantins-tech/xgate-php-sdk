# 🏷️ Property Documentation Templates

This file contains standardized PHPDoc templates for property-level documentation in the XGATE PHP SDK.

## 📋 Basic Property Template

```php
/**
 * [Brief description of what this property represents]
 *
 * [Detailed description explaining:
 * - What the property stores
 * - How it's used within the class
 * - Important constraints or validation rules
 * - Default values or initialization behavior]
 *
 * @var type [Additional details about the type and expected values]
 */
private $propertyName;
```

## 🎯 Specialized Property Templates

### Configuration Properties

```php
/**
 * [Configuration setting name] for [specific functionality]
 *
 * Controls [specific behavior or feature] within the SDK.
 * Valid values include [list of valid values or ranges].
 * Default value is set during initialization and can be overridden
 * via configuration methods or constructor parameters.
 *
 * @var string|int|bool [Expected type and valid values]
 */
private $configurationProperty;
```

### API Client Properties

```php
/**
 * HTTP client instance for API communication
 *
 * Handles all HTTP requests to the XGATE API endpoints.
 * Configured with appropriate timeouts, headers, and retry logic.
 * Should implement PSR-18 ClientInterface for compatibility.
 *
 * @var ClientInterface HTTP client for API requests
 */
private ClientInterface $httpClient;
```

```php
/**
 * Base URL for XGATE API endpoints
 *
 * The root URL for all API requests, typically ending with '/api/v1'.
 * Can be configured for different environments (sandbox, production).
 * Must include protocol (https://) and should not end with a slash.
 *
 * @var string Base API URL (e.g., 'https://api.xgate.com/v1')
 */
private string $baseUrl;
```

```php
/**
 * API authentication credentials
 *
 * Contains the API key, secret, or token required for authentication.
 * Should be kept secure and not logged or exposed in error messages.
 * Format depends on the authentication method used.
 *
 * @var array<string, string> Authentication credentials
 */
private array $credentials;
```

### Data Transfer Object Properties

```php
/**
 * Unique identifier for the [entity]
 *
 * Immutable identifier assigned by the XGATE API when the [entity]
 * is created. Format follows the pattern: [prefix]-[uuid].
 * Used for all subsequent operations on this [entity].
 *
 * @var string Unique [entity] identifier (e.g., 'customer-123e4567-e89b-12d3-a456-426614174000')
 */
private string $id;
```

```php
/**
 * Display name for the [entity]
 *
 * Human-readable name used for display purposes.
 * Must be between 1-255 characters and cannot contain special characters.
 * Can be updated after creation via the update methods.
 *
 * @var string Display name (1-255 characters, alphanumeric and spaces)
 */
private string $name;
```

```php
/**
 * Current status of the [entity]
 *
 * Indicates the current state in the [entity] lifecycle.
 * Valid values: 'active', 'inactive', 'pending', 'suspended'.
 * Status changes may trigger business logic or notifications.
 *
 * @var string Current status ('active'|'inactive'|'pending'|'suspended')
 */
private string $status;
```

```php
/**
 * Timestamp when the [entity] was created
 *
 * ISO 8601 formatted timestamp in UTC indicating when the [entity]
 * was first created in the system. This value is immutable and
 * set automatically by the API.
 *
 * @var string Creation timestamp (ISO 8601 format, UTC)
 */
private string $createdAt;
```

```php
/**
 * Timestamp when the [entity] was last updated
 *
 * ISO 8601 formatted timestamp in UTC indicating the most recent
 * modification to the [entity]. Updated automatically whenever
 * any field is changed via the API.
 *
 * @var string Last update timestamp (ISO 8601 format, UTC)
 */
private string $updatedAt;
```

### Collection Properties

```php
/**
 * Collection of [related entities]
 *
 * Array containing [Entity] objects related to this instance.
 * Loaded lazily when accessed via getter methods.
 * May be empty if no related [entities] exist.
 *
 * @var EntityDto[] Array of related [entity] objects
 */
private array $relatedEntities = [];
```

```php
/**
 * Cached API response data
 *
 * Raw response data from the last API call, stored for debugging
 * and to avoid unnecessary API requests. Data structure matches
 * the API response format exactly.
 *
 * @var array<string, mixed>|null Raw API response data or null if not cached
 */
private ?array $rawData = null;
```

### Validation Properties

```php
/**
 * Validation rules for [specific field or entity]
 *
 * Array of validation rules applied when validating [field/entity] data.
 * Each rule is a callable that receives the value and returns boolean.
 * Rules are applied in order and all must pass for validation to succeed.
 *
 * @var callable[] Array of validation rule callables
 */
private array $validationRules = [];
```

```php
/**
 * Validation error messages
 *
 * Collection of error messages generated during the last validation attempt.
 * Each message describes a specific validation failure with context.
 * Array is cleared before each validation run.
 *
 * @var string[] Array of validation error messages
 */
private array $validationErrors = [];
```

### Logger Properties

```php
/**
 * Logger instance for debugging and monitoring
 *
 * PSR-3 compatible logger for recording SDK operations, errors, and debug info.
 * Used throughout the SDK for consistent logging behavior.
 * Can be configured to different log levels and outputs.
 *
 * @var LoggerInterface PSR-3 compatible logger instance
 */
private LoggerInterface $logger;
```

### State Management Properties

```php
/**
 * Current state of the [component/service]
 *
 * Tracks the internal state of the [component] to ensure proper
 * operation sequencing and prevent invalid state transitions.
 * Valid states: [list valid states].
 *
 * @var string Current component state
 */
private string $currentState = 'initialized';
```

```php
/**
 * Flag indicating if [component] is currently processing
 *
 * Prevents concurrent operations that could cause data corruption
 * or inconsistent state. Set to true during processing operations
 * and reset to false when complete.
 *
 * @var bool True if currently processing, false otherwise
 */
private bool $isProcessing = false;
```

### Configuration Arrays

```php
/**
 * Default configuration values
 *
 * Associative array containing default values for all configurable options.
 * Used as fallback when specific configuration values are not provided.
 * Should not be modified directly; use configuration methods instead.
 *
 * @var array<string, mixed> Default configuration values
 */
private array $defaultConfig = [
    'timeout' => 30,
    'retries' => 3,
    'debug' => false,
];
```

```php
/**
 * Runtime configuration overrides
 *
 * Configuration values that override defaults for this instance.
 * Merged with default configuration to create effective configuration.
 * Can be updated via configuration methods during runtime.
 *
 * @var array<string, mixed> Runtime configuration overrides
 */
private array $configOverrides = [];
```

## 🏷️ Property Documentation Tags

### Required Tags
- `@var` - Always specify the type and brief description

### Optional Tags
- `@since` - Version when property was introduced
- `@deprecated` - Mark as deprecated with alternative
- `@internal` - Mark as internal implementation detail
- `@readonly` - Mark as read-only property
- `@api` - Mark as stable public API

### Documentation Tags
- `@see` - Reference related properties or methods
- `@link` - External documentation links

## 🎨 Property Documentation Guidelines

### Type Specifications
```php
@var string Simple scalar type
@var string|null Nullable type
@var array<string, mixed> Generic array with key/value types
@var EntityDto[] Array of specific objects
@var array{id: string, name: string} Shaped array
@var callable(string): bool Callable with signature
@var class-string<EntityInterface> Class string constraint
```

### Visibility and Access
```php
/**
 * Public property accessible directly
 * @var string
 */
public string $publicProperty;

/**
 * Protected property for inheritance
 * @var string
 */
protected string $protectedProperty;

/**
 * Private implementation detail
 * @var string
 */
private string $privateProperty;
```

### Constants
```php
/**
 * Maximum allowed value for [specific constraint]
 *
 * Used for validation and business rule enforcement.
 * Changing this value may affect API compatibility.
 *
 * @var int Maximum allowed value
 */
public const MAX_VALUE = 100;
```

### Static Properties
```php
/**
 * Shared configuration across all instances
 *
 * Global configuration that applies to all instances of this class.
 * Modified via static methods to ensure consistency.
 * Thread-safe for concurrent access.
 *
 * @var array<string, mixed> Global configuration settings
 */
private static array $globalConfig = [];
```

## 🎨 Style Guidelines

### Description Format
1. **First line**: Brief, descriptive summary
2. **Blank line**
3. **Detailed description**: Purpose, usage, constraints
4. **Blank line**
5. **@var tag**: Type and additional details

### Language Style
- Use present tense ("Contains", "Stores", "Represents")
- Be specific about data types and formats
- Explain constraints and validation rules
- Include examples of valid values when helpful
- Mention relationships to other properties or methods

### Type Safety
- Always specify the most specific type possible
- Use union types for properties that can hold multiple types
- Specify array shapes when structure is known
- Include null in type when property can be null
- Use generic types for collections when appropriate 