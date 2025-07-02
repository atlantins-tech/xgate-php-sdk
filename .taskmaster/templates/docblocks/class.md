# 🏗️ Class Documentation Templates

This file contains standardized PHPDoc templates for class-level documentation in the XGATE PHP SDK.

## 📋 Basic Class Template

```php
/**
 * [Brief one-line description of the class purpose]
 *
 * [Detailed description explaining:
 * - What the class does
 * - How it fits into the system
 * - Key responsibilities
 * - Important usage notes or constraints]
 *
 * @package XGate\[Namespace]
 * @author XGate PHP SDK Contributors
 * @since 1.0.0
 *
 * @example Basic usage
 * ```php
 * $instance = new ClassName($dependency1, $dependency2);
 * $result = $instance->primaryMethod();
 * echo "Result: " . $result;
 * ```
 */
class ClassName
{
    // Class implementation
}
```

## 🎯 Specialized Class Templates

### API Resource Class

```php
/**
 * [Resource Name] Resource for XGATE API operations
 *
 * Handles HTTP operations for [resource] management through the XGATE API.
 * Provides methods for [creating, retrieving, updating, listing] [resources]
 * with proper error handling and DTO conversion.
 *
 * [Additional context about the resource, business rules, or constraints]
 *
 * @package XGate\Resource
 * @author XGate PHP SDK Contributors
 *
 * @example Basic [resource] operations
 * ```php
 * $[resource]Resource = new [Resource]Resource($httpClient, $logger);
 * 
 * // Create new [resource]
 * $new[Resource] = $[resource]Resource->create($param1, $param2);
 * 
 * // Get [resource] by ID
 * $[resource] = $[resource]Resource->get('[resource]-123');
 * 
 * // List all [resources]
 * $[resources] = $[resource]Resource->list();
 * ```
 */
class ResourceNameResource
{
    // Implementation
}
```

### Data Transfer Object (DTO)

```php
/**
 * [Entity Name] Data Transfer Object for XGATE API
 *
 * Simple DTO class for transporting [entity] data between the XGATE API
 * and client applications. Provides JSON serialization/deserialization
 * and basic type safety without complex domain validation.
 *
 * [Additional context about the entity, relationships, or business rules]
 *
 * @package XGate\Model
 * @author XGate PHP SDK Contributors
 *
 * @example Basic [entity] DTO usage
 * ```php
 * // Creating from API response
 * $[entity]Data = json_decode($apiResponse, true);
 * $[entity] = [Entity]::fromArray($[entity]Data);
 *
 * // Converting to API request
 * $requestData = $[entity]->toArray();
 * ```
 */
class EntityName implements JsonSerializable
{
    // Implementation
}
```

### Exception Class

```php
/**
 * [Exception Type] exception for XGATE SDK operations
 *
 * Thrown when [specific condition or error scenario occurs].
 * [Explain when this exception is used and how to handle it.]
 *
 * [Additional context about error recovery, retry logic, or debugging tips]
 *
 * @package XGate\Exception
 * @author XGate PHP SDK Contributors
 *
 * @example Handling [exception type] exceptions
 * ```php
 * try {
 *     $result = $api->operation();
 * } catch ([Exception]Exception $e) {
 *     echo "Error: " . $e->getMessage();
 *     // Handle specific error scenario
 * }
 * ```
 */
class ExceptionNameException extends XGateException
{
    // Implementation
}
```

### Configuration Manager

```php
/**
 * [Configuration Type] configuration manager for XGATE SDK
 *
 * This class is responsible for loading and managing [specific type]
 * configurations for the SDK, including [list key configuration areas].
 * Supports [configuration sources] and validation of [required settings].
 *
 * [Additional context about configuration precedence, validation rules, or usage patterns]
 *
 * @package XGate\Configuration
 * @author XGate PHP SDK Contributors
 * @since 1.0.0
 *
 * @example Basic configuration usage
 * ```php
 * // Basic usage
 * $config = new [Configuration]Manager();
 * $value = $config->getValue();
 *
 * // With custom settings
 * $config = new [Configuration]Manager('/custom/path');
 *
 * // From array
 * $config = [Configuration]Manager::fromArray([
 *     'setting1' => 'value1',
 *     'setting2' => 'value2'
 * ]);
 * ```
 */
class ConfigurationManagerName
{
    // Implementation
}
```

### Abstract Base Class

```php
/**
 * Abstract base class for [category] implementations
 *
 * Provides common functionality and defines the contract for [category]
 * implementations in the XGATE SDK. Subclasses must implement [key methods]
 * while inheriting [shared functionality].
 *
 * [Additional context about the inheritance hierarchy, design patterns used, or extension points]
 *
 * @package XGate\[Namespace]
 * @author XGate PHP SDK Contributors
 * @since 1.0.0
 *
 * @example Extending the base class
 * ```php
 * class ConcreteImplementation extends AbstractBaseName
 * {
 *     protected function requiredMethod(): string
 *     {
 *         return 'implementation-specific value';
 *     }
 * }
 * 
 * $instance = new ConcreteImplementation();
 * $result = $instance->inheritedMethod();
 * ```
 */
abstract class AbstractBaseName
{
    // Implementation
}
```

### Factory Class

```php
/**
 * Factory for creating [object type] instances
 *
 * Provides a centralized way to create [object type] instances with
 * proper configuration and dependency injection. Handles [creation logic]
 * and ensures [consistency/validation requirements].
 *
 * [Additional context about factory patterns, supported types, or configuration options]
 *
 * @package XGate\[Namespace]
 * @author XGate PHP SDK Contributors
 * @since 1.0.0
 *
 * @example Creating instances via factory
 * ```php
 * $factory = new [Object]Factory($config, $dependencies);
 * 
 * // Create with default settings
 * $instance = $factory->create();
 * 
 * // Create with custom configuration
 * $instance = $factory->create(['option1' => 'value1']);
 * 
 * // Create specific type
 * $instance = $factory->createType('specific_type');
 * ```
 */
class ObjectFactory
{
    // Implementation
}
```

## 🏷️ Common Tags Reference

### Required Tags
- `@package` - Always specify the namespace package
- `@author` - Always use "XGate PHP SDK Contributors"

### Optional Tags
- `@since` - Version when class was introduced (use for new classes)
- `@version` - Current version (use for significant changes)
- `@api` - Mark as stable public API
- `@internal` - Mark as internal implementation detail
- `@deprecated` - Mark as deprecated with migration info

### Documentation Tags
- `@see` - Reference related classes or methods
- `@link` - External documentation links
- `@example` - Always include for public classes

## 🎨 Style Guidelines

### Description Format
1. **First line**: Brief, descriptive summary ending with period
2. **Blank line**
3. **Detailed description**: Multiple paragraphs explaining purpose, context, and usage
4. **Blank line**
5. **Tags**: Ordered according to PHP CS Fixer rules

### Example Quality
- Show realistic usage scenarios
- Include error handling where relevant
- Demonstrate both simple and advanced usage
- Use meaningful variable names and values

### Package Naming
- Use full namespace path after `XGate\`
- Be consistent with existing package structure
- Group related functionality under same package

### Language Style
- Use active voice
- Be concise but comprehensive
- Explain business context, not just technical details
- Include "why" not just "what" 